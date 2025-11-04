<?php

/**
* @brief      Settings Class
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage storm
* @since 1.0.0
*/

namespace IPS\storm;

use IPS\Patterns\Singleton;
use UnderflowException;
use Throwable;

use function array_combine;
use function array_values;
use function is_array;
use function defined;
use function header;
use function json_decode;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * @mixin \IPS\_Settings
 */
class Settings extends \IPS\Settings
{
    public const ARRAYS_OR_STRINGS = [
    ];

    protected const ARRAYS = [

    ];

    protected const OBJECTS = [];

    protected const BOOLEANS = [
        'storm_profiler_enabled' => 1,
        'storm_profiler_js_enabled' => 1,
        'storm_profiler_js_vars_enabled' => 1,
        'storm_profiler_css_enabled' => 1,
        'storm_profiler_templates_enabled' => 1,
        'storm_profiler_execution_times_enabled' => 1,
        'storm_profiler_memory_tab_enabled' => 1,
        'storm_profiler_files_enabled' => 1,
        'storm_profiler_database_enabled' => 1,
        'storm_profiler_environment_enabled' => 1,
        'storm_profiler_debug_enabled' => 1,
        'storm_profiler_admin_enabled' => 1,
        'storm_proxy_mixin' => 1,
        'storm_devcenter_keep_case' => 1,
        'storm_proxy_do_non_owned' => 1,
        'storm_proxy_write_mixin' => 1,
        'storm_proxy_alt_templates' => 1,
        'storm_profiler_ajax_enabled' => 1
    ];

    protected const INTEGERS = [
    ];

    public const STRINGS = [
    ];

    public const MIXED = [
    ];

    protected static ?Singleton $instance = null;

    public function __get($key): mixed
    {
        try {
            $return = parent::__get($key);
            if (isset(static::STRINGS[$key])) {
                return $return;
            }
            if (isset(static::ARRAYS[$key])) {
                if (is_array($return)) {
                    return $return;
                }
                $return = json_decode($return, true) ?? $return;
                if (is_array($return) && static::ARRAYS[$key] === 2) {
                    return array_combine(array_values($return), array_values($return));
                }

                if (static::ARRAYS[$key] === 3) {
                    return (int)$return;
                }
                return $return;
            }

            if (isset(static::ARRAYS_OR_STRINGS[$key])) {
                if (is_array($return)) {
                    return $return;
                }
                $return = json_decode($return, true) ?? $return;
                if (is_array($return) && static::ARRAYS_OR_STRINGS[$key] === 2) {
                    return array_combine(array_values($return), array_values($return));
                }

                if (static::ARRAYS_OR_STRINGS[$key] === 3) {
                    return (int)$return;
                }

                //still here?
                return $return;
            }

            if (isset(static::INTEGERS[$key])) {
                return (int)$return;
            }

            if (isset(static::BOOLEANS[$key])) {
                return (bool)$return;
            }

            if (isset(static::OBJECTS[$key])) {
                $class = static::OBJECTS[$key];
                try {
                    return $class::load($class);
                } catch (UnderflowException $e) {
                }
            }

            return $return;
        } catch (Throwable $e) {
        }

        return null;
    }

    public function changeValues($newValues): void
    {
        $toSave = [];

        foreach ($newValues as $k => $v) {
            if (\is_array($v)) {
                $v = json_encode($v);
            }

            $toSave[$k] = $v;
        }

        parent::changeValues($toSave);
    }
}
