<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;


class ForgotPasswordValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'email' => [
            'label' => 'Email',
            'rules' => [ValidationType::REQUIRED, ValidationType::EMAIL]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }
}
