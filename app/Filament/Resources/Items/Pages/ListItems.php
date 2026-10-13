<?php

namespace App\Filament\Resources\Items\Pages;

use App\Enums\ItemStatus;
use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Item nou')];
    }

    /**
     * Tab-urile ordonează munca de validare după urgență, nu alfabetic.
     * „Suspecți" e primul lucru pe care trebuie să-l vadă un editor: acolo sunt
     * itemii care produc activ note greșite pentru elevi.
     */
    public function getTabs(): array
    {
        return [
            'suspecti' => Tab::make('Suspecți')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->whereHas('stats', fn ($s) => $s->where('needs_review', true)))
                ->badge(fn () => Item::whereHas('stats', fn ($s) => $s->where('needs_review', true))->count())
                ->badgeColor('danger'),

            'validare' => Tab::make('În validare')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ItemStatus::Review))
                ->badge(fn () => Item::where('status', ItemStatus::Review)->count())
                ->badgeColor('warning'),

            'ciorne' => Tab::make('Ciorne')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ItemStatus::Draft))
                ->badge(fn () => Item::where('status', ItemStatus::Draft)->count()),

            'necalibrati' => Tab::make('Necalibrați')
                ->icon('heroicon-o-beaker')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->where('status', ItemStatus::Published)
                    ->where(fn ($q) => $q
                        ->whereDoesntHave('stats')
                        ->orWhereHas('stats', fn ($s) => $s->where('attempts_count', '<', 25))))
                ->badge(fn () => Item::where('status', ItemStatus::Published)
                    ->where(fn ($q) => $q->whereDoesntHave('stats')
                        ->orWhereHas('stats', fn ($s) => $s->where('attempts_count', '<', 25)))
                    ->count())
                ->badgeColor('info'),

            'publicati' => Tab::make('Publicați')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ItemStatus::Published))
                ->badge(fn () => Item::where('status', ItemStatus::Published)->count())
                ->badgeColor('success'),

            'toti' => Tab::make('Toți'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        // Dacă există itemi semnalați automat, ei au prioritate.
        return Item::whereHas('stats', fn ($s) => $s->where('needs_review', true))->exists()
            ? 'suspecti'
            : 'validare';
    }
}
