<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;

class DeleteUserProfileValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'id' => [
            'label' => 'User',
            'rules' => [ValidationType::REQUIRED, ValidationType::INTEGER]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
