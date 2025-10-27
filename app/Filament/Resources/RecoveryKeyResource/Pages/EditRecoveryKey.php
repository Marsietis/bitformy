<?php

namespace App\Filament\Resources\RecoveryKeyResource\Pages;

use App\Filament\Resources\RecoveryKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecoveryKey extends EditRecord
{
    protected static string $resource = RecoveryKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
