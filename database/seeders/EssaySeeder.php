<?php

namespace Database\Seeders;

use App\Models\EssayPrompt;
use App\Models\Subject;
use Database\Seeders\Eseu\Engleza;
use Database\Seeders\Eseu\Romana;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Cerințele de producere scrisă pentru autoevaluare (date în `Eseu\*`).
 *
 * Fiecare grilă e verificată la seeding: suma punctajelor maxime trebuie să
 * dea exact totalul subiectului din barem (40, 30, 24, 20). O transcriere
 * greșită a baremului se oprește aici, nu ajunge la elev ca notă falsă.
 */
class EssaySeeder extends Seeder
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    public function run(): void
    {
        $engleza = Subject::where('slug', 'limba-engleza')->firstOrFail();
        $romana = Subject::where('slug', 'limba-romana')->firstOrFail();

        foreach (Engleza::all() as $i => $p) {
            $subiect = $p['kind'] === 'eseu' ? 'subiectul2' : 'subiectul3';
            $this->save($engleza->id, "engleza-{$p['session']}-{$p['kind']}", $p, $i,
                "ance:12_len_barem_{$p['session']}", self::ANCE."12_len_barem_{$p['session']}.pdf#{$subiect}");
        }

        foreach (Romana::all() as $i => $p) {
            $this->save($romana->id, 'romana-'.str_replace('_', '-', $p['session']), $p, $i,
                "ance:12_llro_barem_{$p['session']}", self::ANCE."12_llro_barem_{$p['session']}.pdf#eseu");
        }
    }

    private function save(int $subjectId, string $slug, array $p, int $order, string $source, string $ref): void
    {
        $rubric = array_filter([
            'total' => $p['total'],
            'criteria' => $p['criteria'],
            'errors' => $p['errors'] ?? null,
            'volume' => $p['volume'] ?? null,
        ], fn ($v) => $v !== null);

        $max = array_sum(array_map(fn (array $c) => max(array_column($c['options'], 0)), $rubric['criteria']))
            + array_sum(array_column($rubric['errors'] ?? [], 'max'))
            + ($rubric['volume']['max'] ?? 0);

        if ($max !== $p['total']) {
            throw new RuntimeException("{$slug}: grila dă {$max} puncte, baremul — {$p['total']}");
        }

        $keys = array_merge(array_column($rubric['criteria'], 'key'), array_column($rubric['errors'] ?? [], 'key'));
        if (count($keys) !== count(array_unique($keys))) {
            throw new RuntimeException("{$slug}: chei de criteriu duplicate");
        }

        EssayPrompt::updateOrCreate(['slug' => $slug], [
            'subject_id' => $subjectId,
            'kind' => $p['kind'],
            'title' => $p['title'],
            'task' => $p['task'],
            'word_min' => $p['words'][0] ?? null,
            'word_max' => $p['words'][1] ?? null,
            'rubric' => $rubric,
            'source' => $source,
            'source_ref' => $ref,
            'sort_order' => $order,
        ]);
    }
}
