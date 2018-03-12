<?php

/**
 * @brief       Profiler Active Record
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.8
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Profiler
{
    protected static $instance = null;
    protected $countTab = 0;
    protected $consoleTab = 0;
    protected $dbQueriesList = '';
    protected $dbQueriesTab = 0;
    protected $memoryList = '';
    protected $memoryTab = 0;
    protected $memoryTotal = 0;
    protected $fileTab = 0;
    protected $totalTime = null;
    protected $logMessage = '';
    protected $logsTab = 0;
    protected $logList = '';
    protected $processedLogs = '';
    protected $timeTab = 0;
    protected $cacheList = '';
    protected $cacheTab = 0;
    protected $mstart = null;
    protected $ttime = null;
    protected $cacheLogs = [];
    protected $dbLogs = [];
    protected $speed = [];
    protected $speedTab = 0;
    protected $speedList = '';
    protected $ipsLogsList = '';
    protected $ipsLogsTab = 0;
    protected $filesEnabled = true;
    protected $logsEnabled = true;
    protected $dbEnabled = true;
    protected $dbEnabledSpeed = true;
    protected $memEnabled = true;
    protected $cacheEnabled = true;
    protected $timeEnabled = true;
    protected $type = 0;

    final protected function __construct( $type )
    {

        if( defined( 'CJ_STORM_PROFILER_DISABLE_LOGS' ) and CJ_STORM_PROFILER_DISABLE_LOGS )
        {
            $this->logsEnabled = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_DB' ) and CJ_STORM_PROFILER_DISABLE_DB )
        {
            $this->dbEnabled = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_DB_SPEED' ) and CJ_STORM_PROFILER_DISABLE_DB_SPEED )
        {
            $this->dbEnabledSpeed = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_MEM' ) and CJ_STORM_PROFILER_DISABLE_MEM )
        {
            $this->memEnabled = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_CACHE' ) and CJ_STORM_PROFILER_DISABLE_CACHE )
        {
            $this->cacheEnabled = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_TIME' ) and CJ_STORM_PROFILER_DISABLE_TIME )
        {
            $this->timeEnabled = false;
        }

        if( defined( 'CJ_STORM_PROFILER_DISABLE_FILE' ) and CJ_STORM_PROFILER_DISABLE_FILE )
        {
            $this->filesEnabled = false;
        }
    }

    public static function i( $type = 0 )
    {
        if( static::$instance === null )
        {
            static::$instance = new static( $type );
        }
        static::$instance->type = $type;
        return static::$instance;
    }

    public function run()
    {
        if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
        {
            $this->fileTab();
            $this->memoryTotal();
            $this->totalTime();
//            if( $this->type == 1 )
//            {
            $bt = [];
//                if( isset( \IPS\Data\Store::i()->storm_bt ) )
//                {
//                    $bt = \IPS\Data\Store::i()->storm_bt;
//                }

                $ca = [];
//                if( isset( \IPS\Data\Store::i()->storm_cache ) )
//                {
//                    $ca = \IPS\Data\Store::i()->storm_cache ;
//                }
//            }

            \IPS\Data\Store::i()->storm_bt = array_merge( $bt, $this->dbLogs );
            \IPS\Data\Store::i()->storm_cache = array_merge( $ca, $this->cacheLogs);

            return \IPS\storm\Profiler\Template::i()->tabs();
        }
    }

    public static function profilePassCheck()
    {
        if( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE )
        {
            $password = defined( 'CJ_STORM_PROFILER_PASS' ) ? CJ_STORM_PROFILER_PASS : md5( rand( 1, 100000 ) );
            $pass = \IPS\Request::i()->profilerPass;

            if( $password == $pass or ( isset( $_SESSION[ 'storm_profile' ] ) and $_SESSION[ 'storm_profile' ] ) )
            {

                if( !isset( $_SESSION[ 'storm_profile' ] ) )
                {
                    $_SESSION[ 'storm_profile' ] = true;
                }

                return true;
            }
        }
    }

    protected function fileTab()
    {
        if( $this->filesEnabled )
        {
            $files = get_included_files();
            $count = count( $files );
            $this->fileTab = $count;
        }
    }

    protected function memoryTotal( $object = null, $name = 'total' )
    {
        if( $this->memEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                $mem = ( \is_object( $object ) ) ? mb_strlen( \serialize( $object ) ) : \memory_get_usage();
                $mem = $this->formatBytes( $mem );
                if( $name === 'total' )
                {
                    $this->memoryTotal = $this->formatBytes( \memory_get_usage() );
                }
                $this->consoleTab++;
                $this->memoryTab++;
                $msg = \IPS\storm\Profiler\Template::i()->memory( [ 'name' => $name, 'msg' => '', 'memory' => $mem ] );
                $msg = \IPS\storm\Profiler\Template::i()
                    ->consoleContainer( 'Memory', $msg, "Memory",
                        $this->oddEven( $this->memoryTab ) );
                $this->memoryList = $msg . "\n" . $this->memoryList;
                $this->processedLogs = $msg . "\n" . $this->processedLogs;
            }
        }
    }

    protected function formatBytes( $size, $precision = 2 )
    {
        $base = \log( $size, 1024 );
        $suffixes = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

        return \round( \pow( 1024, $base - \floor( $base ) ), $precision ) . ' ' . $suffixes[ \floor( $base ) ];
    }

    public function oddEven( $num )
    {
        if( $num % 2 == 0 )
        {
            return "Even";
        }

        return "Odd";
    }

    protected function totalTime()
    {
        if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
        {

            $this->totalTime = round( microtime( true ) - $_SERVER[ "REQUEST_TIME_FLOAT" ], 4 );

            if( is_array( $this->speed ) and count( $this->speed ) )
            {
                foreach( $this->speed as $num => $speed )
                {
                    $percent = number_format( ( $speed / $this->totalTime ) * 100, 2 );
                    $this->processedLogs = str_replace( "##speed{$num}##", $percent, $this->processedLogs );
                    $this->speedList = str_replace( "##speed{$num}##", $percent, $this->speedList );
                }
            }
        }
    }

    public function __get( $key )
    {
        if( property_exists( $this, $key ) )
        {
            return $this->{$key};
        }
    }

    public function log( $message, $category = null )
    {
        if( $this->logsTab )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {
                $exception_class = '';
                $exception_code = '';

                if( $message instanceof \Exception )
                {
                    $exception_class = get_class( $message );
                    $exception_code = $message->getCode();

                    if( method_exists( $message, 'extraLogData' ) )
                    {
                        $msg = $message->extraLogData() . "\n" . $message->getMessage();
                    }
                    else
                    {
                        $msg = $message->getMessage();
                    }

                    $backtrace = $message->getTraceAsString();
                }
                else
                {
                    if( is_array( $message ) )
                    {
                        $message = var_export( $message, true );
                    }
                    $msg = $message;
                    $backtrace = ( new \Exception )->getTraceAsString();
                }

                $final = [
                    'message' => $msg,
                    'backtrace' => $backtrace,
                    'exception_class' => $exception_class,
                    'exception_code' => $exception_code,
                    'category' => $category
                ];
                $this->consoleTab++;
                $this->ipsLogsTab++;
                $msg = \IPS\storm\Profiler\Template::i()->log( $final );
                $msg = \IPS\storm\Profiler\Template::i()
                    ->consoleContainer( 'Log', $msg, "IPS Log",
                        $this->oddEven( $this->ipsLogsTab ) );
                $this->ipsLogsList = $msg . "\n" . $this->ipsLogsList;
                $this->processedLogs = $msg . "\n" . $this->processedLogs;
            }
        }
    }

    public function dbQuery( $query, $time )
    {
        if( $this->dbEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                $this->consoleTab++;
                $this->dbQueriesTab++;
                $hash = sha1( trim( $query[ 'backtrace' ] ) );
                $this->dbLogs[ $hash ] = $query;
                $msg = \IPS\storm\Profiler\Template::i()->db( [
                    'query' => $query[ 'query' ],
                    'backtrace' => $hash,
                    'time' => $time
                ] );
                $msg = \IPS\storm\Profiler\Template::i()
                    ->consoleContainer( 'DbQueries', $msg, "DB Query",
                        $this->oddEven( $this->dbQueriesTab ) );
                $this->dbQueriesList = $msg . "\n" . $this->dbQueriesList;
                $this->processedLogs = $msg . "\n" . $this->processedLogs;

                if( $this->dbEnabledSpeed )
                {
                    $this->speed( $query[ 'query' ], $time );
                }
            }
        }
    }

    public function speed( $for, $end )
    {
        if( $this->timeEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                $this->consoleTab++;
                $this->speedTab++;
                $percent = "##speed{$this->speedTab}##";
                $this->speed[ $this->speedTab ] = $end;
                $msg = \IPS\storm\Profiler\Template::i()->speed( $for, $end, $percent );
                $msg = \IPS\storm\Profiler\Template::i()
                    ->consoleContainer( 'Speed', $msg, "Execution Time",
                        $this->oddEven( $this->speedTab ) );
                $this->speedList = $msg . "\n" . $this->speedList;
                $this->processedLogs = $msg . "\n" . $this->processedLogs;
            }
        }
    }

    public function memoryStart()
    {
        if( $this->memEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                $this->mstart = \memory_get_usage();
            }
        }
    }

    public function memoryEnd( $for, $msgs = '' )
    {
        if( $this->memEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                if( $this->mstart !== null )
                {
                    $end = \memory_get_usage() - $this->mstart;
                    $this->consoleTab++;
                    $this->memoryTab++;
                    $msg = \IPS\storm\Profiler\Template::i()->memory( [
                        'name' => $for,
                        'msg' => $msgs,
                        'memory' => $this->formatBytes( $end )
                    ] );
                    $msg = \IPS\storm\Profiler\Template::i()
                        ->consoleContainer( 'Memory', $msg, 'Memory',
                            $this->oddEven( $this->memoryTab ) );
                    $this->memoryList = $msg . "\n" . $this->memoryList;
                    $this->processedLogs = $msg . "\n" . $this->processedLogs;
                }
                $this->mstart = null;
            }
        }
    }

    public function cacheLog( $cache = [] )
    {
        if( $this->cacheEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {
                $this->consoleTab++;
                $this->cacheTab++;
                $this->cacheLogs[ $this->cacheTab ] = $cache;
                $msg = \IPS\storm\Profiler\Template::i()->cache( $cache[ 'type' ], $cache[ 'key' ], $this->cacheTab );
                $msg = \IPS\storm\Profiler\Template::i()
                    ->consoleContainer( 'Cache', $msg, "Cache",
                        $this->oddEven( $this->cacheTab ) );
                $this->cacheList = $msg . "\n" . $this->cacheList;
                $this->processedLogs = $msg . "\n" . $this->processedLogs;
            }
        }
    }

    public function timeStart()
    {
        if( $this->timeEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                $this->ttime = microtime( true );
            }
        }
    }

    public function timeEnd( $for )
    {
        if( $this->timeEnabled )
        {
            if( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) )
            {

                if( $this->ttime !== null )
                {
                    $end = microtime( true ) - $this->ttime;
                    $this->speed( $for, $end );
                }
                $this->ttime = null;
            }
        }
    }
}
