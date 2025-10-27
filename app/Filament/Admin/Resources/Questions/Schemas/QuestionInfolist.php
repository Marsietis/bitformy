<?php

namespace App\Filament\Admin\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('form.title')
                    ->label('Form'),
                TextEntry::make('title'),
                TextEntry::make('type'),
                TextEntry::make('options')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('required')
                    ->boolean(),
                TextEntry::make('order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Question $record): bool => $record->trashed()),
            ]);
    }
}
