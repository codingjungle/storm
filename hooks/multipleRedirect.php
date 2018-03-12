//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_multipleRedirect extends _HOOK_CLASS_
{

    public function __construct( $url, $callback, $finished, $finalRedirect = true )
    {
        if( isset( \IPS\Request::i()->storm ) and \IPS\Request::i()->storm ) {
            $url = $url->setQueryString( [ 'storm' => \IPS\Request::i()->storm ] );
            $finished = function () {
                $path = 'app=storm&module=configuration&controller=plugins';
                $url = \IPS\Http\Url::internal( $path )->setQueryString( [
                        'storm' => \IPS\Request::i()->storm,
                        'do'    => "doDev",
                    ]
                );
                \IPS\Output::i()->redirect($url);
            };
        }

        parent::__construct( $url, $callback, $finished, $finalRedirect );

    }
}
