<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
