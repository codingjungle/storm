//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class storm_hook_frontGlobal extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
    return parent::hookData();
}
/* End Hook Data */

function queryLog( $log ){
    if( defined( 'CJ_STORM_PROFILER') and CJ_STORM_PROFILER) {
        if( !\IPS\Request::i()->isAjax() ) {
            return \IPS\storm\Profiler::i()->run( true );
        }
    }
    else{
        return parent::queryLog( $log );
    }
}
}
