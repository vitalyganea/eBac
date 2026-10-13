<?php

namespace App\Domain\Exams;

use App\Models\ExamBlueprint;

/**
 * Cum se traduce structura unui examen real într-o simulare pe banca de itemi.
 *
 * Itemii din bancă sunt mai mărunți decât cei de la examen: „Subiectul II" la
 * informatică e o singură problemă de 32 de puncte, la noi sunt doi itemi
 * scurți pe aceleași competențe. De aceea fiecare secțiune declară, opțional,
 * `mock_items` — câți itemi din bancă o reprezintă. Punctajul secțiunii se
 * împarte egal între ei, deci ponderea secțiunilor rămâne cea din examen.
 *
 * Fiecare secțiune poate restrânge și CAPITOLELE (`skills`): la matematică
 * toate secțiunile primesc aceleași tipuri de item, deci fără capitole o
 * „Geometrie" s-ar putea umple cu probleme de algebră.
 *
 * Secțiunile care nu se pot corecta automat (eseul, răspunsul extins) NU se
 * simulează și NU se ghicesc: rezultatul spune cinstit „acoperă X din 100 de
 * puncte" (§13).
 */
final readonly class MockPlan
{
    /**
     * @param  list<array{name: string, points: int, types: list<string>, skills: list<string>, items: int, note: ?string}>  $autoSections
     * @param  list<array{name: string, points: int, note: ?string}>  $manualSections
     */
    private function __construct(
        public ExamBlueprint $blueprint,
        public array $autoSections,
        public array $manualSections,
        public bool $declaredManual,
    ) {}

    public static function fromBlueprint(ExamBlueprint $blueprint): self
    {
        $auto = [];
        $manual = [];
        $declaredManual = true;

        foreach ($blueprint->structure ?? [] as $section) {
            $types = array_values((array) ($section['types'] ?? []));
            $points = (int) ($section['points'] ?? 0);

            // Fără tipuri de item declarate nu avem ce servi, indiferent de
            // flag: o secțiune „auto" fără tipuri ar fi tăcut punctaj pierdut.
            $isAuto = ($section['auto_gradable'] ?? true) && $types !== [];
            $declaredManual = $declaredManual && ($section['auto_gradable'] ?? true) === false;

            if ($isAuto) {
                $auto[] = [
                    'name' => (string) $section['name'],
                    'points' => $points,
                    'types' => $types,
                    // Opțional: capitolele din care vin itemii secțiunii (slug-uri
                    // de competență, frunză sau părinte). La matematică toate
                    // secțiunile au aceleași tipuri — „Algebră" și „Geometrie"
                    // se deosebesc doar prin conținut.
                    'skills' => array_values((array) ($section['skills'] ?? [])),
                    'items' => max(1, (int) ($section['mock_items'] ?? $section['items'] ?? 1)),
                    'note' => $section['note'] ?? null,
                ];
            } else {
                $manual[] = [
                    'name' => (string) $section['name'],
                    'points' => $points,
                    'note' => $section['note'] ?? null,
                ];
            }
        }

        return new self($blueprint, $auto, $manual, $declaredManual && $manual !== []);
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->autoSections, 'items'));
    }

    public function autoPoints(): int
    {
        return array_sum(array_column($this->autoSections, 'points'));
    }

    public function totalPoints(): int
    {
        return (int) $this->blueprint->total_points;
    }

    /**
     * Timpul simulării. Nu e durata examenului (180 de minute pentru zece
     * itemi scurți ar fi fără sens), ci un buget pe item, configurabil.
     */
    public function minutes(): int
    {
        return $this->itemCount() * (int) config('ebac.mock.minutes_per_item', 3);
    }

    public function isSimulable(): bool
    {
        return $this->autoSections !== [];
    }

    /**
     * Toată proba e declarată cu răspuns deschis (limba română: fiecare item se
     * corectează pe barem). Diferit de „fără conținut încă": aici nu lipsesc
     * întrebările, ci nu există o parte pe care s-o putem nota automat.
     */
    public function isFullyManual(): bool
    {
        return $this->declaredManual;
    }

    /** Rezumatul pentru interfață: ce se simulează și ce nu. */
    public function summary(): array
    {
        return [
            'session' => $this->blueprint->session,
            'profile' => $this->blueprint->profile->value,
            'profile_label' => $this->blueprint->profile->label(),
            'items' => $this->itemCount(),
            'minutes' => $this->minutes(),
            'auto_points' => $this->autoPoints(),
            'total_points' => $this->totalPoints(),
            'sections' => array_map(fn (array $s) => [
                'name' => $s['name'], 'points' => $s['points'], 'items' => $s['items'],
            ], $this->autoSections),
            'manual_sections' => $this->manualSections,
        ];
    }
}
