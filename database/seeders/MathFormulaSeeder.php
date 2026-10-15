<?php

namespace Database\Seeders;

use App\Models\Formula;
use App\Models\Skill;
use App\Models\Subject;
use Database\Seeders\Matematica\Formule;
use Illuminate\Database\Seeder;

/**
 * Secțiunea „Formule" la matematică (date în `Matematica\Formule`).
 *
 * Sursa fiecărei formule (§11b): cel mai recent test ANCE în a cărui anexă a
 * apărut, sau — pentru cele care n-au apărut niciodată — unitatea de conținut
 * din programa de examen.
 */
class MathFormulaSeeder extends Seeder
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    public function run(): void
    {
        $subject = Subject::where('slug', 'matematica')->firstOrFail();
        $skills = Skill::where('subject_id', $subject->id)->pluck('id', 'slug');

        foreach (Formule::all() as $i => [$slug, $skill, $name, $latex, $body, $variables, $pitfall, $annex, $unit]) {
            [$source, $ref] = $annex === []
                ? ['ance:programa_bac_matematica_romana_0', self::ANCE."programa_bac_matematica_romana_0.pdf#{$unit}"]
                : self::latestTest($annex);

            Formula::updateOrCreate(
                ['subject_id' => $subject->id, 'slug' => $slug],
                [
                    'skill_id' => $skills[$skill] ?? throw new \RuntimeException("Competență necunoscută: {$skill}"),
                    'name' => $name,
                    'latex' => self::stacked($latex),
                    'body' => $body,
                    'variables' => $variables,
                    'pitfall' => $pitfall,
                    'annex' => $annex,
                    'source' => $source,
                    'source_ref' => $ref,
                    'sort_order' => $i,
                ],
            );
        }
    }

    /**
     * Rupe pe rânduri o formulă cu mai multe părți („$a^m a^n = \ldots$, $(a^m)^n = \ldots$").
     *
     * Pe un telefon de 360px, cinci egalități pe un rând ies din ecran. KaTeX nu
     * rupe singur o formulă afișată, deci separatorii de la nivelul de sus
     * (`,\quad`, `,\qquad`) devin treceri la rând într-un `gathered`. Cei din
     * interiorul unui mediu (`vmatrix`, `array`) nu se ating.
     */
    private static function stacked(string $latex): string
    {
        $parts = [];
        $depth = 0;
        $start = 0;

        preg_match_all('/\\\\begin\{|\\\\end\{|,\s*\\\\q?quad\s*/', $latex, $m, PREG_OFFSET_CAPTURE);

        foreach ($m[0] as [$token, $offset]) {
            if (str_starts_with($token, '\\begin')) {
                $depth++;
            } elseif (str_starts_with($token, '\\end')) {
                $depth--;
            } elseif ($depth === 0) {
                $parts[] = substr($latex, $start, $offset - $start);
                $start = $offset + strlen($token);
            }
        }

        if ($parts === []) {
            return $latex;
        }

        $parts[] = substr($latex, $start);

        return '\\begin{gathered}'.implode(' \\\\ ', $parts).'\\end{gathered}';
    }

    /**
     * `r_ss25` e mai recent decât `r_sb25`: sesiunea suplimentară vine după cea de bază.
     *
     * @param  list<string>  $codes
     * @return array{0: string, 1: string}
     */
    private static function latestTest(array $codes): array
    {
        usort($codes, fn (string $a, string $b) => self::rank($b) <=> self::rank($a));
        [$profile, $session] = explode('_', $codes[0]);
        $file = "12_mat_test_{$profile}_ro_{$session}";

        return ["ance:{$file}", self::ANCE."{$file}.pdf#anexa"];
    }

    private static function rank(string $code): int
    {
        $session = explode('_', $code)[1];

        return (int) substr($session, 2) * 10 + (str_starts_with($session, 'ss') ? 1 : 0);
    }
}
