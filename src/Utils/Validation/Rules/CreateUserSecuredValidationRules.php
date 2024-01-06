<?php

namespace App\Utils\Validation\Rules;

use App\Utils\Constants\Status;
use App\Utils\Constants\Users\UsersRoles;
use App\Utils\Constants\Users\UsersStatus;
use App\Utils\Constants\ValidationType;
use App\Utils\Validation\ValidationRules;


class CreateUserSecuredValidationRules extends ValidationRules
{

    public static array $validationRules = [
        'email' => [
            'label' => 'Email',
            'rules' => [ValidationType::REQUIRED, ValidationType::EMAIL]
        ],
        /*'firstName' => [
            'label' => 'First name',
            'rules' => [ValidationType::REQUIRED, ValidationType::LENGTH],
            'extra' => ['min' => 3, 'max' => 50]
        ],
        'lastName' => [
            'label' => 'Last name',
            'rules' => [ValidationType::REQUIRED, ValidationType::LENGTH],
            'extra' => ['min' => 3, 'max' => 50]
        ],*/
        'password' => [
            'label' => 'Password',
            'rules' => [ValidationType::REQUIRED, ValidationType::PASSWORD]
        ],
        'confirmPassword' => [
            'label' => 'Password confirmation',
            'rules' => [ValidationType::REQUIRED, ValidationType::PASSWORD]
        ],
        'status' => [
            'label' => 'Status',
            'rules' => [ValidationType::CHOICE, ValidationType::REQUIRED],
            'extra' => Status::CASES
        ],
        'roles' => [
            'label' => 'Roles',
            'rules' => [ValidationType::CHOICE, ValidationType::REQUIRED],
            'extra' => UsersRoles::CASES
        ]
    ];

    public static function getValidationRules(): array
    {
        return self::$validationRules;
    }

}
