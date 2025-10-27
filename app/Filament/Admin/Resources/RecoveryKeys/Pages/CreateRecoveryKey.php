<?php

namespace App\Filament\Admin\Resources\RecoveryKeys\Pages;

use App\Filament\Admin\Resources\RecoveryKeys\RecoveryKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecoveryKey extends CreateRecord
{
    protected static string $resource = RecoveryKeyResource::class;
}
