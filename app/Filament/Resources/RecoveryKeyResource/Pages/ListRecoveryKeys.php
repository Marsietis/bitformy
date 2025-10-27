<?php

namespace App\Filament\Resources\RecoveryKeyResource\Pages;

use App\Filament\Resources\RecoveryKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecoveryKeys extends ListRecords
{
    protected static string $resource = RecoveryKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
