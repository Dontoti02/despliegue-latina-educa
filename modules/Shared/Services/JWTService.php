<?php

namespace Modules\Shared\Services;

use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Parser;

class JWTService
{

    protected static $JWT_SIGN = '49dbba5d-8dbe-4d8a-a7a3-699f51d3883d';

    # time in days
    protected static $EXPIRATION_TIME = 1;

    public static function sign($payload, $remember = false): string
    {
        $issuedAt = time();

        $days = self::$EXPIRATION_TIME;

        if ($remember) {
            $days = 7;
        }

        $expirationTime = $issuedAt + 60 * 60 * 24 * ($days);

        $key = new HmacKey(self::$JWT_SIGN);
        $signer = new HS256($key);
        $generator = new Generator($signer);

        $jwt = $generator->generate([
            ...$payload,
            'exp' => $expirationTime
        ]);

        return $jwt;
    }


    public static function valid(string $token)
    {
        try {
            $key = new HmacKey(self::$JWT_SIGN);
            $signer = new HS256($key);
            $parser = new Parser($signer);

            $claims = $parser->parse($token);

            return $claims;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
