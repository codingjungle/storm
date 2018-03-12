<?php

/**
 * @brief       Settings Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Settings extends \IPS\Patterns\Singleton
{
    protected static $instance = null;

    public static function mbUcfirst( $string )
    {
        return mb_strtoupper( mb_substr( $string, 0, 1 ) ) . mb_substr( $string, 1 );
    }

    public static function buildCss( $css )
    {
        if( \IPS\Data\Store::i()->exists( 'dev_css' ) )
        {
            \IPS\Data\Store::i()->delete( 'dev_css' );
        }

        $url = null;
        $files = [];

        foreach( $css as $c )
        {

                $data = \IPS\Http\Url::external( $c );
                $f = $data->queryString[ 'css' ];
                $f = explode( ',', $f);
            foreach( $f as $cc ) {
                $files[] = $cc;
            }
        }
        \IPS\Data\Store::i()->dev_css = $files;
        return str_replace( [ 'http://', 'https://' ], '//', \IPS\Settings::i()->base_url ) . "applications/storm/interface/css/css.php";
    }

    public static function form()
    {
        $s = \IPS\Settings::i();
        $form = \IPS\storm\Forms::i( static::elements(), $s );

        if( $vals = $form->values() )
        {

            $form->saveAsSettings( $vals );
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=settings' ) );
        }

        return $form;
    }

    protected static function elements()
    {
        $e[] = [
            'class' => "YesNo",
            'name' => "storm_settings_tab_debug_css_alt"
        ];

        $e[] = [
            'class' => "YesNo",
            'name' => 'storm_profiler_is_fixed'
        ];

        $e[] = [
            'class' => 'YesNo',
            'name' => 'storm_settings_disable_menu'
        ];

        return $e;
    }

}
