<?php

namespace App\Domain\Learning;

use App\Models\LearningGap;
use App\Models\Lesson;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Înregistrează că elevul nu înțelege ceva.
 *
 * Nu acordă XP și nu penalizează nimic. A recunoaște că nu înțelegi e cea mai
 * utilă acțiune pe care o poate face un elev: orice recompensă ar transforma-o
 * în ceva de exploatat, iar orice penalizare ar face-o să dispară.
 */
final class MarkGap
{
    public function __invoke(
        User $user,
        Skill $skill,
        ?Lesson $lesson = null,
        ?string $blockId = null,
        ?string $note = null,
        string $source = 'lesson',
    ): LearningGap {
        $block = $blockId !== null ? $lesson?->findBlock($blockId) : null;

        // `skill_id` face parte din cheia de potrivire, nu doar `block_id`.
        // Fără el, două teme diferite marcate FĂRĂ bloc (venind dinspre
        // exerciții) s-ar suprascrie reciproc: `where block_id is null` ar găsi
        // primul rând și l-ar rescrie.
        $gap = LearningGap::updateOrCreate([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'block_id' => $blockId,
        ], [
            'lesson_id' => $lesson?->id,
            'block_type' => $block['type'] ?? null,
            // Fotografie a conținutului: lecția se poate edita sau șterge, dar
            // elevul trebuie să înțeleagă în continuare ce anume marcase.
            'excerpt' => $this->excerpt($block),
            'note' => $note,
            'source' => $source,
        ]);

        // `resolved_at` nu e mass-assignable — e stare de progres, nu date de
        // formular. Remarcarea a ceva deja lămurit îl redeschide explicit.
        if ($gap->resolved_at !== null) {
            $gap->forceFill(['resolved_at' => null])->save();
        }

        return $gap;
    }

    public function resolve(User $user, int $gapId): void
    {
        LearningGap::where('user_id', $user->id)
            ->whereKey($gapId)
            ->update(['resolved_at' => now(), 'updated_at' => now()]);
    }

    public function reopen(User $user, int $gapId): void
    {
        LearningGap::where('user_id', $user->id)
            ->whereKey($gapId)
            ->update(['resolved_at' => null, 'updated_at' => now()]);
    }

    /** @param array<string, mixed>|null $block */
    private function excerpt(?array $block): ?string
    {
        if ($block === null) {
            return null;
        }

        $text = $block['text']
            ?? $block['latex']
            ?? $block['problem']
            ?? $block['caption']
            ?? $block['title']
            ?? null;

        return $text ? Str::limit(strip_tags((string) $text), 240) : null;
    }
}
