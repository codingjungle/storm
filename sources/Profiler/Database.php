<?php

/**
 * @brief       Database Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Profiler;

use IPS\Db;
use IPS\Patterns\Singleton;
use IPS\storm\Editor;
use IPS\Theme;
use UnexpectedValueException;

use function count;
use function defined;
use function header;
use function round;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Database extends Singleton
{
    public static array $slowest = [];

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child class.
     */
    protected static ?Singleton $instance = null;

    /**
     * query store
     *
     * @var array
     */
    protected $dbQueries = [];

    /**
     * _Database constructor.
     */
    public function __construct()
    {
        $this->dbQueries = Db::i()->log;
    }

    /**
     * builds the database button
     *
     * @return mixed
     * @throws UnexpectedValueException
     */
    public function render()
    {
        $store = Db::i()->log;
        $newStore = [];
        foreach ($store as $key => $data) {
            $bt = [];
            $key = randomString(6);
            if (isset($data['backtrace'])) {
                $i = 1;
                foreach ($data['backtrace'] as $k => $v) {
                    $url = null;
                    if (isset($v['file'])) {
                        $url = Editor::i()->replace($v['file'], $v['line']);
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
            }

            $time = null;
            if (isset($data['time'])) {
                $time = round($data['time'], 4);
                if (empty(static::$slowest) || $time > static::$slowest['time']) {
                    static::$slowest = [
                        'query' => $data['query'],
                        'server' => $data['server'],
                        'time' => $time,
                        'mem' => $data['mem'] ?? null,
                        'bt' => $bt,
                        'key' => $key
                    ];
                }
            }

            $newStore[$key] = [
                'query' => $data['query'],
                'server' => $data['server'],
                'time' => $time,
                'mem' => $data['mem'] ?? null,
                'bt' => $bt,
                'key' => $key
            ];
        }
        $count = count($newStore);
        $button = Theme::i()->getTemplate('profiler', 'storm', 'global')->buttons(
            'storm_profiler_database',
            '',
            'storm_databases_panel', //'storm_execution_panel',
            lang('storm_profiler_button_database'),
            'database',
            '#006666',
            '#fff',
            $count
        );
        $panel = Theme::i()->getTemplate('profiler', 'storm', 'global')->databasePanel(
            $newStore,
            'Database Queries (' . $count . ')'
        );

        return [
            'button' => $button,
            'panel' => $panel
        ];
    }
}
