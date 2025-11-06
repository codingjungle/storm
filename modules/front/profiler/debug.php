<?php

namespace IPS\storm\modules\front\profiler;

use IPS\Db;
use IPS\dinit;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\storm\Head;
use IPS\storm\Settings;
use IPS\storm\Tpl;
use IPS\Theme;
use Throwable;
use UnderflowException;

use function defined;
use function ini_get;
use function json_encode;
use function time;

use const JSON_PRETTY_PRINT;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * debug
 */
class debug extends Controller
{
    /**
     * Execute
     *
     * @return  void
     */
    public function execute(): void
    {

        parent::execute();
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage(): void
    {
        // This is the default method if no 'do' parameter is specified
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
    protected function check()
    {
        $date = (int) Request::i()->date;
        $all = \IPS\storm\Profiler\Debug::all(['where' => ['debug_date > ?', $date]], true);

        try {
            $last = Db::i()->select('*', 'storm_debug', ['debug_date > ?', $date], 'debug_id DESC', 1)->first();
            $date = $last['debug_date'];
        } catch (UnderflowException) {
        }

        Output::i()->sendOutput(
            json_encode(['count' => $all, 'date' => $date]),
            200,
            'application/json',
            Output::i()->httpHeaders
        );
    }

    protected function logs()
    {
        $max = (ini_get('max_execution_time') / 2) - 5;
        $time = time();
        $since = Request::i()->last ?: 0;
        while (true) {
            $ct = time() - $time;
            if ($ct >= $max) {
                Output::i()->json(['error' => 1]);
            }

            $config =
                [
                    'where' => ['debug_date > ?', $since],
                    'order' => 'debug_id ASC'
                ];

            $debug = \IPS\storm\Profiler\Debug::all($config, true);
            if ($debug !== 0) {
                $all = \IPS\storm\Profiler\Debug::all($config);
                $logs = [];
                $send = ['error' => 1];
                $last = 0;
                /* @var \IPS\storm\Profiler\Debug $log */
                foreach ($all as $log) {
                    $logs[] = Theme::i()->getTemplate('profiler', 'storm', 'global')->debugRow($log);
                    if ($log->date > $last) {
                        $last = $log->date;
                    }
                }

                if (empty($logs) === false) {
                    $send = [
                        'error' => 0,
                        'logs' => $logs,
                        'last' => $last
                    ];
                    Output::i()->json($send);
                }
            } else {
                sleep(1);
                continue;
            }
        }
    }

    protected function delete(): void
    {
        $id = (int) Request::i()->id;
        $msg = 'Debug log deleted';
        $error = 0;
        try {
            Db::i()->delete('storm_debug', ['debug_id=?', $id]);
        } catch (Throwable $e) {
            $error = 1;
            $msg = $e->getMessage();
        }

        Output::i()->json(['error' => $error, 'msg' => $msg]);
    }

    protected function deleteAll(): void
    {
        $msg = 'All debug logs deleted';
        $error = 0;
        try {
            Db::i()->delete('storm_debug');
        } catch (Throwable $e) {
            $error = 1;
            $msg = $e->getMessage();
        }

        Output::i()->json(['error' => $error, 'msg' => $msg]);
    }

    protected function popup(): void
    {
        $image = \IPS\Theme::i()->resource('bug.png', 'storm', 'global', false);
        Dispatcher\Front::i()->init();
        Head::i()->css(['global_popup']);
        Head::i()->jsVars(['debugLogIcon' => (string) $image]);
        $output = Tpl::get('popup.storm.global')->popup(\IPS\storm\Profiler\Debug::popup(), 'Storm Debug Logs');
        Output::i()->sendOutput($output);
    }

    protected function manifest(): void
    {
        $image192 = (string)\IPS\Theme::i()->resource('bug192.png', 'storm', 'global', false);
        $image512 = (string)\IPS\Theme::i()->resource('bug512.png', 'storm', 'global', false);

        $image192 = str_replace(Url::baseUrl(), '', $image192);
        $image512 = str_replace(Url::baseUrl(), '', $image512);
        if (str_starts_with($image512, '/') === false) {
            $image512 = '/' . $image512;
        }

        if (str_starts_with($image192, '/') === false) {
            $image192 = '/' . $image192;
        }
        $manifest = [
            'shortname' => 'SDL',
            'name' => 'Storm Debug Logs',
            'icons' => [
                [
                    'src' => $image192,
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => $image512,
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ],
            'start_url' => (string) Url::internal('app=storm&module=profiler&controller=debug&do=popup', 'front'),
            'display' => 'standalone',
            'theme_color' => '#000000',
            'background_color' => '#ffffff'
        ];

        Output::i()->sendOutput(
            json_encode($manifest, JSON_PRETTY_PRINT),
            200,
            'application/manifest+json',
            Output::i()->httpHeaders
        );
        //Output::i()->json($manifest);
    }

    protected function app(): void
    {
        $hide = (int) Request::i()->hide;
        if ($hide === 2) {
            $hide = 0;
        }
        Settings::i()->changeValues(['storm_profiler_show_app_button' => $hide]);
    }
}
