<?php

require_once '../../../../init.php';

if( \IPS\IN_DEV !== true AND !\IPS\Theme::designersModeEnabled() )
{
    exit();
}

try
{
    /* The CSS is parsed by the theme engine, and the theme engine has plugins, and those plugins need to now which theme ID we're using */
    if( \IPS\Theme::designersModeEnabled() )
    {
        \IPS\Session\Front::i();
    }
    $names = [];

    $needsParsing = false;
    $cs = [];
    foreach( \IPS\Data\Store::i()->dev_css as $key => $val )
    {
        if( is_array( $val ) )
        {
            foreach( $val as $k => $v )
            {
                $cs[] = $v;
            }
        }
        else
        {
            $cs[] = $val;
        }
    }
    $cssF = implode( ',', $cs );
    if( strstr( $cssF, ',' ) )
    {
        $contents = '';
        foreach( explode( ',', $cssF ) as $css )
        {
            if( mb_substr( $css, -4 ) !== '.css' )
            {
                continue;
            }
            $names[] = $css;
            $css = str_replace( \IPS\ROOT_PATH, '', $css );
            $css = str_replace( '../', '&#46;&#46;/', $css );
            $file = file_get_contents( \IPS\ROOT_PATH . '/' . $css );
            $params = processFile( $file );

            if( $params[ 'hidden' ] === 1 )
            {
                continue;
            }

            $contents .= "\n" . $file;

            if( needsParsing( $css ) )
            {
                $needsParsing = true;
            }
        }
    }
    else
    {
        if( mb_substr( $cssF, -4 ) !== '.css' )
        {
            exit();
        }
        $names[] = $cssF;
        $contents = file_get_contents( \IPS\ROOT_PATH . '/' . str_replace( '../', '&#46;&#46;/', $cssF ) );

        $params = processFile( $contents );

        if( $params[ 'hidden' ] === 1 )
        {
            exit;
        }

        if( needsParsing( $cssF ) )
        {
            $needsParsing = true;
        }
    }

    if( $needsParsing )
    {
        if( \IPS\Theme::designersModeEnabled() )
        {
            /* If we're in designer's mode, we need to reset the theme ID based on the CSS path as we could be in the ACP which may have a different theme ID set */
            preg_match( '#themes/(\d+)/css/(.+?)/(.+?)/(.*)\.css#', $cssF, $matches );

            if( $matches[ 1 ] and $matches[ 1 ] !== \IPS\Theme::$memberTheme->id )
            {
                try
                {
                    \IPS\Theme::$memberTheme = \IPS\Theme\Advanced\Theme::load( $matches[ 1 ] );
                }
                catch( \OutOfRangeException $ex )
                {
                }
            }
        }
        $names = str_replace( [ '/', '.css', '-' ], '', implode( '', $names ) );
        $functionName = 'css_' . md5( $names );

        $contents = str_replace( '\\', '\\\\', $contents );
        \IPS\Theme::makeProcessFunction( $contents, $functionName );
        $functionName = "IPS\\Theme\\{$functionName}";
        if( function_exists( $functionName ) ) {
            \IPS\Output::i()->sendOutput( $functionName(), 200, 'text/css' );
        }else{
            \IPS\Output::i()->sendOutput( '', 200, 'text/css' );
        }
    }
    else
    {
        \IPS\Output::i()->sendOutput( $contents, 200, 'text/css' );
    }
}
catch( \Exception $e )
{
    \IPS\Log::log( $e );
}

/**
 * Determine whether this file needs parsing or not
 *
 * @return boolean
 */
function needsParsing( $fileName )
{
    if( \IPS\IN_DEV === true AND !\IPS\Theme::designersModeEnabled() )
    {
        preg_match( '#applications/(.+?)/dev/css/(.+?)/(.*)\.css#', $fileName, $matches );
    }
    else
    {
        preg_match( '#themes/(?:\d+)/css/(.+?)/(.+?)/(.*)\.css#', $fileName, $matches );
    }

    return count( $matches );
}

/**
 * Process the file to extract the header tag params
 *
 * @return array
 */
function processFile( $contents )
{
    $return = [ 'module' => '', 'app' => '', 'pos' => '', 'hidden' => 0 ];

    /* Parse the header tag */
    preg_match_all( '#^/\*<ips:css([^>]+?)>\*/\n#', $contents, $params, PREG_SET_ORDER );
    foreach( $params as $id => $param )
    {
        preg_match_all( '#([\d\w]+?)=\"([^"]+?)"#i', $param[ 1 ], $items, PREG_SET_ORDER );

        foreach( $items as $key => $attr )
        {
            if( isset( $attr[1] ) and isset( $attr[2] ) ) {
                switch( trim( $attr[ 1 ] ) ) {
                    case 'module':
                        $return[ 'module' ] = trim( $attr[ 2 ] );
                        break;
                    case 'app':
                        $return[ 'app' ] = trim( $attr[ 2 ] );
                        break;
                    case 'position':
                        $return[ 'pos' ] = intval( $attr[ 2 ] );
                        break;
                    case 'hidden':
                        $return[ 'hidden' ] = intval( $attr[ 2 ] );
                        break;
                }
            }
        }
    }

    return $return;
}