<?php

/**
 * @brief       Proxyclass Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.9
 * @version     -storm_version-
 */

namespace IPS\storm\modules\admin\configuration;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Proxyclass
 */
class _proxyclass extends \IPS\Dispatcher\Controller
{

    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'Proxyclass_manage' );
        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'storm_proxyclass_title' );


        \IPS\Output::i()->output =\IPS\Theme::i() ->getTemplate( 'proxyclass', 'storm', 'admin' )->button( $this->url->setQueryString( [ 'do' => 'queue' ] ) );
    }

    // Create new methods with the same name as the 'do' parameter which should execute it

    protected function queue()
    {
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'storm_proxyclass_title' );

        \IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
            \IPS\Http\Url::internal( "app=storm&module=configuration&controller=proxyclass&do=queue&includes".\IPS\Request::i()->includes ),
            function( $data )
            {
                if( !isset( $data[ 'total' ] ) )
                {
                    $data = [];
                    $data[ 'total' ] = \IPS\storm\Proxyclass::i()->dirIterator();
                    $data[ 'current' ] = 0;
                    $data[ 'progress' ] = 0;
                }

                $run = \IPS\storm\Proxyclass::i()->run( $data );

                if( $run == null )
                {
                    return null;
                }
                else
                {
                    $progress = isset( $run[ 'progress' ] ) ? $run[ 'progress' ] : 0;

                    if( $run[ 'total' ] and $run[ 'current' ] )
                    {
                        $progress = ( $run[ 'current' ] / $run[ 'total' ] ) * 100;
                    }

                    $language = \IPS\Member::loggedIn()->language()->addToStack( 'storm_proxyclass_progress', false,
                        [ 'sprintf' => [ $run[ 'current' ], $run[ 'total' ] ] ] );

                    return [
                        [
                            'total' => $run[ 'total' ],
                            'current' => $run[ 'current' ],
                            'progress' => $run[ 'progress' ]
                        ],
                        $language,
                        $progress
                    ];
                }
            },
            function()
            {
                /* And redirect back to the overview screen */
                \IPS\Output::i()
                           ->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=proxyclass' ),
                               'storm_proxyclass_done' );
            }
        );
    }
}