//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_Log extends _HOOK_CLASS_
{

    public static function log( $message, $category = null )
    {

        if ( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) ) {
            \IPS\storm\Profiler::i()->log( $message, $category );
        }

        return parent::log( $message, $category );

    }
}
