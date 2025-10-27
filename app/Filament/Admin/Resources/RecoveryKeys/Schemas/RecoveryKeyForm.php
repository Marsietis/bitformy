<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RecoveryKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('recovery_key')
                    ->required(),
            ]);
    }
}
