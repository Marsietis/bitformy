<?php

namespace App\Filament\Resources\RecoveryKeyResource\Pages;

use App\Filament\Resources\RecoveryKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecoveryKey extends CreateRecord
{
    protected static string $resource = RecoveryKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
