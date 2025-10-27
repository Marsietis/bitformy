<?php

namespace App\Filament\Admin\Resources\Questions;

use App\Filament\Admin\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Admin\Resources\Questions\Pages\EditQuestion;
use App\Filament\Admin\Resources\Questions\Pages\ListQuestions;
use App\Filament\Admin\Resources\Questions\Pages\ViewQuestion;
use App\Filament\Admin\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Admin\Resources\Questions\Schemas\QuestionInfolist;
use App\Filament\Admin\Resources\Questions\Tables\QuestionsTable;
use App\Models\Question;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::QuestionMarkCircle;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|null|\UnitEnum $navigationGroup = 'Forms';

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'form.title'];
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
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'view' => ViewQuestion::route('/{record}'),
            'edit' => EditQuestion::route('/{record}/edit'),
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
