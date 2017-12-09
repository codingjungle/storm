<?php

/**
 * @brief       Plugins Class
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
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Plugins extends \IPS\Patterns\Singleton
{

    public static $instance = null;

    public function finish( $file = false )
    {
        $return = \IPS\storm\Plugins::i()->build( $file );
        @unlink( $file );

        $message = \IPS\Member::loggedIn()
                              ->language()
                              ->addToStack( $return[ 'msg' ], false, [ 'sprintf' => [ $return[ 'name' ] ] ] );
        $url = \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=plugins' );
        \IPS\Output::i()->redirect( $url, $message );
    }

    public function build( $plugin )
    {
        $xml = new \IPS\Xml\XMLReader;
        $xml->open( $plugin );
        $xml->read();
        $plugins = \IPS\ROOT_PATH . "/plugins/";
        $versions = [];
        $lang = [];
        $langJs = [];
        $settings = [];
        $return = 'storm_plugins_done';
        $oriName = $xml->getAttribute( 'name' );
        $xml->getAttribute( 'author' );
        $name = \mb_strtolower( \preg_replace( '#[^a-zA-Z0-9_]#', '', $oriName ) );
        $pluginName = $oriName;
        $folder = $plugins . $name . '/dev/';
        $html = $folder . 'html/';
        $css = $folder . 'css/';
        $js = $folder . 'js/';
        $resources = $folder . 'resources/';
        $setup = $folder . 'setup/';
        $widgets = [];
        $filename = '';
        $content = '';

        if( !is_dir( $folder ) )
        {
            mkdir( $folder, 0777, true );
        }

        if( !is_dir( $html ) )
        {
            mkdir( $html, 0777, true );
        }

        if( !is_dir( $css ) )
        {
            mkdir( $css, 0777, true );
        }

        if( !is_dir( $js ) )
        {
            mkdir( $js, 0777, true );
        }

        if( !is_dir( $resources ) )
        {
            mkdir( $resources, 0777, true );
        }

        if( !is_dir( $setup ) )
        {
            mkdir( $setup, 0777, true );
        }

        while( $xml->read() )
        {
            if( $xml->nodeType != \XMLReader::ELEMENT )
            {
                continue;
            }
            if( $xml->name == 'html' )
            {
                $filename = $html . $xml->getAttribute( 'filename' );
                $content = base64_decode( $xml->readString() );
                \file_put_contents( $filename, $content );
            }

            if( $xml->name == 'css' )
            {
                $filename = $css . $xml->getAttribute( 'filename' );
                $content = base64_decode( $xml->readString() );
                \file_put_contents( $filename, $content );
            }

            if( $xml->name == 'js' )
            {
                $filename = $js . $xml->getAttribute( 'filename' );
                $content = base64_decode( $xml->readString() );
                \file_put_contents( $filename, $content );
            }

            if( $xml->name == 'resources' )
            {
                $filename = $html . $xml->getAttribute( 'filename' );
                $content = base64_decode( $xml->readString() );
                \file_put_contents( $filename, $content );
            }

            if( $xml->name == "version" )
            {
                $versions[ $xml->getAttribute( 'long' ) ] = $xml->getAttribute( 'human' );
                $content = $xml->readString();

                if( $content )
                {
                    if( $xml->getAttribute( 'long' ) == '10000' )
                    {
                        $name = $setup . 'install.php';
                    }
                    else
                    {
                        $name = $setup . $xml->getAttribute( 'long' ) . ".php";
                    }

                    \file_put_contents( $name, $content );
                }
            }

            if( $xml->name == "setting" )
            {
                $xml->read();
                $key = $xml->readString();
                $xml->next();
                $value = $xml->readString();
                $settings[] = [ "key" => $key, 'default' => $value ];
            }

            if( $xml->name == 'word' )
            {
                $key = $xml->getAttribute( 'key' );
                $value = $xml->readString();
                $jsW = (int)$xml->getAttribute( 'js' );
                $lang[ $key ] = $value;

                if( $jsW )
                {
                    $langJs[ $key ] = $value;
                }
            }

            if( $xml->name == 'widget'){
                $widgets[ $xml->getAttribute('key')] = [
                    'class' => $xml->getAttribute('class'),
                    'restrict' => explode( ",", $xml->getAttribute('restrict') ),
                    'default_area' => $xml->getAttribute('default_area'),
                    'allow_reuse' => ( $xml->getAttribute('allow_reuse') == 1) ? true : false,
                    'menu_style' => $xml->getAttribute('menu_style'),
                    'embeddable' => ( $xml->getAttribute('embeddable') == 1) ? true : false
                ];
            }
        }

        if( count( $widgets ) ){
            \file_put_contents( $folder . 'widgets.json', json_encode( $widgets, JSON_PRETTY_PRINT ) );
        }
        \file_put_contents( $folder . 'settings.json', json_encode( $settings, JSON_PRETTY_PRINT ) );
        \file_put_contents( $folder . 'versions.json', json_encode( $versions, JSON_PRETTY_PRINT ) );
        \file_put_contents( $folder . "lang.php", "<?php\n\n \$lang = " . var_export( $lang, true ) . ";\n" );
        \file_put_contents( $folder . "jslang.php", "<?php\n\n \$lang = " . var_export( $langJs, true ) . ";\n" );

        return [ 'msg' => $return, 'name' => $pluginName ];
    }
}
