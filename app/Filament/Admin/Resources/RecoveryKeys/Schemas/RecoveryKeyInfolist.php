<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Schemas;

use App\Models\RecoveryKey;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RecoveryKeyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('recovery_key'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (RecoveryKey $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
