<?php

namespace IPS\storm\modules\front\profiler;

use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use UnderflowException;

use function defined;
use function json_encode;

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
        $date = (int) Request::i()->date;
        $all = \IPS\storm\Profiler\Debug::all(
            [
                'where' => ['debug_date > ?', $date],
                'order' => 'debug_id ASC'
            ]
        );
        $logs = [];
        $send = ['error' => 1];
        $count = 0;
        foreach ($all as $log) {
            $logs[] = Theme::i()->getTemplate('profiler', 'storm', 'global')->debugRow($log);
            $count++;
        }

        if (empty($logs) === false) {
            $send = ['error' => 0, 'logs' => $logs, 'count' => $count];
        }

        Output::i()->json($send);
    }
}
