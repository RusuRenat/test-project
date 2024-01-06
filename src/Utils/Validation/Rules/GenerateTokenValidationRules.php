<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;

class GenerateTokenValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'username' => [
            'label' => 'Username',
            'rules' => [ValidationType::REQUIRED]
        ],
        'accessToken' => [
            'label' => 'Access Token',
            'rules' => [ValidationType::REQUIRED]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
