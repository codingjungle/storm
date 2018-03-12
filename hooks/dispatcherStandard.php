//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

abstract class storm_hook_dispatcherStandard extends _HOOK_CLASS_
{
    protected static function baseJs()
    {
        parent::baseJs();

        if ( ( defined( 'CJ_STORM_PROFILER' ) and CJ_STORM_PROFILER ) or ( defined( 'CJ_STORM_PROFILER_SAFE_MODE' ) and CJ_STORM_PROFILER_SAFE_MODE and \IPS\storm\Profiler::profilePassCheck() ) ) {

            \IPS\Output::i()->jsFiles = \array_merge(
                \IPS\Output::i()->jsFiles,
                \IPS\Output::i()->js(
                    'global_profiler.js',
                    'storm',
                    'global'
                )
            );

            \IPS\Output::i()->cssFiles = \array_merge(
                \IPS\Output::i()->cssFiles,
                \IPS\Theme::i()->css(
                    'profiler.css',
                    'storm',
                    'front'
                )
            );
        }

        if ( defined( 'CJ_STORM_DEBUG' ) and CJ_STORM_DEBUG ) {
            $settings[ 'storm_debug_url' ] = \IPS\Settings::i()->base_url . 'applications/storm/interface/debug/index.php';
            $settings[ 'storm_debug_enabled' ] = ( defined( 'CJ_STORM_DEBUG' ) and CJ_STORM_DEBUG ) ? true : false;
            $settings[ 'storm_debug_time' ] = time();
            \IPS\Output::i()->jsVars = \array_merge( \IPS\Output::i()->jsVars, $settings );
            \IPS\Output::i()->jsFiles = \array_merge(
                \IPS\Output::i()->jsFiles,
                \IPS\Output::i()->js(
                    'global_debug.js',
                    'storm',
                    'global'
                )
            );
        }
    }
}
