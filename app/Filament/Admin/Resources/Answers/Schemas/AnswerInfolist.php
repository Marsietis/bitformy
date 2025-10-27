<?php

namespace App\Filament\Admin\Resources\Answers\Schemas;

use App\Models\Answer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AnswerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('question.title')
                    ->label('Question'),
                TextEntry::make('form_id'),
                TextEntry::make('submission_id'),
                TextEntry::make('answer')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Answer $record): bool => $record->trashed()),
            ]);
    }
}
