<?php

namespace App\Domain\Seo;

use App\Models\Item;
use App\Models\Skill;
use App\Models\Subject;

/**
 * Construiește datele structurate schema.org.
 *
 * E cel mai mare levier de SEO pentru conținut educațional: Google are rezultate
 * îmbogățite pentru `Quiz`, `Question` și `FAQPage`, iar o pagină cu exerciții
 * rezolvate marcate corect poate apărea cu răspunsurile direct în rezultate.
 *
 * Toate entitățile declară `inLanguage: ro-MD` și zona deservită Republica
 * Moldova — semnale explicite de țintire geografică.
 */
final class JsonLd
{
    public static function organization(): array
    {
        $domain = config('seo.domain');

        return [
            '@type' => 'EducationalOrganization',
            '@id' => "https://{$domain}/#organization",
            'name' => config('seo.site_name'),
            'url' => "https://{$domain}",
            'description' => config('seo.default_description'),
            'inLanguage' => 'ro-MD',
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Republica Moldova',
                'identifier' => 'MD',
            ],
            'audience' => [
                '@type' => 'EducationalAudience',
                'educationalRole' => 'student',
                'audienceType' => 'Elevi de liceu, candidați la bacalaureat',
            ],
        ];
    }

    /** @param list<array{name: string, url: string|null}> $items */
    public static function breadcrumbs(array $items): ?array
    {
        if (count($items) < 2) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ]))->all(),
        ];
    }

    public static function course(Subject $subject, int $skillCount, int $itemCount): array
    {
        return [
            '@type' => 'Course',
            'name' => "Pregătire BAC — {$subject->name}",
            'description' => "Pregătire pentru proba de {$subject->name} la examenul național de bacalaureat din Republica Moldova, pe programa oficială ANCE. {$skillCount} competențe, {$itemCount} exerciții cu rezolvare.",
            'inLanguage' => 'ro-MD',
            'provider' => ['@id' => 'https://'.config('seo.domain').'/#organization'],
            'educationalLevel' => 'Învățământ liceal',
            'teaches' => $subject->name,
            'isAccessibleForFree' => true,
        ];
    }

    /**
     * Un item cu variante devine o întrebare marcată, cu răspunsul corect
     * indicat. Se folosește DOAR pe paginile publice de exerciții rezolvate —
     * niciodată pentru itemii de holdout.
     */
    public static function question(Item $item): ?array
    {
        $stem = $item->payload['stem'] ?? null;
        $choices = $item->payload['choices'] ?? null;
        $correct = $item->gradingKey()['correct'] ?? null;

        if (! $stem || ! is_array($choices) || ! is_array($correct)) {
            return null;
        }

        $correctKey = (string) ($correct[0] ?? '');
        $answers = collect($choices);

        $right = $answers->firstWhere('key', $correctKey);

        if ($right === null) {
            return null;
        }

        $text = fn (array $c) => $c['text'] ?? $c['latex'] ?? '';

        return array_filter([
            '@type' => 'Question',
            'name' => strip_tags($stem),
            'text' => strip_tags($stem),
            'inLanguage' => 'ro-MD',
            'acceptedAnswer' => array_filter([
                '@type' => 'Answer',
                'text' => $text($right),
                'comment' => $item->explanation ? ['@type' => 'Comment', 'text' => $item->explanation] : null,
            ]),
            'suggestedAnswer' => $answers
                ->reject(fn ($c) => ($c['key'] ?? null) === $correctKey)
                ->map(fn ($c) => ['@type' => 'Answer', 'text' => $text($c)])
                ->values()->all(),
        ]);
    }

    /** @param iterable<Item> $items */
    public static function quiz(Skill $skill, iterable $items): ?array
    {
        $questions = collect($items)
            ->map(fn (Item $i) => self::question($i))
            ->filter()
            ->values();

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'Quiz',
            'name' => "Exerciții rezolvate: {$skill->name}",
            'about' => ['@type' => 'Thing', 'name' => $skill->name],
            'inLanguage' => 'ro-MD',
            'educationalLevel' => 'Învățământ liceal',
            'isAccessibleForFree' => true,
            'hasPart' => $questions->all(),
        ];
    }

    /** Împachetează totul într-un singur graf, ca să nu avem cinci blocuri script. */
    public static function graph(array $nodes): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter($nodes)),
        ];
    }
}
