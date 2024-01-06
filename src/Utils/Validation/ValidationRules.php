<?php

namespace App\Utils\Validation;

use Exception;
use RuntimeException;

abstract class ValidationRules
{

    /**
     * @throws Exception
     */
    public static function get(string $resource)
    {
        $class = __NAMESPACE__ . '\\Rules\\' . ucfirst($resource) . 'ValidationRules';

        if (!class_exists($class)) {
            throw new RuntimeException('Class "' . $class . '" not found.');
        }

        return $class::getValidationRules();
    }
}
