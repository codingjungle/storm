<?php

namespace IPS\storm\extensions\storm\MetaData;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\storm\Proxy\Generator\Store;

use function str_replace;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Other
 */
class Other
{
    public function map(&$body): void
    {
        $body[] = <<<eof
    exitPoint(\IPS\Output::error());
    exitPoint(\IPS\Output::sendOutput());
    exitPoint(\IPS\Output::json());
    exitPoint(\IPS\Output::redirect());
    exitPoint(\IPS\Output::showOffline());
    exitPoint(\IPS\Output::showBanned());
    exitPoint(\_p());
    exitPoint(\_d());
    exitPoint(\_e());
    override(\IPS\Settings::i(), map([
        '' => 'IPS\_Settings'
    ]));
    override(\IPS\Request::i(), map([
        '' => 'IPS\_Request'
    ]));    
    override(\IPS\Data\Store::i(), map([
        '' => 'IPS\\Data\\_Store'
    ]));
eof;
    }
}