<?php

namespace App\Enums;

enum QuestionType: string
{
    case TEXT = 'text';
    case CHOICE = 'choice';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text Answer',
            self::CHOICE => 'Choice',
        };
    }
}
