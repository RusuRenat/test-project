<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;

class GetBookValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'id' => [
            'label' => 'Book',
            'rules' => [ValidationType::REQUIRED, ValidationType::INTEGER]
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
