<?php

namespace App\Service;

class RoundPrice {
    public static function roundPrice($price) {
        $result = explode(".", (string)$price);

        if (count($result) === 1) {
            $result[] = '00';
        } else if(strlen($result[1]) == 1) {
            $result[1] .= '0';
        } else if (strlen($result[1]) == 1) {
            $result[1] = mb_substr($result[1], 0, 2);
        }

        return implode(".", $result);
    }
}