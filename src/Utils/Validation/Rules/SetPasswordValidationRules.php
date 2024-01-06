<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;


class SetPasswordValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'id' => [
            'label' => 'User',
            'rules' => [ValidationType::REQUIRED, ValidationType::INTEGER]
        ],
        'password' => [
            'label' => 'Password',
            'rules' => [ValidationType::REQUIRED, ValidationType::PASSWORD]
        ],
        'confirmPassword' => [
            'label' => 'Confirm password',
            'rules' => [ValidationType::REQUIRED, ValidationType::PASSWORD]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
