<?php

namespace App\Tests\Utils;

class JWTParse {
    public static function parse(string $token) {
        $payload = explode(".", $token)[1];

        return json_decode(base64_decode($payload), true);
    }
}