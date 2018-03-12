//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_Db extends _HOOK_CLASS_
{
    protected $start = null;

    protected $final = null;

    protected $currentQ = 1;
    
    public function query( $query, $log = true, $read = false )
    {
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB' ) and CJ_STORM_PROFILER_DISABLE_DB ) {
            return parent::query( $query, $log, $read );
        }

        $dbMem = true;
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB_MEM' ) and CJ_STORM_PROFILER_DISABLE_DB_MEM ) {
            $dbMem = false;
        }

        if ( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) ) {
            $this->start = microtime( true );
            if ( $dbMem ) {
                \IPS\storm\Profiler::i()->memoryStart();
            }
            $return = parent::query( $query, true );
            $this->final = microtime( true ) - $this->start;
            $this->log( $query );
            $this->sendToProfiler();

            return $return;
        }

        return parent::query( $query, $log, $read );
    }
    
    public function log( $query, $server = null )
    {
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB' ) and CJ_STORM_PROFILER_DISABLE_DB ) {
            parent::log( $query, $server );
        }

        $dbMem = true;
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB_MEM' ) and CJ_STORM_PROFILER_DISABLE_DB_MEM ) {
            $dbMem = false;
        }

        if ( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) ) {
            if ( $dbMem ) {
                \IPS\storm\Profiler::i()->memoryEnd( 'DB Query', $query );
            }
            $bt = var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true );
            $this->log[ $this->currentQ ] = [
                'query'     => $query,
                'backtrace' => $bt,
            ];
        }
        else {
            parent::log( $query, $server );
        }

    }

    public function sendToProfiler()
    {
        if ( isset( $this->log[ $this->currentQ ] ) ) {
            $data = $this->log[ $this->currentQ ];
            \IPS\storm\Profiler::i()->dbQuery( $data, round( $this->final, 4 ) );
            $this->currentQ++;
        }
    }
    
    public function preparedQuery( $query, array $_binds, $read = false )
    {
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB' ) and CJ_STORM_PROFILER_DISABLE_DB ) {
            return parent::preparedQuery( $query, $_binds );
        }
        $dbMem = true;
        if ( defined( 'CJ_STORM_PROFILER_DISABLE_DB_MEM' ) and CJ_STORM_PROFILER_DISABLE_DB_MEM ) {
            $dbMem = false;
        }

        if ( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) ) {
            $this->start = microtime( true );
            if ( $dbMem ) {
                \IPS\storm\Profiler::i()->memoryStart();
            }

            $bindsS = [];
            $queryS = $query;
            $i = 0;
            for ( $j = 0; $j < \strlen( $queryS ); $j++ ) {
                if ( $queryS[ $j ] == '?' ) {
                    if ( array_key_exists( $i, $_binds ) ) {
                        if ( $_binds[ $i ] instanceof \IPS\Db\Select ) {
                            $queryS = \substr( $queryS, 0, $j ) . $_binds[ $i ]->query . \substr( $queryS, $j + 1 );
                            $j += \strlen( $_binds[ $i ]->query );

                            foreach ( $_binds[ $i ]->binds as $_bind ) {
                                $bindsS[] = $_bind;
                            }
                        }
                        else {
                            $bindsS[] = $_binds[ $i ];
                        }

                        $i++;
                    }
                }
            }

            $this->log( static::_replaceBinds( $queryS, $bindsS ) );
            $parent = parent::preparedQuery( $query, $_binds, $read );
            $this->final = microtime( true ) - $this->start;
            $this->sendToProfiler();

            return $parent;
        }

        return parent::preparedQuery( $query, $_binds, $read );
    }
}
