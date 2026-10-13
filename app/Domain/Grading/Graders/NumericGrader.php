<?php

namespace App\Domain\Grading\Graders;

use App\Domain\Grading\Grader;
use App\Domain\Grading\GradeResult;
use App\Domain\Grading\NumberExpression;
use App\Models\Item;

/**
 * Răspuns numeric cu toleranță.
 *
 * payload: {stem, unit?: "N", decimals?: 2}
 * answer:  {value: 3.14, tolerance?: 0.01, accept?: [3.1416], base?: 10}
 *
 * Toleranța există pentru că un elev care scrie 3.14 în loc de π nu a greșit
 * matematica; a rotunjit.
 */
final class NumericGrader implements Grader
{
    public function grade(Item $item, array $submitted): GradeResult
    {
        $key = $item->gradingKey();

        if (! array_key_exists('value', $key)) {
            return GradeResult::wrong(['error' => 'item_without_key']);
        }

        $raw = $submitted['value'] ?? null;
        $value = is_scalar($raw) ? self::parse((string) $raw, $item, $key) : null;

        if ($value === null) {
            return GradeResult::wrong(['error' => 'not_numeric']);
        }

        $tolerance = (float) ($key['tolerance'] ?? 0.0);

        $candidates = array_merge([(float) $key['value']], array_map('floatval', $key['accept'] ?? []));

        foreach ($candidates as $candidate) {
            if (abs($value - $candidate) <= $tolerance + 1e-9) {
                return GradeResult::correct();
            }
        }

        return GradeResult::wrong(['submitted' => $value]);
    }

    /**
     * Textul tastat de elev → număr, acceptând formele în care un elev scrie
     * FIRESC un răspuns corect. Auditul conținutului a găsit itemi la care
     * răspunsul corect era respins doar din cauza formei:
     *
     *   „3/2" la log₄8 sau sin 30° + …   — fracția e forma de manual
     *   „15π" la „multiplu de π"         — elevul a scris și unitatea
     *   „90°", „2%", „5 biți"            — idem
     *   „x = 3" la punctul critic        — enunțul arată chiar „x = ?"
     *   „8 192", „−5"                    — spațiu de mii, minus tipografic
     *   „9√2π", „500√3/3"               — forma exactă, ca în baremul ANCE
     *
     * Nimic din toate astea nu schimbă VALOAREA: un răspuns greșit rămâne
     * greșit. Unitatea se acceptă doar dacă e cea a itemului (`payload.unit`):
     * „50%" la un item care așteaptă 0,5 nu devine 50.
     */
    private static function parse(string $raw, Item $item, array $key): ?float
    {
        $raw = trim(strtr($raw, ['−' => '-', '–' => '-', "\u{00A0}" => ' ']));

        // „x = 3", „f'(x) = 0" → partea din dreapta.
        $raw = preg_replace('/^[a-zA-Zα-ω][\w\'()]*\s*=\s*/u', '', $raw) ?? $raw;

        if (isset($key['base'])) {
            $raw = self::stripBaseSuffix($raw, (int) $key['base']);
        }

        if (filled($unit = $item->payload['unit'] ?? null)) {
            $raw = self::stripUnit($raw, (string) $unit);
        }

        // Spațiul ca separator de mii: „8 192".
        $raw = preg_replace('/(?<=\d)\s+(?=\d{3}\b)/', '', $raw) ?? $raw;

        // Elevii din Moldova scriu virgula ca separator zecimal.
        $raw = str_replace(',', '.', trim($raw));

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        // „3/2", „9√2π", „500√3/3" — forma exactă din barem.
        return NumberExpression::evaluate($raw);
    }

    /** Unitatea itemului, cu felurile în care o tastează elevii pe telefon. */
    private static function stripUnit(string $raw, string $unit): string
    {
        $aliases = [
            'π' => ['π', 'pi', 'Pi', 'PI'],
            '°' => ['°', 'grade', 'grad', 'deg'],
            '%' => ['%', 'la sută', 'procente'],
            'biți' => ['biți', 'biti', 'bit', 'b'],
        ][$unit] ?? [$unit];

        foreach ($aliases as $alias) {
            $stripped = preg_replace('/\s*'.preg_quote($alias, '/').'$/iu', '', $raw);
            if ($stripped !== null && $stripped !== $raw && $stripped !== '') {
                return $stripped;
            }
        }

        return $raw;
    }

    /**
     * La conversiile între baze, enunțul și explicația scriu $13_{(10)}$, deci
     * elevul poate tasta și indicele: „13(10)", „13_10", „13₍₁₀₎". Valoarea e
     * corectă — a fost doar scrisă complet. Îndepărtăm indicele DOAR dacă e
     * baza cerută: „13(2)" rămâne greșit.
     */
    private static function stripBaseSuffix(string $raw, int $base): string
    {
        // Cifrele de indice (₁₀) devin un grup între paranteze, nu cifre
        // obișnuite: „13₁₀" e 13 în baza 10, nu numărul 1310.
        $raw = preg_replace_callback('/[₀-₉]+/u', fn ($m) => '('.strtr($m[0], [
            '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
            '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
        ]).')', trim($raw)) ?? $raw;
        $raw = strtr($raw, ['₍(' => '(', ')₎' => ')', '₍' => '(', '₎' => ')']);

        // Indicele trebuie să fie SEPARAT de număr — prin `_` sau paranteze.
        // Fără separator, „1310" e un răspuns greșit, nu 13 cu indice.
        return preg_replace(
            '/^([+-]?[\d.,]+)\s*(?:_\s*[({]?\s*'.$base.'\s*[)}]?|[({]\s*'.$base.'\s*[)}])$/u',
            '$1',
            $raw,
        ) ?? $raw;
    }
}
