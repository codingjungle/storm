<?php

/**
 * @brief       Members Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       2.1.0
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
 * members
 */
class _members extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'members_manage' );
        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        $groups = [];
        foreach( \IPS\Member\Group::groups() as $k => $v )
        {
            $groups[ $k ] = $v->get_formattedName();
        }

        $el = [
            [
                'class' => "Number",
                'name' => "storm_mc_limit",
                'default' => 10,
                'options' => [
                    'min' => 10
                ]
            ],
            [
                'class' => "YesNo",
                'default' => 1,
                'name' => "storm_mc_passwords"
            ],
            [
                'class' => "YesNo",
                'default' => 1,
                'name' => "storm_mc_avatars"
            ],
            [
                'class' => "Select",
                'name' => "storm_mc_group",
                'default' => \IPS\Settings::i()->getFromConfGlobal( 'member_group' ),
                'options' => [
                    'options' => $groups,
                ]
            ]
        ];
        $form = \IPS\storm\Forms::i( $el );

        if( $vals = $form->values() )
        {
            $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=members&do=createMembers" )
                                ->setQueryString( [
                                    'password' => $vals[ 'storm_mc_passwords' ],
                                    'limit' => $vals[ 'storm_mc_limit' ],
                                    'avatar' => $vals[ 'storm_mc_avatars' ],
                                    'group' => $vals[ 'storm_mc_group' ]
                                ] );
            \IPS\Output::i()->redirect( $url );
        }

        \IPS\Output::i()->title = "Create Member";
        \IPS\Output::i()->output = $form;
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
    protected function createMembers()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'storm_create_members_loop' );

        \IPS\Output::i()->title = "Member Creation";
        $limit = \IPS\Request::i()->limit ?: 10;
        $password = \IPS\Request::i()->password ?: null;
        $group = \IPS\Request::i()->group ?: null;
        $avatar = \IPS\Request::i()->avatar ?: null;
        $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=members&do=createMembers" )
                            ->setQueryString( [
                                'password' => $password,
                                'limit' => $limit,
                                'avatar' => $avatar,
                                'group' => $group
                            ] );
        \IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
            $url,
            function( $data )
            {
                $offset = 0;
                $limit = \IPS\Request::i()->limit ?: 10;
                $password = \IPS\Request::i()->password ?: null;
                $group = \IPS\Request::i()->group ?: null;
                $avatar = \IPS\Request::i()->avatar ?: null;
                if( isset( $data[ 'offset' ] ) )
                {
                    $offset = $data[ 'offset' ];
                }

                if( isset( $data[ 'limit' ] ) )
                {
                    $limit = $data[ 'limit' ];
                }

                if( isset( $data[ 'password' ] ) )
                {
                    $password = $data[ 'password' ];
                }

                if( isset( $data[ 'group' ] ) )
                {
                    $group = $data[ 'group' ];
                }

                if( isset( $data[ 'avatar' ] ) )
                {
                    $avatar = $data[ 'avatar' ];
                }

                $max = 10;

                if( $limit < $max )
                {
                    $max = $limit;
                }

                if( $offset >= $limit )
                {
                    return null;
                }


                for( $i = 0; $i < $max; $i++ )
                {
                    $mem = new \IPS\storm\Pseudo\Member;
                    $mem->run( $password, $group, $avatar );
                    $offset++;
                }

                $progress = ( $offset / $limit ) * 100;

                $language = \IPS\Member::loggedIn()->language()->addToStack( 'storm_progress', false, [
                    'sprintf' => [
                        $offset,
                        $limit
                    ]
                ] );

                return [
                    [
                        'password' => $password,
                        'group' => $group,
                        'avatar' => $avatar,
                        'limit' => $limit,
                        'offset' => $offset
                    ],
                    $language,
                    $progress
                ];
            },
            function()
            {
                /* And redirect back to the overview screen */
                \IPS\Output::i()
                           ->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=members' ),
                               'storm_member_creation_done' );
            }
        );
    }
}
