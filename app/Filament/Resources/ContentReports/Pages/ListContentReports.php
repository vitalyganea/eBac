<?php

namespace App\Filament\Resources\ContentReports\Pages;

use App\Filament\Resources\ContentReports\ContentReportResource;
use App\Models\ContentReport;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListContentReports extends ListRecords
{
    protected static string $resource = ContentReportResource::class;

    /**
     * Ordinea tab-urilor urmează urgența, nu alfabetul.
     *
     * Imaginile ascunse automat sunt primele: acolo un elev nevinovat poate fi
     * afectat de o campanie de raportări, iar decizia umană trebuie să vină
     * repede în ambele direcții.
     */
    public function getTabs(): array
    {
        return [
            'ascunse' => Tab::make('Ascunse automat')
                ->icon('heroicon-o-eye-slash')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'open')
                    ->whereHasMorph('reportable', [User::class],
                        fn ($u) => $u->whereNotNull('avatar_hidden_at')))
                ->badgeColor('danger'),

            'deschise' => Tab::make('Deschise')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'open'))
                ->badge(fn () => ContentReport::where('status', 'open')->count())
                ->badgeColor('warning'),

            'rezolvate' => Tab::make('Rezolvate')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', ['resolved', 'dismissed'])),

            'toate' => Tab::make('Toate'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'deschise';
    }
}
