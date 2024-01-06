<?php

namespace App\Utils\Helpers;

class SlugHelper
{

    public static function generate(string $string, string $space = "-"): string
    {

        if (function_exists('iconv')) {
            $inChartset = 'UTF-8';
            $outChartset = 'ASCII';
            if (preg_match('/[А-Яа-яЁё]/u', $string)) {
                $outChartset = $inChartset = 'CP1251';
            }
            $string = @iconv($inChartset, $outChartset . '//TRANSLIT', $string);
        }

        $string = preg_replace("/\w[^a-zA-Z0-9А-Яа-яЁё -]/", "", $string);
        $string = strtolower($string);
        $string = mb_strtolower($string);
        $transliteratorToASCII = \Transliterator::create('Latin-ASCII');
        $string = $transliteratorToASCII->transliterate(\Transliterator::create('Any-Latin')->transliterate($string));

        return str_replace(" ", $space, $string);
    }

    public static function validate(string $str): bool|int
    {
        return preg_match('/^[a-z0-9]+(-?[a-z0-9]+)*$/i', $str);
    }

}