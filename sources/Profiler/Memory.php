<?php

/**
 * @brief       Memory Active Record
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Profiler;

use IPS\Settings;
use IPS\Theme;
use IPS\storm\Profiler;
use UnexpectedValueException;

use function count;
use function defined;
use function floor;
use function header;
use function json_encode;
use function log;
use function memory_get_usage;
use function round;
use function time;

use function end;



if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _Memory
 *
 * @package IPS\storm\Profiler
 * @mixin Memory
 */
class Memory
{ 
    protected static $store = [];
    /**
     * start time
     *
     * @var null
     */
    protected $start = [];

    public function __construct()
    {
        $this->start = memory_get_usage();
    }

    /**
     * @throws UnexpectedValueException
     */
    public static function render()
    { 
        $total = static::total();
        $panel = '';

        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_memory',
            $total,
            null,//'storm_execution_panel',
            lang('storm_profiler_button_memory_total'),
            'microchip',
            '#0066ff',
            '#fff' 
        );

        return [
            'button' => $button,
            'panel' => $panel
        ]; 
    }

    /**
     * @return string
     */
    protected static function total(): string
    {
        return Profiler::formatBytes(memory_get_usage());
    }

    /**
     * @param     $size
     * @param int $precision
     *
     * @return string
     */


    public function endWithNoSuffix()
    {
        $end = memory_get_usage();
        $memEnd = $end - $this->start;

        return Profiler::formatBytes($memEnd);
    }

    public function end($key = null, $name = null): string
    {
        $end = memory_get_usage();
        $memEnd = $end - $this->start;
        $mem = Profiler::formatBytes($memEnd);

        if ($mem === 'NAN B') {
            $mem = '> 1 B';
        }

        if ($key !== null && Settings::i()->dtprofiler_enabled_memory_summary) {
            static::$store[] = [
                'name' => $name,
                'key'  => $key,
                'log'  => $mem,
                'time' => time(),
            ];
        }

        return $mem;
    }
}
