<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    /**
     * Cheia, explicația și indiciile sunt `#[Hidden]` pe model (§2), deci
     * Filament nu le vede când umple formularul din `attributesToArray()`.
     *
     * Fără pasul ăsta, formularul de editare se deschidea cu ele goale: salvarea
     * eșua pe „answer obligatoriu", iar dacă n-ar fi eșuat, ar fi șters
     * explicația și indiciile itemului editat.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            'answer' => $this->record->answer,
            'explanation' => $this->record->explanation,
            'hints' => $this->record->hintList(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
