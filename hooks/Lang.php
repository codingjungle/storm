//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_Lang extends _HOOK_CLASS_
{

    public function parseOutputForDisplay( &$output )
    {
        \IPS\storm\Profiler::i()->timeStart();
        parent::parseOutputForDisplay( $output );
        \IPS\storm\Profiler::i()->timeEnd( 'parseOutputForDisplay' );
    }
}
