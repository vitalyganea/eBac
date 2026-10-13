<?php

namespace App\Filament\Resources\Items\Tables;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Item;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Coada de validare a itemilor.
 *
 * Ăsta e panoul care face banca de itemi posibilă. Motorul de joc e scris în
 * câteva săptămâni; 10.000 de itemi buni sunt munca de un an, iar aici mor
 * majoritatea proiectelor de tipul ăsta. Fluxul e:
 *
 *   Draft (generat) → În validare → Publicat
 *
 * Plus două semnale automate care înlocuiesc recitirea manuală a întregii bănci:
 *   - `item_stats.needs_review`, ridicat de RecalibrateItem când rata observată
 *     de succes contrazice dificultatea (cheie greșită sau enunț ambiguu);
 *   - numărul de semnalări trimise de elevi.
 */
class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('payload.stem')
                    ->label('Enunț')
                    ->wrap()
                    ->limit(90)
                    ->formatStateUsing(fn ($state, Item $record) => $state
                        ?: Str::limit((string) ($record->payload['problem'] ?? $record->payload['stem_template'] ?? '—'), 90))
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereRaw("payload->>'stem' ilike ?", ["%{$search}%"])),

                TextColumn::make('skills.name')
                    ->label('Competență')
                    ->badge()
                    ->color('gray')
                    ->limitList(1),

                TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (ItemType $state) => $state->label()),

                TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->formatStateUsing(fn (ItemStatus $state) => $state->label())
                    ->color(fn (ItemStatus $state) => match ($state) {
                        ItemStatus::Draft => 'gray',
                        ItemStatus::Review => 'warning',
                        ItemStatus::Published => 'success',
                        ItemStatus::Retired => 'danger',
                    }),

                // Elo pe item: dificultatea reală, măsurată, nu estimată de autor.
                TextColumn::make('stats.rating')
                    ->label('Elo')
                    ->numeric(0)
                    ->sortable()
                    ->placeholder('necalibrat')
                    ->tooltip('Dificultatea măsurată din răspunsurile reale'),

                TextColumn::make('stats.p_correct')
                    ->label('% corect')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : round($state * 100).'%')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state < 0.08 => 'danger',
                        $state > 0.97 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('stats.attempts_count')
                    ->label('Răspunsuri')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('stats.needs_review')
                    ->label('Suspect')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('')
                    ->tooltip('Semnalat automat: statisticile contrazic dificultatea declarată'),

                IconColumn::make('is_exam_holdout')
                    ->label('Holdout')
                    ->boolean()
                    ->tooltip('Rezervat simulărilor — nu se servește în antrenament')
                    ->toggleable(),

                TextColumn::make('source')
                    ->label('Sursă')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('revision')
                    ->label('Rev.')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stare')
                    ->options(fn () => collect(ItemStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),

                SelectFilter::make('subject')
                    ->label('Disciplină')
                    ->relationship('subject', 'name'),

                SelectFilter::make('type')
                    ->label('Tip')
                    ->options(fn () => collect(ItemType::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),

                TernaryFilter::make('needs_review')
                    ->label('Semnalat automat')
                    ->queries(
                        true: fn ($q) => $q->whereHas('stats', fn ($s) => $s->where('needs_review', true)),
                        false: fn ($q) => $q->whereDoesntHave('stats', fn ($s) => $s->where('needs_review', true)),
                        blank: fn ($q) => $q,
                    ),

                TernaryFilter::make('is_exam_holdout')->label('Holdout'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('publish')
                    ->label('Publică')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Item $record) => $record->status !== ItemStatus::Published)
                    ->requiresConfirmation()
                    ->modalDescription('Itemul devine vizibil elevilor. Verifică enunțul și cheia de corectare.')
                    ->action(function (Item $record) {
                        $record->update([
                            'status' => ItemStatus::Published,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()->title('Item publicat')->success()->send();
                    }),

                Action::make('retire')
                    ->label('Retrage')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('danger')
                    ->visible(fn (Item $record) => $record->status === ItemStatus::Published)
                    ->requiresConfirmation()
                    // Retragem, nu ștergem: `attempts` referă itemul, iar
                    // istoricul elevilor trebuie să rămână interpretabil.
                    ->modalDescription('Itemul nu se mai servește. Răspunsurile deja date rămân în istoric.')
                    ->action(function (Item $record) {
                        $record->update(['status' => ItemStatus::Retired]);
                        $record->stats?->update(['needs_review' => false]);

                        Notification::make()->title('Item retras')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publică selecția')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update([
                                'status' => ItemStatus::Published,
                                'reviewed_by' => auth()->id(),
                                'reviewed_at' => now(),
                            ]);

                            Notification::make()
                                ->title("{$records->count()} itemi publicați")
                                ->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
