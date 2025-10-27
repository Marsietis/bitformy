<?php

namespace App\Filament\Admin\Resources\Answers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('question_id')
                    ->relationship('question', 'title')
                    ->required(),
                TextInput::make('form_id')
                    ->required(),
                TextInput::make('submission_id')
                    ->required(),
                Textarea::make('answer')
                    ->columnSpanFull(),
            ]);
    }
}
