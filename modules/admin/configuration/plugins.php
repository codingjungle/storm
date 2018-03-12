<?php

/**
 * @brief       Plugins Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.8
 * @version     -storm_version-
 */

namespace IPS\storm\modules\admin\configuration;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _plugins extends \IPS\Dispatcher\Controller
{
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'plugins_manage' );
        parent::execute();
    }

    protected function manage()
    {

        $el = [
            [
                'name' => "storm_plugin_upload",
                'class' => "Upload",
                "required" => true,
                "options" => [
                    'allowedFileTypes' => [ 'xml' ],
                    'temporary' => true,
                ],
            ]
        ];

        $form = \IPS\storm\Forms::i( $el );

        if( $vals = $form->values() )
        {

            $xml = new \IPS\Xml\XMLReader;

            $xml->open( $vals[ 'storm_plugin_upload' ] );

            if( !@$xml->read() )
            {
                \IPS\Output::i()->error( 'xml_upload_invalid', '2C145/D', 403, '' );
            }
            try
            {
                \IPS\Db::i()->select( 'plugin_id', 'core_plugins', [
                    'plugin_name=? AND plugin_author=?',
                    $xml->getAttribute( 'name' ),
                    $xml->getAttribute( 'author' )
                ] )->first();

                $tempFileStir = tempnam( \IPS\TEMP_DIRECTORY, 'IPSStorm' );
                move_uploaded_file( $vals[ 'storm_plugin_upload' ], $tempFileStir );
                \IPS\Output::i()->redirect( $this->url->setQueryString( [
                    'do' => "doDev",
                    'storm' => $tempFileStir
                ] ) );
            }
            catch( \UnderflowException $e )
            {
                $tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
                move_uploaded_file( $vals[ 'storm_plugin_upload' ], $tempFile );
                $secondTemp = tempnam( \IPS\TEMP_DIRECTORY, "Storm" );
                copy( $tempFile, $secondTemp );
                $url = \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=doInstall' )
                                    ->setQueryString( [
                                        'file' => $tempFile,
                                        'key' => md5_file( $tempFile ),
                                        'storm' => $secondTemp
                                    ] );

                if( isset( \IPS\Request::i()->id ) )
                {
                    $url = $url->setQueryString( 'id', \IPS\Request::i()->id );
                }

                \IPS\Output::i()->redirect( $url );
            }
        }

        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'storm_plugins_title' );

        \IPS\Output::i()->output = $form;
    }

    protected function doDev()
    {
        \IPS\storm\Plugins::i()->finish( \IPS\Request::i()->storm );
    }
}