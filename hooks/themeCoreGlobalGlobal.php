//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_themeCoreGlobalGlobal extends _HOOK_CLASS_
{
    public static function hookData()
    {
        return parent::hookData();

    }

    public function includeCSS()
    {
        $parent = parent::includeCSS();
        if( \IPS\Settings::i()->storm_settings_tab_debug_css_alt ) {
            foreach( \IPS\Output::i()->cssFiles as $key => $val ) {
                if( mb_strpos( $val, 'query_log.css' ) !== false ) {
                    unset( \IPS\Output::i()->cssFiles[ $key ] );
                }
            }
            $d = \IPS\Theme::i()->css( 'custom.css', 'core', 'front' );
            foreach( $d as $dd ) {
                \IPS\Output::i()->cssFiles[] = $dd;
            }
            $url = \IPS\storm\Settings::buildCss( \IPS\Output::i()->cssFiles );

            return \IPS\Theme::i()->getTemplate( 'css', 'storm', 'front' )->css( $url );
        } else if( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE ) {
            foreach( \IPS\Output::i()->cssFiles as $key => $val ) {
                if( mb_strpos( $val, 'query_log.css' ) !== false ) {
                    unset( \IPS\Output::i()->cssFiles[ $key ] );
                }
            }
        }

        return $parent;

    }
}
