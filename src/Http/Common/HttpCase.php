<?php

namespace Swilen\Http\Common;

/**
 * @internal
 * The packages is internal, provide helpers functions
 */
class HttpCase
{
    /**
     * Normalize $_SERVER['HTTP_X_HEADER'] to 'X-Header'.
     */
    public static function toNormalizeHttp(string $token): string
    {
        return self::normalize(substr($token, 5), '_');
    }

    /**
     * Normalize 'content-type' or 'CONTENT_TYPE' to 'Content-Type'.
     */
    public static function toNormalize(string $token): string
    {
        return self::normalize($token, ['-', '_']);
    }

    private static function normalize(string $str, string|array $delimiters): string
    {
        $len = strlen($str);
        if ($len === 0) return '';

        $upper = true;

        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];

            if ($char === '_' || $char === '-') {
                $str[$i] = '-';
                $upper = true;
                continue;
            }

            $ascii = ord($char);

            if ($upper) {
                if ($ascii >= 97 && $ascii <= 122) {
                    $str[$i] = chr($ascii - 32);
                }
                $upper = false;
            } else {
                if ($ascii >= 65 && $ascii <= 90) {
                    $str[$i] = chr($ascii + 32);
                }
            }
        }

        return $str;
    }

    public static function uppercase(string $str): string
    {
        return \strtoupper($str);
    }

    public static function lowercase(string $str): string
    {
        return \strtolower($str);
    }
}
