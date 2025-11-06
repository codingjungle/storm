<?php

namespace IPS;

use Exception;
use IPS\storm\Profiler\Debug;
use IPS\storm\Settings;

use function defined;

class Log extends \IPS\_Log
{
    protected static bool $skip = false;
    public static function log(Exception|string|array $message, string $category = null): ?Log
    {
        if (
            static::$skip === false &&
            Settings::i()->storm_profiler_debug_enabled === true &&
            $category !== 'request'
        ) {
            Debug::log($message, $category);
        }

        return parent::log($message, $category);
    }

    public static function debug(Exception|string $message, string $category = null, ?int $logLevel = null): ?Log
    {
        if (
            Settings::i()->storm_profiler_debug_enabled === true &&
            $category !== 'request' &&
            (defined('\IPS\DEBUG_LOG') && DEBUG_LOG)
        ) {
            Debug::log($message, $category, $logLevel);
            static::$skip = true;
        }

        $return = parent::debug($message, $category, $logLevel);
        static::$skip = false;

        return $return;
    }
}
