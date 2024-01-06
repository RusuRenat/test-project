<?php

namespace App\Utils\Helpers;


use RuntimeException;

class FileHelper
{

    public static function makeDir($dirName, $permission = 0777): void
    {
        if (!empty($dirName) && !is_dir($dirName) && !mkdir($dirName, $permission, true) && !is_dir($dirName)) {
            throw new RuntimeException(__FILE__ . ' | ' . __LINE__ . ' | ' . $dirName);
        }
    }

}