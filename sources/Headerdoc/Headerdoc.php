<?php

/**
 * @brief       Headerdoc Class
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

class _Headerdoc extends \IPS\Patterns\Singleton
{

    public static $instance = null;

    public function addIndexHtml( \IPS\Application $app )
    {
        $continue = false;

        foreach( $app->extensions( 'storm', 'Headerdoc' ) as $class )
        {
            if( method_exists( $class, 'indexEnabled' ) )
            {
                $continue = $class->indexEnabled();
            }
        }

        if( !$continue )
        {
            return;
        }

        $dir = \IPS\ROOT_PATH . "/applications/" . $app->directory;

        $exclude = [
            '.git',
            '.idea',
            'dev'
        ];

        $filter = function( $file, $key, $iterator ) use ( $exclude )
        {
            if( !\in_array( $file->getFilename(), $exclude ) )
            {
                return true;
            }

            return false;
        };

        $dirIterator = new \RecursiveDirectoryIterator(
            $dir,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator( $dirIterator, $filter ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach( $iterator as $iter )
        {
            if( $iter->isDir() )
            {
                $path = $iter->getPathname();
                if( !file_exists( $path . "/index.html" ) )
                {
                    \file_put_contents( $path . "/index.html", '' );
                }
            }
        }
    }

    public function process( \IPS\Application $app )
    {

        if( !$this->can( $app ) )
        {
            return;
        }

        $dir = \IPS\ROOT_PATH . "/applications/" . $app->directory;

        $subpackage = \IPS\Member::loggedIn()->language()->get( "__app_{$app->directory}" );

        $exclude = [
            'hooks',
            'dev',
            'data',
            '3rdparty',
            '3rd_party',
            'vendor',
            '.git',
            '.idea'
        ];

        $since = $app->version;

        foreach( $app->extensions( 'storm', 'Headerdoc' ) as $class )
        {
            if( method_exists( $class, 'headerDocExclude' ) )
            {
                $exclude = array_merge( $exclude, $class->headerDocExclude() );
            }

            $reflector = new \ReflectionMethod( $class, 'since' );

            $isProto = ( $reflector->getDeclaringClass()->getName() !== get_class( $class ) );

            if( $isProto )
            {
                $since = $class->since( $app );
            }
        }

        $filter = function( $file, $key, $iterator ) use ( $exclude )
        {
            if( !\in_array( $file->getFilename(), $exclude ) )
            {
                return true;
            }

            return false;
        };

        $dirIterator = new \RecursiveDirectoryIterator(
            $dir,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator( $dirIterator, $filter ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $iterator = new \RegexIterator( $iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );

        $regEx = '#(?:(?<!\w))(?:[^\w]|\s+)(?:(?:(?:abstract|final|static)\s+)*)(class|trait)\s+([-a-zA-Z0-9_]+)(?:\s+extends\s+([^\s]+))?#';

        foreach( $iterator as $file )
        {
            try
            {
                $filePath = $file[ 0 ];

                $line = \file_get_contents( $filePath );

                preg_match( "#^.+?\s(?=namespace)#s", $line, $section );

                if( isset( $section[ 0 ] ) )
                {
                    preg_match( '#@since([^\n]+)?#', $section[ 0 ], $sinced );
                }
                else
                {
                    $sinced = [];
                }

                if( !isset( $sinced[ 1 ] ) )
                {

                    preg_match( "#^.+?\s(?=namespace)#s", $line, $section );

                    if( isset( $section[ 0 ] ) )
                    {
                        preg_match( '#@brief([^\n]+)?#', $section[ 0 ], $brief );
                    }
                    else
                    {
                        $brief = [];
                    }

                    if( !isset( $brief[ 1 ] ) )
                    {
                        $path = pathinfo( $filePath );

                        $type = $path[ 'dirname' ];

                        $type = str_replace( '\\', '/', $type );

                        $file = $path[ 'filename' ];

                        if( \mb_strpos( $filePath, "extensions" ) !== false )
                        {
                            $type = explode( '/', $type );
                            $extension = \IPS\storm\Settings::mbUcfirst( mb_strtolower( array_pop( $type ) ) );
                            $extApp = \IPS\storm\Settings::mbUcfirst( mb_strtolower( array_pop( $type ) ) );
                            $brief = $extApp . " " . $extension . " extension: " . \IPS\storm\Settings::mbUcfirst( $file );
                        }
                        else
                        {
                            $file = \IPS\storm\Settings::mbUcfirst( $file );

                            \preg_match(
                                $regEx,
                                $line,
                                $matches
                            );

                            if( isset( $matches[ 3 ] ) )
                            {
                                $brief = ( \mb_strpos( $matches[ 3 ],
                                        "Model" ) !== false ) ? $file . " Node" : $file . " Class";
                            }
                            else
                            {
                                $brief = $file;
                                $brief .= ( isset( $matches[ 1 ] ) ) ? " " . \IPS\storm\Settings::mbUcfirst( $matches[ 1 ] ) : " Class";
                            }
                        }

                        $brief = \trim( $brief );
                    }
                    else
                    {
                        $brief = str_replace( ' ', '', trim( $brief[ 1 ] ) );
                    }

                    $replacement = <<<EOF
/**
 * @brief       {$brief}
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  {$subpackage}
 * @since       {$since}
 * @version     -storm_version-
 */
EOF;
                    $line = \preg_replace( "#^.+?\s(?=namespace)#s", "<?php\n\n$replacement\n\n", $line );

                    \file_put_contents( $filePath, $line );
                }
                else
                {

                    $write = false;

                    $line = preg_replace_callback( "#^.+?\s(?=namespace)#s", function( $m ) use ( &$write, $since )
                    {
                        $line = $m[ 0 ];

                        //
                        preg_match( '#@since([^\n]+)?#', $line, $since );

                        if( isset( $since[ 1 ] ) and trim( $since[ 1 ] ) == '-storm_since_version-' )
                        {
                            $write = true;

                            $since = <<<EOF
@author      {$since}
EOF;

                            $line = preg_replace( '#@author([^\n]+)?#', $since, $line );
                        }
                        //author
                        preg_match( '#@author([^\n]+)?#', $line, $auth );

                        if( isset( $auth[ 1 ] ) and trim( $auth[ 1 ] ) != '-storm_author-' )
                        {
                            $write = true;

                            $author = <<<EOF
@author      -storm_author-
EOF;

                            $line = preg_replace( '#@author([^\n]+)?#', $author, $line );
                        }

                        //version
                        preg_match( '#@version([^\n]+)?#', $line, $ver );

                        if( isset( $ver[ 1 ] ) and trim( $ver[ 1 ] ) != '-storm_version-' )
                        {
                            $write = true;

                            $ver = <<<EOF
@version     -storm_version-
EOF;

                            $line = preg_replace( '#@version([^\n]+)?#', $ver, $line );
                        }

                        //copyright
                        preg_match( '#@copyright([^\n]+)?#', $line, $cp );

                        if( isset( $cp[ 1 ] ) and trim( $cp[ 1 ] ) != '-storm_copyright-' )
                        {
                            $write = true;

                            $cpy = <<<EOF
@copyright   -storm_copyright-
EOF;

                            $line = preg_replace( '#@copyright([^\n]+)?#', $cpy, $line );
                        }

                        return $line;
                    }, $line );

                    if( $write )
                    {
                        \file_put_contents( $filePath, $line );
                    }
                }
            }
            catch( \Exception $e )
            {
            }
        }
    }

    public function can( $app )
    {
        $continue = false;

        foreach( $app->extensions( 'storm', 'Headerdoc' ) as $class )
        {
            if( method_exists( $class, 'headerDocEnabled' ) )
            {
                $continue = $class->headerDocEnabled();
            }
        }

        return $continue;
    }
}