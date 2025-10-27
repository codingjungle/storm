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
use function md5;
use function method_exists;
use function nl2br;
use function rand;
use function time;

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
    public static function log($message, $key = null)
    {
        if (Settings::i()->storm_profiler_debug_enabled === true) {
        //        if (!Settings::i()->dtprofiler_enable_debug) {
        //            Log::debug($message, $key);
        //            return;
        //        }
        //        if (!\IPS\QUERY_LOG) {
        //            Log::debug($message, $key);
        //            return;
        //        }

            $debug = new static();
        // $prev = null;
        // $next = null;
        // foreach ($bt as $b) {
        //     $file = str_replace(['/', '.'], '', $b['file']);
        //     if (mb_substr($file, -16) === 'ProfilerDebugphp') {
        //         continue;
        //     }

        //     if ($prev === null) {
        //         $prev = $b;
        //         continue;
        //     }

        //     if ($prev !== null && $next === null) {
        //         $next = $b;
        //         break;
        //     }
        // }
        // if ($key === null) {
        //     $next = $next ?? $prev;
        //     $key = $next['function'];
        //     if (!$key) {
        //         $file = new SplFileInfo($next['file']);
        //         $key = $file->getFilename();
        //     }
        // }
            $debug->key = $key;
            $debug->bt = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        // $debug->path = $prev['file'];
        // $debug->line = $prev['line'];
            if ($message instanceof Exception) {
                $data['class'] = get_class($message);
                $data['ecode'] = $message->getCode();

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

            $debug->type = $type;
            $debug->log = $message;
            $debug->date = time();

        // if(defined('DT_NODE') && DT_NODE){
        //     try {
        //         $return = [
        //             'count' => 1,
        //             'to' => 'debug',
        //             'loc' => \IPS\SUITE_UNIQUE_KEY,
        //             'items' => Theme::i()->getTemplate('generic', 'storm', 'front')->li($debug->body())
        //         ];
        //         Sockets::i()->post($return);
        //     }catch(\Exception | Throwable $e){
        //         $debug->save();
        //     }
        // }
        // else {

            $debug->save();
        // }
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
        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
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
            $return .= $bt['file'] . '::' . $bt['line'] . "\n\n";

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
        return DateTime::ts($this->date)->format('m/d/y-h:ia');
    }
}
