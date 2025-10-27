<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Pages;

use App\Filament\Admin\Resources\RecoveryKeys\RecoveryKeyResource;
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
