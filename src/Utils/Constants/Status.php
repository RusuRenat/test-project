<?php

namespace App\Utils\Constants;

use ReflectionClass;

class Status
{
    public const CASES = [self::PENDING, self::INACTIVE, self::ACTIVE, self::ARCHIVED, self::DELETED];
    public const INACTIVE = 0;
    public const ACTIVE = 1;
    public const PENDING = 2;
    public const DELETED = 3;
    public const ARCHIVED = 4;

    public static function getConstants(): array
    {
        $oClass = new ReflectionClass(__CLASS__);

        return array_values($oClass->getConstants());
    }
}