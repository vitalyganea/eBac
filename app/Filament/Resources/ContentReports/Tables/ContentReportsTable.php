<?php

namespace App\Filament\Resources\ContentReports\Tables;

use App\Domain\Users\ProcessAvatar;
use App\Models\ContentReport;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Coada de moderare.
 *
 * Moderatorul are nevoie să vadă în același rând CE s-a raportat și CINE, ca să
 * poată decide fără să deschidă nimic. De aceea avatarul apare direct în tabel,
 * iar numele public alături de el.
 *
 * Acțiunile sunt deliberat proporționale: cea mai blândă e respingerea
 * semnalării, cea mai dură e ștergerea avatarului plus cererea de redenumire.
 * Nu există „șterge contul" — un nume nepotrivit nu justifică pierderea
 * progresului unui elev.
 */
class ContentReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('subject_type')
                    ->label('Ce')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'avatar' ? 'Imagine' : 'Nume public')
                    ->color(fn (string $state) => $state === 'avatar' ? 'warning' : 'info'),

                ImageColumn::make('reportable.avatar_path')
                    ->label('Imagine')
                    ->circular()
                    ->getStateUsing(fn (ContentReport $record) => $record->subject_type === 'avatar'
                        ? $record->reportable?->avatar_path.'-64.webp'
                        : null),

                TextColumn::make('reportable.username')
                    ->label('Nume public')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (ContentReport $record) => $record->reportable?->avatar_hidden_at
                        ? 'imagine retrimisă la moderare'
                        : null),

                TextColumn::make('reason')
                    ->label('Motiv')
                    ->badge()
                    ->formatStateUsing(fn (ContentReport $record) => $record->reasonLabel()),

                TextColumn::make('comment')
                    ->label('Detalii')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—'),

                // Câți OAMENI diferiți au semnalat, nu câte semnalări. Semnalările
                // independente sunt un semnal mult mai puternic.
                TextColumn::make('distinct_reports')
                    ->label('Semnalări')
                    ->badge()
                    ->getStateUsing(fn (ContentReport $record) => ContentReport::open()
                        ->where('reportable_id', $record->reportable_id)
                        ->where('subject_type', $record->subject_type)
                        ->distinct('reported_by')
                        ->count('reported_by'))
                    ->color(fn ($state) => $state >= 3 ? 'danger' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Primită')
                    ->since()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stare')
                    ->options(['open' => 'Deschise', 'resolved' => 'Rezolvate', 'dismissed' => 'Respinse'])
                    ->default('open'),

                SelectFilter::make('subject_type')
                    ->label('Tip')
                    ->options(['avatar' => 'Imagine', 'username' => 'Nume public']),
            ])
            ->recordActions([
                Action::make('dismiss')
                    ->label('Nu e o problemă')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('gray')
                    ->visible(fn (ContentReport $r) => $r->status === 'open')
                    ->requiresConfirmation()
                    ->modalDescription('Semnalarea se închide, iar conținutul rămâne vizibil. Dacă imaginea a fost ascunsă automat, redevine vizibilă.')
                    ->action(function (ContentReport $record) {
                        self::closeAll($record, 'dismissed', 'niciuna');

                        // Ascunderea automată e o măsură temporară, nu un verdict:
                        // respingerea semnalării readuce imaginea în public.
                        $record->reportable?->forceFill([
                            'avatar_status' => 'approved',
                            'avatar_hidden_at' => null,
                        ])->save();

                        Notification::make()->title('Semnalare respinsă')->success()->send();
                    }),

                Action::make('remove_avatar')
                    ->label('Șterge imaginea')
                    ->icon('heroicon-o-photo')
                    ->color('warning')
                    ->visible(fn (ContentReport $r) => $r->subject_type === 'avatar'
                        && $r->status === 'open'
                        && filled($r->reportable?->avatar_path))
                    ->requiresConfirmation()
                    ->modalDescription('Fișierele se șterg definitiv. Elevul rămâne cu avatarul emoji și își poate încărca altă imagine.')
                    ->action(function (ContentReport $record) {
                        $user = $record->reportable;

                        if ($user instanceof User) {
                            app(ProcessAvatar::class)->delete($user);
                            $user->forceFill([
                                'avatar_path' => null,
                                'avatar_updated_at' => null,
                                'avatar_status' => 'approved',
                                'avatar_hidden_at' => null,
                            ])->save();
                        }

                        self::closeAll($record, 'resolved', 'avatar_sters');

                        Notification::make()->title('Imagine ștearsă')->success()->send();
                    }),

                Action::make('force_rename')
                    ->label('Cere alt nume')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->visible(fn (ContentReport $r) => $r->status === 'open')
                    ->requiresConfirmation()
                    ->modalDescription('La următoarea accesare, elevul va fi obligat să aleagă alt nume public. Contul și progresul rămân intacte.')
                    ->action(function (ContentReport $record) {
                        $record->reportable?->forceFill(['username_reset_required' => true])->save();

                        self::closeAll($record, 'resolved', 'redenumire_ceruta');

                        Notification::make()->title('Redenumire cerută')->success()->send();
                    }),
            ]);
    }

    /**
     * Închide TOATE semnalările deschise pe același subiect, nu doar cea apăsată.
     *
     * Altfel, un avatar raportat de cinci ori ar cere cinci apăsări identice, iar
     * coada ar arăta mereu mai plină decât e.
     */
    private static function closeAll(ContentReport $record, string $status, string $action): void
    {
        ContentReport::open()
            ->where('reportable_type', $record->reportable_type)
            ->where('reportable_id', $record->reportable_id)
            ->where('subject_type', $record->subject_type)
            ->update([
                'status' => $status,
                'action_taken' => $action,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
