<?php

namespace App\Http\Resources;

use App\Domain\Grading\AnswerKeyMask;
use App\Models\Item;
use App\Models\SessionItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ==========================================================================
 *  SINGURA formă în care un item are voie să ajungă la client ÎNAINTE de
 *  trimiterea răspunsului.
 * ==========================================================================
 *
 * Lista de câmpuri e ALBĂ (explicită), nu neagră. Un `$item->toArray()` cu
 * excluderi ar fi o bombă cu ceas: prima dată când cineva adaugă o coloană nouă
 * cu informație sensibilă, ea ajunge automat la elevi.
 *
 * Ce nu apare niciodată aici: `answer`, `explanation`, `hint_2`, statisticile de
 * dificultate (`item_stats.p_correct` ar spune elevului cât de greu e itemul
 * înainte să-l citească).
 *
 * `hint` apare doar dacă modul de joc permite indicii — în simulare și în duel,
 * nu.
 *
 * @property-read Item $resource
 */
class ItemForStudentResource extends JsonResource
{
    /**
     * Fără împachetare în `data`.
     *
     * Props-urile Inertia și răspunsurile de API trebuie să aibă exact aceeași
     * formă, altfel clientul web ar citi `item.uuid`, iar cel mobil
     * `item.data.uuid` — două contracte pentru aceleași date.
     */
    public static $wrap = null;

    public function __construct(
        $resource,
        private bool $hintsAllowed = false,
        private ?SessionItem $sessionItem = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $item = $this->resource;

        return [
            'uuid' => $item->uuid,
            'type' => $item->type->value,
            'points' => $item->points,
            'payload' => $this->sanitizePayload($item->payload, $item->type->value),

            // TEXTUL indiciilor nu pleacă de aici — doar câte există și câte a
            // cerut deja elevul. Fiecare indiciu se ia individual, prin
            // POST /antrenament/{sesiune}/indiciu, ca serverul să poată
            // contoriza penalizarea de XP.
            'hints' => [
                'allowed' => $this->hintsAllowed && $item->hintCount() > 0,
                'total' => $item->hintCount(),
                'revealed' => (int) ($this->sessionItem->hints_used ?? 0),
            ],

            'position' => $this->sessionItem?->position,
            'skill' => $item->relationLoaded('skills')
                ? $item->skills->first()?->only(['id', 'name'])
                : null,
        ];
    }

    /**
     * Ultima plasă de siguranță: chiar dacă cineva scrie într-o zi cheia de
     * corectare în `payload` (în loc de coloana `answer`), o eliminăm aici.
     */
    private function sanitizePayload(array $payload, string $type): array
    {
        unset(
            $payload['answer'], $payload['correct'], $payload['solution'],
            $payload['order'], $payload['pairs'], $payload['values'],
            $payload['hint'], $payload['hints'],
        );

        // Cheile variantelor și ale pașilor se maschează, iar ordinea se
        // amestecă: autorii scriu varianta corectă prima și pașii în ordinea
        // bună, deci forma din bancă ar fi chiar răspunsul (vezi AnswerKeyMask).
        $seed = $this->sessionItem?->id ?? 'u'.(request()->user()?->id ?? 0);

        return app(AnswerKeyMask::class)->maskPayload($this->resource, $payload, (string) $seed);
    }
}
