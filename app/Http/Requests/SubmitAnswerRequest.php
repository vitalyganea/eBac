<?php

namespace App\Http\Requests;

use App\Domain\Attempts\SubmitAnswerData;
use App\Enums\ClientSource;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Validare partajată între clientul web și API-ul mobil.
 *
 * Un singur set de reguli, deci imposibil să divergă. Metoda toData() e locul
 * unic în care HTTP-ul se transformă în date de domeniu.
 */
class SubmitAnswerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // `hints_used` NU e acceptat de la client: e contorizat pe server în
            // `session_items` de către App\Domain\Sessions\RevealHint. Un câmp
            // trimis din browser ar fi o invitație la a păstra XP-ul integral.
            'client_uuid' => ['required', 'uuid'],
            'item_uuid' => ['required', 'uuid', 'exists:items,uuid'],
            'session_uuid' => ['nullable', 'uuid'],
            'answer' => ['required', 'array'],
            'client_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],

            // Doar clienții mobili trimit answered_at (răspunsuri date offline).
            'answered_at' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'in:web,ios,android'],
            'app_version' => ['nullable', 'string', 'max:24'],
        ];
    }

    public function toData(): SubmitAnswerData
    {
        $source = ClientSource::from($this->input('source', 'web'));
        $answeredAt = $this->date('answered_at');

        return new SubmitAnswerData(
            clientUuid: $this->string('client_uuid')->toString(),
            itemId: Item::where('uuid', $this->input('item_uuid'))->value('id'),
            answer: $this->input('answer'),
            sessionUuid: $this->input('session_uuid'),
            clientMs: $this->integer('client_ms') ?: null,
            answeredAt: $this->normalizeAnsweredAt($answeredAt, $source),
            source: $source,
            appVersion: $this->input('app_version'),
        );
    }

    /**
     * Clientul web nu are voie să-și declare singur momentul răspunsului.
     * Cel mobil are — pentru offline — dar într-o fereastră strictă: nimic din
     * viitor, nimic mai vechi de 7 zile. Altfel cineva trimite un lot fabricat
     * și își cumpără un streak.
     */
    private function normalizeAnsweredAt(?Carbon $answeredAt, ClientSource $source): ?Carbon
    {
        if ($answeredAt === null || ! $source->isMobile()) {
            return null;
        }

        if ($answeredAt->isFuture() || $answeredAt->lt(now()->subDays(7))) {
            return null;
        }

        return $answeredAt;
    }
}
