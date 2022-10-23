<?php

namespace HeyMoon\MVTTools\Helper;

class EncodingHelper
{
    public static function getOriginalOrGZIP(string $data): bool|string
    {
        set_error_handler(fn() => null);
        $result = gzdecode($data) ?: $data;
        restore_error_handler();
        return $result;
    }
}
