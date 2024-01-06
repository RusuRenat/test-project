<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\Users\UsersRoles;
use App\Utils\Constants\Users\UsersStatus;
use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;


class UpdateUserProfileValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'id' => [
            'label' => 'User Profile',
            'rules' => [ValidationType::REQUIRED, ValidationType::INTEGER]
        ],
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
