<?php

namespace App\Filament\Resources\UserModeration;

use App\Filament\Resources\UserModeration\Pages\ListUserModeration;
use App\Filament\Resources\UserModeration\Tables\UserModerationTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserModerationResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $modelLabel = 'cont';

    protected static ?string $pluralModelLabel = 'aprobări';

    protected static ?string $navigationLabel = 'Aprobări';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'aprobari';

    public static function table(Table $table): Table
    {
        return UserModerationTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListUserModeration::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::pendingQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Conturi cu ceva de aprobat: numele sau o imagine încărcată. */
    public static function pendingQuery(): Builder
    {
        return User::query()->where(fn (Builder $q) => $q
            ->where('username_status', 'pending')
            ->orWhere(fn (Builder $a) => $a
                ->where('avatar_status', 'pending')
                ->whereNotNull('avatar_path')));
    }
}
