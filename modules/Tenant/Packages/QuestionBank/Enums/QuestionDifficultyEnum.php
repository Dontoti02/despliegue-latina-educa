<?php

namespace Modules\Tenant\Packages\QuestionBank\Enums;

class QuestionDifficultyEnum
{
    const EASY = 'EASY';
    const MEDIUM = 'MEDIUM';
    const HARD = 'HARD';

    public static function all(): array
    {
        return [self::EASY, self::MEDIUM, self::HARD];
    }
}
