<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\Status;
use App\Utils\Constants\Users\UsersRoles;
use App\Utils\Constants\Users\UsersStatus;
use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;


class CreateBookSecuredValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'title' => [
            'label' => 'Title',
            'rules' => [ValidationType::REQUIRED, ValidationType::LENGTH],
            'extra' => ['min' => 3, 'max' => 255]
        ],
        'price' => [
            'label' => 'Price',
            'rules' => [ValidationType::REQUIRED,ValidationType::NUMERIC]
        ],
        'author' => [
            'label' => 'Author',
            'rules' => [ValidationType::REQUIRED, ValidationType::LENGTH],
            'extra' => ['min' => 3, 'max' => 255]
        ],
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
