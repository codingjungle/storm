<?php

/**
 * @brief       Debug Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.3
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Debug extends \IPS\Patterns\ActiveRecord
{

    /**
     * @brief    [ActiveRecord] Database table
     */
    public static $databaseTable = 'storm_debug';

    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static $databasePrefix = "debug_";

    /**
     * @brief    [ActiveRecord] ID Database Column
     */
    public static $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $multitons;

    /**
     * @brief    [ActiveRecord] Database ID Fields
     */
    protected static $databaseIdFields = [ 'debug_id' ];

    /**
     * @brief    Bitwise keys
     */
    protected static $bitOptions = [];

    protected static $clear = true;

    public static function both(
        $message,
        $logType = null,
        $consoleType = "type",
        $includeBackTrace = false,
        $file = null,
        $dir = null
    ) {
        static::console( $message, $consoleType, $includeBackTrace );
        static::log( $message, $logType, $file, $dir );
    }

    public static function console( $message, $type = "log", $includeBackTrace = false )
    {
        if( defined( 'CJ_STORM_DEBUG' ) and CJ_STORM_DEBUG )
        {
            if( !$message )
            {
                return;
            }

            $debug = new static;

            if( is_array( $message ) )
            {
                $message = json_encode( $message );
            }
            $debug->time = time();
            $debug->dump = $message;
            $debug->type = $type;

            if( $includeBackTrace )
            {
                $debug->bt = ( new \Exception )->getTraceAsString();
            }

            $debug->save();
        }
    }

    public static function log( $message, $type = null, $file = null, $dir = null )
    {
        if( defined( 'CJ_STORM_DEBUG' ) and CJ_STORM_DEBUG )
        {
            if( !$message )
            {
                return;
            }

            $date = date( 'r' );

            if( $message instanceof \Exception )
            {
                $message = $date . "\n" . get_class( $message ) . '::' . $message->getCode() . "\n" . $message->getMessage() . "\n" . $message->getTraceAsString();
            }
            else
            {
                if( is_array( $message ) )
                {
                    $message = var_export( $message, true );
                }

                $message = $date . "\n" . $message . "\n" . ( new \Exception )->getTraceAsString();
            }

            if( $dir == null )
            {
                $dir = \IPS\ROOT_PATH . "/uploads/logs";
            }
            else
            {
                if( mb_strpos( $dir, \IPS\ROOT_PATH ) === false )
                {
                    $dir = \IPS\ROOT_PATH . "/" . $dir;
                }
            }

            if( !is_dir( $dir ) )
            {
                if( !@mkdir( $dir ) or !@chmod( $dir, \IPS\IPS_FOLDER_PERMISSION ) )
                {
                    return;
                }
            }

            if( $file == null )
            {
                $type = ( $type ) ? "_{$type}" : "";
                $file = $dir . '/' . date( 'Y' ) . '_' . date( 'm' ) . '_' . date( 'd' ) . $type;
            }
            else
            {
                $type = ( $type ) ? "_{$type}" : "";
                $file = $dir . '/' . $file . $type;
            }

            if( file_exists( $file ) )
            {
                @\file_put_contents( $file, "\n\n-------------\n\n" . $message, FILE_APPEND );
            }
            else
            {
                @\file_put_contents( $file, $message );
            }

            @chmod( $file, \IPS\IPS_FILE_PERMISSION );
        }
    }

    public static function returnLog()
    {
        $return = [];

        $return[] = "<script>";

        $query = \IPS\Db::i()->select( '*', 'storm_debug', [ 'debug_ajax = ?', 0 ], 'debug_id ASC' );

        if( count( $query ) )
        {
            $messages = new \IPS\Patterns\ActiveRecordIterator(
                $query,
                'IPS\storm\Debug'
            );

            foreach( $messages as $key => $val )
            {
                switch( $val->type )
                {
                    default:
                    case 'log':
                        $return[] = "console.log('{$val->dump}');";
                        break;
                    case 'debug':
                        $return[] = "console.debug('{$val->dump}');";
                        break;
                    case 'dir':
                        $return[] = "console.dir('{$val->dump}');";
                        break;
                    case 'dirxml':
                        $return[] = "console.dirxml('{$val->dump}');";
                        break;
                    case 'error':
                        $return[] = "console.error('{$val->dump}');";
                        break;
                    case 'info':
                        $return[] = "console.info('{$val->dump}');";
                        break;
                }

                if( $val->bt )
                {
                    $return[] = "console.log('{$val->bt}');";
                }

                $val->delete();
            }
        }

        $return[] = "</script>";

        return implode( "\n", $return );
    }

    public static function ajax( $msg, $type = "log", $includeBackTrace = false )
    {
        if( defined( 'CJ_STORM_DEBUG' ) and CJ_STORM_DEBUG )
        {
            if( !$msg )
            {
                return;
            }

            $debug = new static;

            if( is_array( $msg ) )
            {
                $msg = json_encode( $msg );
            }

            $debug->dump = $msg;
            $debug->type = $type;
            $debug->ajax = 1;
            $debug->time = time();

            if( $includeBackTrace )
            {
                $debug->bt = ( new \Exception )->getTraceAsString();
            }

            $debug->save();
        }
    }
}