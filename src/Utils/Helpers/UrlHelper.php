<?php

namespace App\Utils\Helpers;


class UrlHelper
{

    public static function isValid($url): bool
    {
        if (!$url) {
            return false;
        }

        $headers = get_headers($url);
        return (bool)stripos($headers[0], "200 OK");
    }

}