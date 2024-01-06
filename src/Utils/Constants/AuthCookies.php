<?php

namespace App\Utils\Constants;


use ReflectionClass;

class AuthCookies
{

    public const BEARER = "BEARER";

    public static function getConstants(): array
    {
        $oClass = new ReflectionClass(__CLASS__);

        return array_values($oClass->getConstants());
    }

}
