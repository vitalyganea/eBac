<?php

namespace App\Filament\Resources\ContentReports;

use App\Filament\Resources\ContentReports\Pages\ListContentReports;
use App\Filament\Resources\ContentReports\Tables\ContentReportsTable;
use App\Models\ContentReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentReportResource extends Resource
{
    protected static ?string $model = ContentReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $modelLabel = 'semnalare';

    protected static ?string $pluralModelLabel = 'moderare';

    protected static ?string $navigationLabel = 'Moderare';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return ContentReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListContentReports::route('/')];
    }

    /** Semnalările nu se creează din panou — vin de la utilizatori. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $deschise = ContentReport::where('status', 'open')->count();

        return $deschise > 0 ? (string) $deschise : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['reportable', 'reporter']);
    }
}
