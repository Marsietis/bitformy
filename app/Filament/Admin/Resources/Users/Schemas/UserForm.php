<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password_validator')
                    ->password()
                    ->required(),
                TextInput::make('salt')
                    ->required(),
                Textarea::make('private_key')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('public_key')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
