<?php

namespace App\Models;

use App\Models\Concerns\TracksContentRevision;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Teoria unei competențe.
 *
 * Conținutul e o listă de blocuri tipizate (vezi migrația). Nimic din el nu e
 * HTML liber: fiecare tip de bloc are propriul component de randare, deci se
 * poate afișa diferit pe mobil și se poate valida automat.
 */
#[Fillable(['skill_id', 'title', 'summary', 'objectives', 'blocks', 'reading_minutes',
    'status', 'language', 'created_by'])]
class Lesson extends Model
{
    use TracksContentRevision;

    protected function casts(): array
    {
        return [
            'objectives' => 'array',
            'blocks' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Fiecare bloc primește un identificator STABIL la prima salvare.
     *
     * Fără el, blocurile ar fi identificate prin poziția în array — iar un bloc
     * inserat la mijlocul unei lecții ar face ca tot ce a marcat un elev să
     * arate brusc spre alt conținut. Id-urile existente nu se ating niciodată.
     */
    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            if (! $lesson->isDirty('blocks')) {
                return;
            }

            $lesson->blocks = collect($lesson->blocks ?? [])
                ->map(function (array $block) {
                    $block['id'] ??= Str::lower(Str::random(8));

                    return $block;
                })
                ->all();
        });
    }

    /** @return array<string, mixed>|null */
    public function findBlock(string $blockId): ?array
    {
        return collect($this->blocks ?? [])->firstWhere('id', $blockId);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', 'published');
    }

    /**
     * Estimare de timp de citire, dacă autorul nu a pus una.
     *
     * ~180 de cuvinte pe minut, plus 20 de secunde pentru fiecare bloc care cere
     * gândire (formulă, exemplu, grafic) — acelea nu se „citesc", se urmăresc.
     */
    public function readingMinutes(): int
    {
        if ($this->reading_minutes) {
            return $this->reading_minutes;
        }

        $words = 0;
        $heavy = 0;

        foreach ($this->blocks ?? [] as $block) {
            $words += str_word_count((string) ($block['text'] ?? $block['title'] ?? ''));

            if (in_array($block['type'] ?? '', ['formula', 'example', 'plot', 'figure'], true)) {
                $heavy++;
            }
        }

        return max(1, (int) ceil($words / 180 + $heavy / 3));
    }

    /** @return list<string> Tipurile de bloc folosite — util pentru validare. */
    public function blockTypes(): array
    {
        return collect($this->blocks ?? [])->pluck('type')->unique()->values()->all();
    }
}
