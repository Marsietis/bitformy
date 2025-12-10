<?php

namespace App\Enums;

enum QuestionType: string
{
    case TEXT = 'text';
    case CHOICE = 'choice';
    case RATING = 'rating';
    case DATE = 'date';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text Answer',
            self::CHOICE => 'Choice',
            self::RATING => 'Rating',
            self::DATE => 'Date',
        };
    }
}
