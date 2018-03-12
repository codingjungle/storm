<?php
require_once str_replace( 'applications/storm/interface/logs/logs.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Session\Front::i();
$max = ( ini_get( 'max_execution_time' ) / 2 );
$time = (int)\IPS\Request::i()->time;
$alttime = time();

while( true )
{
    $ct = ( time() - $alttime );
    if( $ct >= $max )
    {
        \IPS\Output::i()->json( [ 'error' => 1, 'end' => $alttime, 'time' => time(), 'ct' => $ct ] );
    }
    $logs = '';
    $query = \IPS\Db::i()->select( '*', "core_log", [ 'time >= ? ', $time ], 'id desc' );

    if( count( $query ) )
    {
        $sql = new \IPS\Patterns\ActiveRecordIterator(
            $query,
            'IPS\Log'
        );
        $count = 0;
        foreach( $sql as $log )
        {
            $msg = \IPS\storm\Profiler\Template::i()
                                               ->consoleContainer( 'Log',
                                                   \IPS\storm\Profiler\Template::i()->logObj( $log ), "IPS Log",
                                                   \IPS\storm\Profiler::i()->oddEven( $log->id ) );

            $logs = $msg . "\n" . $logs;
            $count++;
        }

        $logs = [
            'html' => $logs,
            'time' => time() + 1,
            'count' => $count
        ];

        \IPS\Output::i()->json( $logs );
    }
    else
    {
        sleep( 1 );
        continue;
    }
}
