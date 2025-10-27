<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Pages;

use App\Filament\Admin\Resources\RecoveryKeys\RecoveryKeyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRecoveryKey extends ViewRecord
{
    protected static string $resource = RecoveryKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
