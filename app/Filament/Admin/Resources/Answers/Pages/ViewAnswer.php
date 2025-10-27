<?php

namespace App\Filament\Admin\Resources\Answers\Pages;

use App\Filament\Admin\Resources\Answers\AnswerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAnswer extends ViewRecord
{
    protected static string $resource = AnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
