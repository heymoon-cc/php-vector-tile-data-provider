<?php

namespace HeyMoon\MVTTools\Helper;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
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
