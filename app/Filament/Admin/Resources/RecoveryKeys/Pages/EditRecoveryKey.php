<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Pages;

use App\Filament\Admin\Resources\RecoveryKeys\RecoveryKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRecoveryKey extends EditRecord
{
    protected static string $resource = RecoveryKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
