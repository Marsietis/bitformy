<?php

namespace App\Filament\Admin\Resources\RecoveryKeys;

use App\Filament\Admin\Resources\RecoveryKeys\Pages\CreateRecoveryKey;
use App\Filament\Admin\Resources\RecoveryKeys\Pages\EditRecoveryKey;
use App\Filament\Admin\Resources\RecoveryKeys\Pages\ListRecoveryKeys;
use App\Filament\Admin\Resources\RecoveryKeys\Pages\ViewRecoveryKey;
use App\Filament\Admin\Resources\RecoveryKeys\Schemas\RecoveryKeyForm;
use App\Filament\Admin\Resources\RecoveryKeys\Schemas\RecoveryKeyInfolist;
use App\Filament\Admin\Resources\RecoveryKeys\Tables\RecoveryKeysTable;
use App\Models\RecoveryKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecoveryKeyResource extends Resource
{
    protected static ?string $model = RecoveryKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Key;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RecoveryKeyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecoveryKeyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecoveryKeysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecoveryKeys::route('/'),
            'create' => CreateRecoveryKey::route('/create'),
            'view' => ViewRecoveryKey::route('/{record}'),
            'edit' => EditRecoveryKey::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
