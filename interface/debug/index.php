<?php
require_once str_replace( 'applications/storm/interface/debug/index.php', '',
        str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Session\Front::i();

//StormDebug::i()->run();
//function quit()
//{
//    \IPS\Output::i()->json(['end' => 1]);
//}
//
//register_shutdown_function('quit');
$max = ( ini_get( 'max_execution_time' ) / 2 ) - 5;
$time = time();

while( true )
{

    $ct = time() - $time;
    if( $ct >= $max )
    {
        \IPS\Output::i()->json( [ 'end' => 1 ] );
    }

    $query = \IPS\Db::i()->select( '*', 'storm_debug', [ 'debug_ajax = ?', 1 ], 'debug_id ASC' );

    if( count( $query ) )
    {

        $messages = new \IPS\Patterns\ActiveRecordIterator(
            $query,
            'IPS\storm\Debug'
        );

        $return = [];

        foreach( $messages as $key => $val )
        {
            $msg = $val->dump;
            $decoded = json_decode( $msg, true );

            if( json_last_error() == JSON_ERROR_NONE )
            {
                $msg = $decoded;
            }

            $data = [
                'type' => $val->type,
                'message' => $msg
            ];

            if( $val->bt )
            {
                $data[ 'bt' ] = $val->bt;
            }

            $return[] = $data;
            $val->delete();
        }

        if( is_array( $return ) and count( $return ) )
        {
            \IPS\Output::i()->json( $return );
        }
    }
    else
    {
        sleep( 1 );
        continue;
    }
}