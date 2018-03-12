<?php

/**
 * @brief       HeaderdocAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.4
 * @version     -storm_version-
 */

namespace IPS\storm\Headerdoc;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _HeaderdocAbstract
{

    /**
     * finalize header Doc
     **/
    public function headerDocFinalize( $line, $application )
    {
        $line = preg_replace_callback( "#^.+?\s(?=namespace)#s", function( $m ) use ( $application )
        {
            $line = $m[ 0 ];
            //author
            $author = "<a href='" . $application->website . "'>" . $application->author . "</a>";

            $line = preg_replace( '#@author([^\n]+)?#', "@author      {$author}", $line );

            //copyright
            $copyright = "(c) " . ( new \DateTime )->format( "Y" ) . " " . $application->author;

            $line = preg_replace( '#@copyright([^\n]+)?#', "@copyright   {$copyright}", $line );

            $line = preg_replace( '#@version([^\n]+)?#', "@version     {$application->version}", $line );

            return $line;
        }, $line );

        return $line;
    }

    /**
     * since version, shouldn't be used unless you want the "since" version to change
     **/
    public function since( $application )
    {
        return $application->version;
    }
}