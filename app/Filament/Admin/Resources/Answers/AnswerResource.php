<?php

namespace App\Filament\Admin\Resources\Answers;

use App\Filament\Admin\Resources\Answers\Pages\CreateAnswer;
use App\Filament\Admin\Resources\Answers\Pages\EditAnswer;
use App\Filament\Admin\Resources\Answers\Pages\ListAnswers;
use App\Filament\Admin\Resources\Answers\Pages\ViewAnswer;
use App\Filament\Admin\Resources\Answers\Schemas\AnswerForm;
use App\Filament\Admin\Resources\Answers\Schemas\AnswerInfolist;
use App\Filament\Admin\Resources\Answers\Tables\AnswersTable;
use App\Models\Answer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AnswerResource extends Resource
{
    protected static ?string $model = Answer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::ShieldCheck;

    protected static ?string $recordTitleAttribute = 'Answers';

    protected static ?int $navigationSort = 4;

    protected static string|null|\UnitEnum $navigationGroup = 'Forms';


    public static function form(Schema $schema): Schema
    {
        return AnswerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnswerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnswersTable::configure($table);
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
            'index' => ListAnswers::route('/'),
            'create' => CreateAnswer::route('/create'),
            'view' => ViewAnswer::route('/{record}'),
            'edit' => EditAnswer::route('/{record}/edit'),
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
