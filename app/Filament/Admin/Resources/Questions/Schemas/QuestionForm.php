<?php

namespace App\Filament\Admin\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_id')
                    ->relationship('form', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                Textarea::make('options')
                    ->columnSpanFull(),
                Toggle::make('allow_multiple')->required(),
                Toggle::make('required')
                    ->required(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
