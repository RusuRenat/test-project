<?php

namespace App\Utils\Helpers;


use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class DateTimeHelper
{

    /**
     * @throws Exception
     */
    public static function getDatesFromRange($start, $end, $format = 'Y-m-d'): array
    {

        $array = [];

        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }

}