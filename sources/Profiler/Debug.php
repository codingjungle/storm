<?php

#generator_token_imports#<?php
#generator_token_imports#<?php

/**
 * @brief       Debug Active Record
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Profiler;

use Exception;
use IPS\DateTime;
use IPS\Patterns\ActiveRecord;
use IPS\storm\Settings;
use IPS\Theme;
use IPS\storm\Editor;
use IPS\storm\Profiler;
use UnexpectedValueException;

use function count;
use function debug_backtrace;
use function defined;
use function get_class;
use function header;
use function htmlentities;
use function is_array;
use function json_decode;
use function json_encode;
use function lang;
use function md5;
use function method_exists;
use function nl2br;
use function rand;
use function time;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class Debug
 *
 * @package IPS\storm\Profiler
 * @mixin _Debug
 */
#[\AllowDynamicProperties]
class Debug extends ActiveRecord
{
    use \IPS\storm\Shared\ActiveRecord;

    const CRITICAL = 5;
    const ERROR = 4;
    const WARNING = 3;
    const INFO = 2;
    const DEBUG = 1;

    public static array $logLevels = [
        1 => 'debug',
        2 => 'info',
        3 => 'warning',
        4 => 'error',
        5 => 'critical',
    ];
    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static string $databasePrefix = 'debug_';

    /**
     * @brief   [ActiveRecord] ID Database Column
     */
    public static string $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Database table
     */
    public static ?string $databaseTable = 'storm_debug';

    /**
     * @brief   Bitwise keys
     */
    public static array $bitOptions = [
        'bitoptions' => [
            'bitoptions' => [],
        ],
    ];

    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static array $multitons = [];

    /**
     * adds a debug message to the log
     *
     * @param $key
     * @param $message
     */
    public static function log(Exception|string|array $message, ?string $category = null, ?int $level = null): void
    {
        if (Settings::i()->storm_profiler_debug_enabled === true) {
            if ($level === null) {
                $level = static::INFO;
            }
            $debug = new static();
            $debug->category = $category;
            $bt = [];

            if ($message instanceof Exception) {
                $data['class'] = get_class($message);
                $data['ecode'] = $message->getCode();

                $bt[] = [
                    'file' => $message->getFile(),
                    'line' => $message->getLine(),
                    'function' => '',
                    'class' => '',
                    'type' => ''
                ];
                if (method_exists($message, 'extraLogData')) {
                    $data['message'] = $message->extraLogData() . "\n" . $message->getMessage();
                } else {
                    $data['message'] = $message->getMessage();
                }

                $type = 'exception';
                $message = json_encode($data);
            } elseif (is_array($message)) {
                $message = json_encode($message);
                $type = 'array';
            } else {
                $type = 'string';
            }
            $bt = array_merge($bt, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

            $debug->bt = json_encode($bt);
            $debug->type = $type;
            $debug->log = $message;
            $debug->date = time();
            $debug->save();
        }
    }

    /**
     * @return null
     * @throws UnexpectedValueException
     */
    public static function render(): array
    {
        $iterators = static::all([
            'order' => 'debug_id DESC',
            'limit' => [0, 100],
        ]);
        $list = [];
        $time = 0;

        /* @var Debug $obj */
        foreach ($iterators as $obj) {
            $list[] = Theme::i()->getTemplate('profiler', 'storm', 'global')->debugRow($obj);
            if ($obj->date > $time) {
                $time = $obj->date;
            }
        }

        $count = count($list) ?: 0;

        $button =  Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_debug',
            '',
            'storm_profiler_debug_panel',
            lang('storm_profiler_button_debug'),
            'bug',
            '#ff3300',
            '#fff',
            $count,
            false,
            $time
        );
        $panel = Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->debugPanel(lang('storm_profiler_title_debug', false, ['sprintf' => [$count]]), $list, $time);

        return [
            'button' => $button,
            'panel' => $panel
        ];
    }

    public static function popup(): string
    {
        $iterators = static::all([
            'order' => 'debug_id DESC',
            'limit' => [0, 100],
        ]);
        $list = [];
        $last = 0;

        /* @var Debug $obj */
        foreach ($iterators as $obj) {
            $list[] = Theme::i()->getTemplate('profiler', 'storm', 'global')->debugRow($obj);
            if ($obj->date > $last) {
                $last = $obj->date;
            }
        }

        return Theme::i()->getTemplate('profiler', 'storm', 'global')
            ->debugPanel(lang('storm_profiler_title_debug', false), $list, $last);
    }

    /**
     * the body of the message
     *
     * @throws UnexpectedValueException
     */
    public function get_log(): string
    {
        $list = [];
        if ($this->type === 'exception' || $this->type === 'array') {
            $list = json_decode($this->_data['log'], true);
            unset($list['backtrace']);
        } else {
            $list = $this->_data['log'];
        }

        return Profiler::dump($list);
    }

    public function get_logRaw(): string
    {
        if ($this->type === 'exception' || $this->type === 'array') {
            $list = json_decode($this->_data['log'], true);
            $list = var_export($list, true);
        } else {
            $list = $this->_data['log'];
        }
        $list = str_replace('"', '', $list);
        return $list;
    }

    public function get_backtrace(): array
    {
        $btOG = json_decode($this->_data['bt'], true);
        $i = 1;
        $bt = [];
        foreach ($btOG as $k => $v) {
            $url = null;
            if (isset($v['file'])) {
                $url =  Editor::i()->replace($v['file'], $v['line']);
            }
            $class = $v['class'] ?? '';
            $check = $class.'::'.$v['function'];
//            if(str_contains($check, 'storm\\Profiler\\Debug::log') || str_contains($check, 'IPS\\Log::log') || str_contains($check, 'IPS\\Log::debug'))
//            {
//                continue;
//            }
            $bt[$i] = [
                'url' => $url,
                'class' => $class,
                'function' => $v['function'] ?? '',
                'type' => $v['type'] ?? '::',
                'file' => $v['file'] ?? 'NULL',
                'line' => $v['line'] ?? '',
            ];
            $i++;
        }
        return $bt;
    }

    public function get_btRaw(): string
    {
        $data = json_decode($this->_data['bt'], true);
        $return = '';
        $i = 1;
        foreach ($data as $bt) {
            $class = $bt['class'] ?? null;
            $type = $bt['type'] ?? '::';
            if ($class === null) {
                $class = '';
                $type = '';
            }
            $function = $bt['function'] ?? '';
            $return .= $i . ': ' . $class . $type . $function . "()\n";
            $file = $bt['file'] ?? null;
            $line = $bt['line'] ?? 0;
            $return .= $file . '::' . $line . "\n\n";

            $i++;
        }

        return $return;
    }

    public function get_messages()
    {
        return json_decode($this->log, true);
    }

    /**
     * @return string
     */
    public function get_name(): string
    {
        $id = $this->_data['id'] ?? md5(rand(1, 1000000));
        return '#' . $id . ' ' . $this->_data['key'];
    }

    public function url()
    {
        if ($this->path !== null) {
            return (new Editor())->replace($this->path, $this->line);
        }
    }

    public function get_time()
    {
        return DateTime::ts($this->date)->format('m/d/y - h:ia');
    }

    public function get_level(): string
    {
        return mb_ucfirst(static::$logLevels[$this->_data['level']]);
    }

}
