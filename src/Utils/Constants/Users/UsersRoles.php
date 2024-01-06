<?php

namespace App\Utils\Constants\Users;

use ReflectionClass;

class UsersRoles
{
    public const CASES = [self::SUPER_ADMIN, self::ADMIN, self::USER, self::EMPLOYEE, self::TECH_LEAD];
    public const SUPER_ADMIN = "SUPER_ADMIN";
    public const ADMIN = "ADMIN";
    public const TECH_LEAD = "TECH_LEAD";
    public const EMPLOYEE = "EMPLOYEE";
    public const USER = "USER";

    public static function getConstants(): array
    {
        $oClass = new ReflectionClass(__CLASS__);

        return array_values($oClass->getConstants());
    }
}
