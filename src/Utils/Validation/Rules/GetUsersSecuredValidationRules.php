<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;

class GetUsersSecuredValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'offset' => [
            'label' => 'Offset',
            'rules' => [ValidationType::INTEGER]
        ],
        'limit' => [
            'label' => 'Limit',
            'rules' => [ValidationType::INTEGER]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
