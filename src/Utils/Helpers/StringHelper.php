<?php

namespace App\Utils\Helpers;

use Exception;
use ReflectionNamedType;
use ReflectionUnionType;

class StringHelper
{

    /**
     * @throws Exception
     */
    public static function generateRandomString(int $length = 8): string
    {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * @throws Exception
     */
    public static function generateUuid(): string
    {

        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            random_int(0, 0xffff), random_int(0, 0xffff),

            // 16 bits for "time_mid"
            random_int(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    public static function splitCamelCase(string $input): array
    {
        return preg_split('/(^[^A-Z]+|[A-Z][^A-Z]+)/', $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }

    public static function stripTags($text)
    {
        $text = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $text);
        $text = strip_tags($text);
        $text = trim($text);
        $text = str_replace(["\n", "\t", "\r"], ' ', $text);

        return $text;
    }

    public static function truncate($text, $length)
    {
        $text = self::stripTags($text);

        $length = abs((int)$length);
        if (strlen($text) > $length) {
            $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1 ...', strip_tags($text));
        }

        return ($text);
    }

    /**
     * @throws Exception
     */
    public static function generateNumericCode(int $length = 8): string
    {

        $characters = '0123456789';
        $randomString = $characters[random_int(1, strlen($characters) - 1)];
        for ($i = 1; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    public static function underscoreToCamelCase($string, $withFk = false, $capitalizeFirstCharacter = false): array|string
    {

        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        if ($withFk && str_ends_with($str, 'Id')) {
            $str = substr($str, 0, -2) . 'sId';
        }

        return $str;
    }

    public static function camelCaseToUnderscore($string): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public static function snakeCaseToCamelCase(string $string, bool $capitalizeFirstCharacter = false): string
    {

        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    public static function checkAllowedType(ReflectionNamedType|ReflectionUnionType $reflectionTypes): bool
    {
        $allowedTypes = ['int', 'string', 'bool', 'float'];

        if (is_a($reflectionTypes, ReflectionUnionType::class)) {
            foreach ($reflectionTypes->getTypes() as $reflectionType) {
                if (in_array($reflectionType->getName(), $allowedTypes, true)) {
                    return true;
                }
            }
        } else if (in_array($reflectionTypes->getName(), $allowedTypes)) {
            return true;
        }

        return false;
    }
}