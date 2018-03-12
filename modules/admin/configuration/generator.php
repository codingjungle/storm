<?php

/**
 * @brief       Generator Class
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
class _generator extends \IPS\Dispatcher\Controller
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

    protected function manage()
    {
        $groups = [];
        foreach( \IPS\Member\Group::groups() as $k => $v )
        {
            $groups[ $k ] = $v->get_formattedName();
        }

        $url = $this->url->setQueryString( [ 'do' => 'delete', 'oldDo' => \IPS\Request::i()->do ] );
        \IPS\Output::i()->sidebar[ 'actions' ][ 'refresh' ] = [
            'icon' => 'delete',
            'title' => 'Delete Dummy Data',
            'link' => $url,

        ];

        $el = [
            [
                'class' => "Select",
                'name' => "storm_gen_type",
                'options' => [
                    'options' => [
                        'none' => "Select Type",
                        'members' => "Members",
                        'forums' => "Forums",
                        'topics' => "Topics",
                        'posts' => "Posts"
                    ],
                    'toggles' => [
                        'members' => [
                            'storm_mc_passwords',
                            'storm_mc_avatars',
                            'storm_mc_group',
                            'storm_mc_club'
                        ]
                    ]
                ],
                'validation' => function( $data )
                {
                    if( $data == "none" )
                    {
                        throw new \InvalidArgumentException( 'storm_gen_none' );
                    }
                }
            ],
            [
                'class' => "Number",
                'name' => "storm_mc_limit",
                'default' => 1,
                'options' => [
                    'min' => 1
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
                'class' => "YesNo",
                'default' => 1,
                'name' => "storm_mc_club"
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
            if( $vals[ 'storm_gen_type' ] == "members" )
            {
                $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator&do=createMembers" )
                                    ->setQueryString( [
                                        'password' => $vals[ 'storm_mc_passwords' ],
                                        'limit' => $vals[ 'storm_mc_limit' ],
                                        'avatar' => $vals[ 'storm_mc_avatars' ],
                                        'group' => $vals[ 'storm_mc_group' ],
                                        'club' => $vals[ 'storm_mc_club' ]
                                    ] );
            }
            else
            {
                $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator&do=generator" )->setQueryString( [
                                        'type' => $vals[ 'storm_gen_type' ],
                                        'limit' => $vals[ 'storm_mc_limit' ]
                                    ] );
            }

            \IPS\Output::i()->redirect( $url );
        }

        \IPS\Output::i()->title = "Generate Dummy Data";
        \IPS\Output::i()->output = $form;
    }

    protected function delete()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'storm_create_delete_loop' );
        \IPS\Output::i()->title = "Delete Content";

        $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator&do=delete" );
        $url->setQueryString( [ 'oldDo' => \IPS\Request::i()->oldDo ] );
        \IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
            $url,
            function( $data )
            {
                $offset = 0;
                if( isset( $data[ 'offset' ] ) )
                {
                    $offset = $data[ 'offset' ];
                }

                if( !isset( $data[ 'total' ] ) )
                {
                    $total = \IPS\Db::i()->select(
                        'COUNT(*)',
                        'storm_generator'
                    )->first();
                }
                else
                {
                    $total = $data[ 'total' ];
                }

                $limit = 10;

                $select = \IPS\Db::i()->select(
                    '*',
                    'storm_generator',
                    [],
                    'generator_id ASC',
                    $limit,
                    null,
                    null,
                    \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS
                );

                if( !$select->count() )
                {
                    return null;
                }

                $content = new \IPS\Patterns\ActiveRecordIterator(
                    \IPS\Db::i()->select( '*', 'storm_generator', [], 'generator_id ASC', $limit ),
                    'IPS\storm\Generator'
                );

                foreach( $content as $key => $v )
                {
                    $v->process();
                    $offset++;
                }

                $progress = ( $offset / $total ) * 100;

                $language = \IPS\Member::loggedIn()->language()->addToStack( 'storm_progress', false, [
                    'sprintf' => [
                        $offset,
                        $total
                    ]
                ] );

                return [ [ 'total' => $total, 'offset' => $offset ], $language, $progress ];
            },
            function()
            {

                \IPS\storm\Generator::finished( "delete" );

                /* And redirect back to the overview screen */
                $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator" );
                \IPS\Output::i()->redirect( $url, 'storm_generation_delete_done' );
            }
        );
    }

    protected function createMembers()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'storm_create_members_loop' );
        \IPS\Output::i()->title = "Member Creation";
        $limit = \IPS\Request::i()->limit ?: 10;
        $password = \IPS\Request::i()->password ?: null;
        $group = \IPS\Request::i()->group ?: null;
        $avatar = \IPS\Request::i()->avatar ?: null;
        $club = \IPS\Request::i()->club ?: null;
        $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator&do=createMembers" )
                            ->setQueryString( [
                                'password' => $password,
                                'limit' => $limit,
                                'avatar' => $avatar,
                                'group' => $group,
                                'club'  => $club,
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
                $club = \IPS\Request::i()->club ?: null;

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

                if ( isset( $data[ 'club' ] ) )
                {
                    $club = $data[ 'club' ];
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
                    $mem->run( $password, $group, $avatar, $club );
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
                        'club' => $club,
                        'limit' => $limit,
                        'offset' => $offset
                    ],
                    $language,
                    $progress
                ];
            },
            function()
            {
                \IPS\storm\Generator::finished( "members" );

                $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator" );
                \IPS\Output::i()->redirect( $url, 'storm_member_creation_done' );
            }
        );
    }

    protected function generator()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'storm_create_generation_loop' );
        \IPS\Output::i()->title = "Generator";
        $type = \IPS\Request::i()->type ?: "forums";
        $limit = \IPS\Request::i()->limit ?: 10;
        $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator&do=generator" )->setQueryString( [
                                'type' => $type,
                                'limit' => $limit
                            ] );

        \IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
            $url,
            function( $data )
            {
                $offset = 0;
                $type = \IPS\Request::i()->type ?: "forums";
                $limit = \IPS\Request::i()->limit ?: 10;
                if( isset( $data[ 'offset' ] ) )
                {
                    $offset = $data[ 'offset' ];
                }

                if( isset( $data[ 'limit' ] ) )
                {
                    $limit = $data[ 'limit' ];
                }

                if( isset( $data[ 'type' ] ) )
                {
                    $type = $data[ 'type' ];
                }

                $max = 1;

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
                    switch( $type )
                    {
                        case "forums":
                            \IPS\storm\Pseudo\Generator::i()->generateForum( false, true );
                            break;
                        case "topics":
                            \IPS\storm\Pseudo\Generator::i()->generateTopic();
                            break;
                        case "posts":
                            \IPS\storm\Pseudo\Generator::i()->generatePost();
                            break;
                    }
                    $offset++;
                }

                $progress = ( $offset / $limit ) * 100;

                $language = \IPS\Member::loggedIn()->language()->addToStack( 'storm_progress', false, [
                    'sprintf' => [
                        $offset,
                        $limit
                    ]
                ] );

                return [ [ 'type' => $type, 'limit' => $limit, 'offset' => $offset ], $language, $progress ];
            },
            function()
            {
                $type = \IPS\Request::i()->type ?: "forums";
                \IPS\storm\Generator::finished( $type );
                
                $url = \IPS\Http\Url::internal( "app=storm&module=configuration&controller=generator" );
                \IPS\Output::i()->redirect( $url, 'storm_member_creation_done' );
            }
        );
    }
}
