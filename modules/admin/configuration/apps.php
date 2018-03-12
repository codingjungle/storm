<?php

/**
 * @brief       Apps Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.6
 * @version     -storm_version-
 */

namespace IPS\storm\modules\admin\configuration;

/* To prevent PHP errors (extending class does not exist) revealing path */
use IPS\Member;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * apps
 */
class _apps extends \IPS\Dispatcher\Controller
{

    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'apps_manage' );
        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        $apps = \IPS\Data\Store::i()->applications;


        $groups[ 'select' ] = \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_apps_select' );

        foreach( \IPS\Application::applications() as $key => $val )
        {
            $groups[ $val->directory ] = \IPS\Member::loggedIn()
                                                            ->language()
                                                            ->addToStack( "__app_{$val->directory}" );
        }

        $langs = [
            'select' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_select' ),
            'all' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_all' ),
            'language' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_lang' ),
            'javascript' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_js' ),
            'templates' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_template' ),
            'email' => \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_type_email' ),
        ];

        $validation = function( $data )
        {
            if( $data == "select" )
            {
                throw new \InvalidArgumentException( 'form_bad_value' );
            }
            $app = \IPS\Request::i()->storm_apps_app;
            $folders = \IPS\ROOT_PATH . "/applications/{$app}/dev";
            $f = $folders;
            $folders2 = false;
            $folders3 = false;

            if( $data != "all" )
            {
                switch( $data )
                {
                    case 'language':
                        $folders .= "/lang.php";
                        $folders2 = $f . "/jslang.php";
                        break;
                    case "javascript":
                        $folders .= "/js/";
                        break;
                    case "templates":
                        $folders .= "/html/";
                        $folders2 = $f . "/css/";
                        $folders3 = $f . "/resources/";
                        break;
                    case "email":
                        $folders .= "/email/";
                        break;
                }
            }

            if( file_exists( $folders ) or ( $folders2 and file_exists( $folders2 ) and $folders = $folders2 ) or ( $folders3 and file_exists( $folders3 ) and $folders = $folders3 ) )
            {
                $lang = \IPS\Member::loggedIn()
                                   ->language()
                                   ->addToStack( 'storm_apps_folder_exist', false, [ 'sprintf' => $folders ] );
                throw new \InvalidArgumentException( $lang );
            }
        };

        $el = [
            [
                'name' => 'storm_apps_app',
                'class' => 'Select',
                'ap' => true,
                'default' => 'select',
                'options' => [
                    'options' => $groups
                ],
                'v' => function( $data )
                {
                    if( $data == "select" )
                    {
                        throw new \InvalidArgumentException( 'form_bad_value' );
                    }
                }
            ],
            [
                'name' => 'storm_apps_type',
                'class' => 'Select',
                'ap' => true,
                'default' => 'all',
                'options' => [
                    'options' => $langs
                ],
                'v' => $validation
            ]
        ];

        $form = \IPS\storm\Forms::i( $el );

        if( $vals = $form->values() )
        {
            $app = $vals[ 'storm_apps_app' ];
            $type = $vals[ 'storm_apps_type' ];

            if( $type === "all" )
            {
                \IPS\Output::i()->redirect( $this->url->setQueryString( [ 'do' => "queue", 'appKey' => $app ] )  );
            }
            else
            {
                $return = \IPS\storm\Apps::i( $app )->{$type}();
                \IPS\Output::i()->redirect( $this->url, $return );
            }
        }

        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_title' );
        \IPS\Output::i()->output = $form;
    }

    protected function queue()
    {
        \IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_queue_title' );

        $app = \IPS\Request::i()->appKey;

        \IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
            \IPS\Http\Url::internal( "app=storm&module=configuration&controller=apps&do=queue&appKey=" . $app ),
            function( $data )
            {
                $app = \IPS\Request::i()->appKey;

                $end = false;

                if( isset( $data[ 'next' ] ) )
                {
                    $do = $data[ 'next' ];
                }
                else
                {
                    $do = 'language';
                }

                $done = 0;

                switch( $do )
                {
                    case 'language':
                        \IPS\storm\Apps::i( $app )->language();
                        $done = 25;
                        $next = 'javascript';
                        break;
                    case 'javascript':
                        \IPS\storm\Apps::i( $app )->javascript();
                        $done = 50;
                        $next = 'templates';
                        break;
                    case 'templates':
                        \IPS\storm\Apps::i( $app )->templates();
                        $done = 75;
                        $next = 'email';
                        break;
                    case 'email':
                        \IPS\storm\Apps::i( $app )->email();
                        $done = 100;
                        $next = 'default';
                        break;
                    default:
                        $end = true;
                        break;
                }

                if( $end )
                {
                    return null;
                }
                else
                {

                    $language = \IPS\Member::loggedIn()->language()->addToStack( 'storm_apps_total_done', false,
                        [ 'sprintf' => [ $done, 100 ] ] );

                    return [ [ 'next' => $next ], $language, $done ];
                }
            },
            function()
            {
                $app = \IPS\Request::i()->appKey;
                $app = \IPS\Member::loggedIn()->language()->addToStack( "__app_{$app}" );
                $msg = \IPS\Member::loggedIn()->language()->addToStack('storm_apps_completed', false, ['sprintf' => [ $app]]);
                /* And redirect back to the overview screen */
                \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=apps' ),
                    $msg );
            }
        );
    }
}