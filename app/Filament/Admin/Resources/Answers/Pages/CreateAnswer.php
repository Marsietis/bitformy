<?php

namespace App\Filament\Admin\Resources\Answers\Pages;

use App\Filament\Admin\Resources\Answers\AnswerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnswer extends CreateRecord
{
    protected static string $resource = AnswerResource::class;
}
