<?php

namespace App\Filament\Resources\UserModeration\Pages;

use App\Filament\Resources\UserModeration\UserModerationResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserModeration extends ListRecords
{
    protected static string $resource = UserModerationResource::class;

    public function getTabs(): array
    {
        return [
            'imagini' => Tab::make('Imagini')
                ->icon('heroicon-o-photo')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('avatar_status', 'pending')
                    ->whereNotNull('avatar_path'))
                ->badge(fn () => User::where('avatar_status', 'pending')->whereNotNull('avatar_path')->count())
                ->badgeColor('danger'),

            'nume' => Tab::make('Nume')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('username_status', 'pending'))
                ->badge(fn () => User::where('username_status', 'pending')->count())
                ->badgeColor('warning'),

            'respinse' => Tab::make('Respinse')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('username_status', 'rejected')),

            'toate' => Tab::make('Toate în așteptare')
                ->modifyQueryUsing(fn (Builder $query) => UserModerationResource::pendingQuery()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        // Imaginile primele: sunt singurul conținut pe care un filtru automat nu
        // îl poate evalua deloc.
        return User::where('avatar_status', 'pending')->whereNotNull('avatar_path')->exists()
            ? 'imagini'
            : 'nume';
    }
}
