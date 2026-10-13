<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Conținut')
                    ->description('Ce vede elevul înainte de a răspunde.')
                    ->schema([
                        Select::make('subject_id')
                            ->label('Disciplină')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->required(),

                        Select::make('skills')
                            ->label('Competențe')
                            ->relationship('skills', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Prima competență e cea principală; progresul se leagă de ea.'),

                        Select::make('type')
                            ->label('Tip de item')
                            ->options(fn () => collect(ItemType::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                            ->required()
                            ->live(),

                        Textarea::make('payload')
                            ->label('Payload (JSON)')
                            ->rows(10)
                            ->required()
                            ->helperText('Enunț, variante, pași. Formulele LaTeX se scriu între $...$.')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state)
                                ? (json_decode($state, true) ?? [])
                                : $state),
                    ])->columns(1),

                Section::make('Cheia de corectare')
                    ->description('NU ajunge niciodată la elev înainte de trimiterea răspunsului.')
                    ->schema([
                        Textarea::make('answer')
                            ->label('Answer (JSON)')
                            ->rows(5)
                            ->required()
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state)
                                ? (json_decode($state, true) ?? [])
                                : $state),

                        Textarea::make('explanation')
                            ->label('Explicație')
                            ->rows(4)
                            ->helperText('Se arată după răspuns. Spune DE CE, nu doar care e răspunsul.'),
                    ])->columns(1),

                /**
                 * Indiciile sunt o listă ordonată, nu două câmpuri fixe.
                 *
                 * Ținta e ca fiecare întrebare să aibă indicii în trepte: primul
                 * împinge spre metodă, ultimul e aproape rezolvarea. Elevul le
                 * cere pe rând, iar fiecare costă XP — de asta ordinea contează.
                 */
                Section::make('Indicii')
                    ->description('În ordine crescătoare de cât dezvăluie. Primul = cel mai vag.')
                    ->schema([
                        Repeater::make('hints')
                            ->label('')
                            ->simple(
                                Textarea::make('hint')
                                    ->label('')
                                    ->rows(2)
                                    ->required()
                                    ->placeholder('Ex.: Gândește-te ce se întâmplă cu semnul la înmulțirea cu un număr negativ.')
                            )
                            ->addActionLabel('Adaugă indiciu')
                            ->reorderable()
                            ->defaultItems(0)
                            ->helperText('Fiecare indiciu cerut reduce XP-ul: 20% / 35% / 50%.'),
                    ])->columns(1),

                Section::make('Clasificare')
                    ->schema([
                        Select::make('status')
                            ->label('Stare')
                            ->options(fn () => collect(ItemStatus::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                            ->required(),

                        TextInput::make('difficulty_seed')
                            ->label('Dificultate estimată')
                            ->numeric()->minValue(1)->maxValue(5)->default(3)
                            ->helperText('Doar punct de plecare. Elo-ul real se calibrează din răspunsuri.'),

                        TextInput::make('points')
                            ->label('Puncte în barem')
                            ->numeric()->minValue(1)->default(1),

                        Select::make('language')
                            ->label('Limbă')
                            ->options(['ro' => 'Română', 'ru' => 'Rusă'])
                            ->default('ro')
                            ->required(),

                        Toggle::make('is_exam_holdout')
                            ->label('Rezervat simulărilor (holdout)')
                            ->helperText('Nu se servește în antrenament, ca estimarea de notă să rămână validă.'),

                        TextInput::make('source')
                            ->label('Sursă')
                            ->placeholder('ance-2026-real-baza | generated | community'),

                        TextInput::make('source_ref')
                            ->label('Referință sursă')
                            ->helperText('Link sau identificator. Un item fără sursă nu e verificabil.'),
                    ])->columns(2),
            ]);
    }
}
