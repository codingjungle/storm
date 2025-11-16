<?php

namespace IPS\storm\extensions\storm\Headerdoc;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden2' );
	exit;
}

/**
 * HeaderDoc
 */
class HeaderDoc extends \IPS\storm\Center\Headerdoc\HeaderdocAbstract
{
    /**
    * files to skip during in the headerdoc
    **/
    public function filesSkip(&$skip)
    {

    }

    /**
    * directories to skip during in the headerdoc
    **/
    public function dirSkip(&$skip)
    {

    }

    public function author(): ?string
    {
        return 'Codingjungle.com';
    }

    public function website(): ?string
    {
        return 'https://codingjungle.com/';
    }

    public function license(): ?string
    {
        return 'https://codingjungle.com/license/';
    }


}
