<?php

namespace App\Filament\Resources\UserModeration\Tables;

use App\Domain\Users\ProcessAvatar;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/**
 * Coada de aprobare pentru conținutul public ales de utilizatori.
 *
 * Spre deosebire de coada de semnalări, aici nu s-a plâns nimeni — pur și simplu
 * nimic ales de utilizator nu ajunge public înainte să-l vadă un om.
 *
 * Costul operațional e real și trebuie asumat: fiecare înscriere produce un
 * element în coadă. Acțiunea în masă există exact pentru asta — marea majoritate
 * a numelor sunt evident inofensive și se aprobă în grup.
 */
class UserModerationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Imagine')
                    ->circular()
                    ->getStateUsing(fn (User $r) => $r->avatar_path ? $r->avatar_path.'-64.webp' : null)
                    ->visibility('public'),

                TextColumn::make('username')
                    ->label('Nume ales')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $r) => 'public momentan: '.$r->public_alias),

                TextColumn::make('username_status')
                    ->label('Nume')
                    ->badge()
                    ->color(fn (string $s) => match ($s) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('avatar_status')
                    ->label('Imagine')
                    ->badge()
                    ->formatStateUsing(fn (User $r) => blank($r->avatar_path) ? '—' : $r->avatar_status)
                    ->color(fn (User $r) => match (true) {
                        blank($r->avatar_path) => 'gray',
                        $r->avatar_status === 'approved' => 'success',
                        default => 'warning',
                    }),

                TextColumn::make('avatar_hidden_at')
                    ->label('Motiv')
                    ->formatStateUsing(fn ($state) => $state ? 'retrimisă după semnalări' : 'încărcare nouă')
                    ->color(fn ($state) => $state ? 'danger' : 'gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Înscris')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('username_status')
                    ->label('Stare nume')
                    ->options(['pending' => 'În așteptare', 'approved' => 'Aprobat', 'rejected' => 'Respins']),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobă')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $r) => $r->username_status === 'pending' || $r->avatarIsPending())
                    ->action(function (User $record) {
                        self::approve($record);

                        Notification::make()->title('Aprobat')->success()->send();
                    }),

                Action::make('reject_username')
                    ->label('Respinge numele')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->visible(fn (User $r) => $r->username_status === 'pending')
                    ->requiresConfirmation()
                    ->modalDescription('Elevul va fi obligat să aleagă alt nume. Contul și progresul rămân intacte, iar între timp apare sub pseudonim.')
                    ->action(function (User $record) {
                        $record->forceFill([
                            'username_status' => 'rejected',
                            'username_reset_required' => true,
                            'moderated_at' => now(),
                        ])->save();

                        Notification::make()->title('Nume respins')->warning()->send();
                    }),

                Action::make('reject_avatar')
                    ->label('Respinge imaginea')
                    ->icon('heroicon-o-photo')
                    ->color('danger')
                    ->visible(fn (User $r) => $r->avatarIsPending())
                    ->requiresConfirmation()
                    ->modalDescription('Fișierele se șterg definitiv. Elevul rămâne cu avatarul emoji și poate încărca altceva.')
                    ->action(function (User $record) {
                        app(ProcessAvatar::class)->delete($record);

                        $record->forceFill([
                            'avatar_path' => null,
                            'avatar_updated_at' => null,
                            'avatar_status' => 'approved',
                            'avatar_hidden_at' => null,
                            'moderated_at' => now(),
                        ])->save();

                        Notification::make()->title('Imagine respinsă')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Fără asta, coada devine impracticabilă: marea majoritate a
                    // numelor sunt evident inofensive.
                    BulkAction::make('approve_all')
                        ->label('Aprobă selecția')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (User $u) => self::approve($u));

                            Notification::make()
                                ->title("{$records->count()} conturi aprobate")
                                ->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    private static function approve(User $user): void
    {
        $user->forceFill([
            'username_status' => 'approved',
            'avatar_status' => 'approved',
            'avatar_hidden_at' => null,
            'moderated_at' => now(),
        ])->save();
    }
}
