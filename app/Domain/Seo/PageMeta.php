<?php

namespace App\Domain\Seo;

/**
 * Metadatele unei pagini publice.
 *
 * Un singur obiect în locul a zece variabile Blade răzlețe: dacă o pagină uită
 * descrierea sau canonical-ul, se vede aici, nu la trei luni după indexare.
 */
final class PageMeta
{
    /** @param list<array{name: string, url: string|null}> $breadcrumbs */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonical = null,
        public array $breadcrumbs = [],
        public bool $noindex = false,
        public ?string $ogType = 'website',
        /** Structured data JSON-LD, ca array de grafuri. */
        public array $jsonLd = [],
    ) {}

    /** Titlul complet, cu sufixul de brand — dar fără să-l dubleze pe pagina de start. */
    public function fullTitle(): string
    {
        $site = config('seo.site_name');

        return str_contains($this->title, $site)
            ? $this->title
            : "{$this->title} · {$site}";
    }

    /**
     * Descrierile prea lungi se taie în rezultate; cele prea scurte nu spun nimic.
     * ~155 de caractere e limita practică.
     */
    public function trimmedDescription(): string
    {
        return mb_strlen($this->description) <= 158
            ? $this->description
            : mb_substr($this->description, 0, 155).'…';
    }
}
