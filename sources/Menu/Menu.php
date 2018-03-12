<?php

/**
 * @brief           Menu Node
 * @author          <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package         IPS Social Suite
 * @subpackage      Storm
 * @since           -storm_since_version-
 * @version         3.0.4
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Menu extends \IPS\Node\Model
{
    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $multitons;

    /**
     * @brief    [ActiveRecord] Default Values
     */
    protected static $defaultValues = null;

    /**
     * @brief    [ActiveRecord] Database Table
     */
    public static $databaseTable = 'storm_menu';

    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static $databasePrefix = 'menu_';

    /**
     * @brief    [ActiveRecord] ID Database Column
     */
    public static $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Database ID Fields
     */
    protected static $databaseIdFields = [ 'menu_id', 'menu_name' ];

    /**
     * @brief    [Node] Order Database Column
     */
    public static $databaseColumnOrder = 'order';

    /**
     * @brief    [Node] Parent ID Database Column
     */
    public static $databaseColumnParent = 'parent';

    /**
     * @brief   [Node] Parent ID Root Value
     * @note    This normally doesn't need changing though some legacy areas use -1 to indicate a root node
     */
    public static $databaseColumnParentRootValue = 0;

    /**
     * @brief    [Node] Enabled/Disabled Column
     */
    public static $databaseColumnEnabledDisabled = null;

    /**
     * @brief    [Node] Show forms modally?
     */
    public static $modalForms = false;

    /**
     * @brief    [Node] Node Title
     */
    public static $nodeTitle = 'Menu';

    /**
     * @brief    [Node] ACP Restrictions
     * @code
    array(
     * 'app'        => 'core',                // The application key which holds the restrictrions
     * 'module'    => 'foo',                // The module key which holds the restrictions
     * 'map'        => array(                // [Optional] The key for each restriction - can alternatively use
     * "prefix"
     * 'add'            => 'foo_add',
     * 'edit'            => 'foo_edit',
     * 'permissions'    => 'foo_perms',
     * 'delete'        => 'foo_delete'
     * ),
     * 'all'        => 'foo_manage',        // [Optional] The key to use for any restriction not provided in the map
     * (only needed if not providing all 4)
     * 'prefix'    => 'foo_',                // [Optional] Rather than specifying each  key in the map, you can specify
     * a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
     * @encode
     */
    protected static $restrictions = [
        'app'    => 'storm',
        'module' => 'menu',
        'prefix' => 'menu_',
    ];


    /**
     * @brief    Bitwise values for members_bitoptions field
     */
    public static $bitOptions = [
        'bitoptions' => [
            'bitoptions' => [],
        ],
    ];

    /**
     * @brief    Cached URL
     */
    protected $_url = null;

    /**
     * get title
     */
    public function get__title()
    {
        return $this->name;
    }

    /**
     * [Node] Add/Edit Form
     *
     * @param    \IPS\Helpers\Form $form The form
     *
     * @return    void
     */
    public function form( &$form )
    {
        $form = \IPS\storm\Forms::i( $this->elements(), $this, null, $form );
    }

    public function elements()
    {

        $el[ 'prefix' ] = 'storm_menu_';
        $el[] = [
            'name'     => 'name',
            'required' => true,
        ];

        $el[] = [
            'name'    => 'parent',
            'class'   => 'node',
            'default' => \IPS\Request::i()->parent ?: $this->parent,
            'options' => [
                'class' => 'IPS\storm\Menu',
            ],
        ];

        $el[] = [
            'name'    => 'type',
            'class'   => 'Select',
            'options' => [
                'options' => [
                    'root' => 'Root',
                    'int'  => 'Internal',
                    'ext'  => 'External',
                ],
                'toggles' => [
                    'int' => [ 'internal' ],
                    'ext' => [ 'external' ],
                ],
            ],
        ];


        $el[] = [
            'name'     => 'internal',
            'required' => true,
            'def'      => $this->url,
        ];

        $el[] = [
            'name'     => 'external',
            'required' => true,
            'def'      => $this->url,
        ];

        return $el;
    }

    /**
     * [Node] Format form values from add/edit form for save
     *
     * @param    array $values Values from the form
     *
     * @return    array
     */
    public function formatFormValues( $values )
    {
        $new = [];

        foreach( $values as $key => $val ) {
            $key = str_replace( 'storm_menu_', '', $key );
            $new[ $key ] = $val;
        }

        if( !$this->id ) {
            $new[ 'original' ] = \IPS\Http\Url::seoTitle( $new[ 'name' ] );
        }

        if( $new[ 'parent' ] instanceof \IPS\storm\Menu ) {
            $new[ 'parent' ] = $new[ 'parent' ]->id;
        } else {
            $new[ 'parent' ] = 0;
        }

        if( $new[ 'type' ] == 'int' ) {
            $new[ 'url' ] = $new[ 'internal' ];
        } else {
            $new[ 'url' ] = $new[ 'external' ];
        }

        unset( $new[ 'internal' ] );
        unset( $new[ 'external' ] );

        return $new;
    }

    /**
     * [Node] Save Add/Edit Form
     *
     * @param    array $values Values from the form
     *
     * @return    void
     */
    public function saveForm( $values )
    {
        parent::saveForm( $values );
    }


    public function getUrl()
    {
        if( $this->type === 'root' ) {
            return 'elStormDev' . $this->original . 'App';
        } else {
            return $this->_data[ 'url' ];
        }
    }

    public function foo()
    {
        return $this->_data;
    }

    public static function kerching()
    {
        $sql = \IPS\Db::i()->select( '*', 'storm_menu', null, 'menu_order asc' );
        $menus = new \IPS\Patterns\ActiveRecordIterator( $sql, 'IPS\storm\Menu' );
        $store = [];
        foreach( $menus as $menu ) {
            $store[ $menu->parent ][] = [
                'name' => $menu->name,
                'url'  => $menu->getUrl(),
                'id'   => $menu->id,
                'ori'  => $menu->original,
                'type' => $menu->type,
            ];
        }
        unset( \IPS\Data\Store::i()->storm_menu );
        \IPS\Data\Store::i()->storm_menu = $store;
    }

    public function delete()
    {
        $parent = parent::delete();
        static::kerching();

        return $parent;
    }

    public function save()
    {
        parent::save();
        static::kerching();
    }

    public static function devBar()
    {

        if( \IPS\Settings::i()->storm_settings_disable_menu ) {
            return;
        }
        $applications = false;
        foreach( \IPS\Application::applications() as $apps ) {
            $applications[] = [
                'name' => $apps->directory,
                'url'  => \IPS\Http\Url::internal( 'app=core&module=applications&controller=developer&appKey=' . $apps->directory ),
            ];
        }
        $plugins = false;
        foreach( \IPS\Plugin::plugins() as $plugin ) {
            $plugins[] = [
                'name' => $plugin->name,
                'url'  => \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=developer&id=' . $plugin->id ),
            ];
        }
        $menus = static::getStore();
        $newMenus = [];
        if( isset( $menus[ 0 ] ) ) {
            foreach( $menus[ 0 ] as $roots ) {
                $newMenus[ 0 ][] = $roots;
                if( isset( $menus[ $roots[ 'id' ] ] ) ) {
                    foreach( $menus[ $roots[ 'id' ] ] as $child ) {
                        if( $child[ 'type' ] == 'int' ) {
                            $child[ 'url' ] = \IPS\Http\Url::internal( $child[ 'url' ] );
                        } else {
                            $child[ 'url' ] = \IPS\Http\Url::external( $child[ 'url' ] );
                        }
                        $newMenus[ $roots[ 'id' ] ][] = $child;
                    }
                }
            }
        }
        $version = \IPS\Application::load( 'core' );
        $menu = \IPS\Theme::i()->getTemplate( 'dev', 'storm', 'admin' )->devBar( $newMenus, $applications, $plugins );
        if( $version->long_version < 101110 ) {

            $menu = \IPS\Theme::i()->getTemplate( 'dev', 'storm', 'admin' )->devBar2( $menu );
        }

        return $menu;
    }

    public static function getStore()
    {
        if( !isset( \IPS\Data\Store::i()->storm_menu ) ) {
            static::kerching();
        }

        return \IPS\Data\Store::i()->storm_menu;
    }

    /**
     * [Node] Does the currently logged in user have permission to add a child node to this node?
     *
     * @return    bool
     */
    public function canAdd()
    {
        if( $this->parent != 0 ) {
            return false;
        }

        return parent::canAdd();

    }

    public function canDelete()
    {
        if( !$this->delete ) {
            return false;
        }

        return parent::canDelete();
    }

    public function canCopy()
    {
        return false;
    }

    public static $installMenus = '{"0":[{"id":2,"name":"Storm","parent":0,"type":"root","url":null,"order":1,"original":"storm","delete":0},{"id":3,"name":"IPS","parent":0,"type":"root","url":null,"order":2,"original":"ips","delete":0},{"id":4,"name":"System","parent":0,"type":"root","url":null,"order":3,"original":"system","delete":0},{"id":5,"name":"Plugins","parent":0,"type":"root","url":null,"order":4,"original":"plugins","delete":0},{"id":6,"name":"Apps","parent":0,"type":"root","url":null,"order":5,"original":"apps","delete":0}],"2":[{"id":7,"name":"Settings","parent":2,"type":"int","url":"app=storm&module=configuration&controller=settings","order":1,"original":"settings","delete":0},{"id":40000,"name":"Sync","parent":1,"type":"int","url":"app=storm&module=configuration&controller=sync","order":28,"original":"sync","delete":0},{"id":8,"name":"Dummy Data Generator","parent":2,"type":"int","url":"app=storm&module=configuration&controller=generator","order":2,"original":"dummy-data-generator","delete":0},{"id":9,"name":"Proxy Class Generator","parent":2,"type":"int","url":"app=storm&module=configuration&controller=proxyclass","order":3,"original":"proxy-class-generator","delete":0},{"id":10,"name":"App Dev Folder","parent":2,"type":"int","url":"app=storm&module=configuration&controller=apps","order":4,"original":"app-dev-folder","delete":0},{"id":11,"name":"Plugins Dev Folder","parent":2,"type":"int","url":"app=storm&module=configuration&controller=plugins","order":5,"original":"plugins-dev-folder","delete":0},{"id":25,"name":"Menu","parent":2,"type":"int","url":"app=storm&module=configuration&controller=menu","order":6,"original":"menu","delete":1}],"3":[{"id":12,"name":"Guides","parent":3,"type":"ext","url":"https:\/\/invisioncommunity.com\/4guides\/how-to-use-ips-community-suite\/first-steps\/terminology-r7\/","order":11,"original":"guides","delete":0},{"id":13,"name":"Developer Docs","parent":3,"type":"ext","url":"https:\/\/invisioncommunity.com\/developers\/","order":12,"original":"developer-docs","delete":0},{"id":14,"name":"Community Forums","parent":3,"type":"ext","url":"https:\/\/invisioncommunity.com\/forums\/forum\/503-customization-resources\/","order":13,"original":"community-forums","delete":0},{"id":15,"name":"Release Notes","parent":3,"type":"ext","url":"https:\/\/invisioncommunity.com\/release-notes\/","order":14,"original":"release-notes","delete":0}],"4":[{"id":16,"name":"Applications","parent":4,"type":"int","url":"app=core&module=applications&controller=applications","order":15,"original":"applications","delete":0},{"id":17,"name":"Plugins","parent":4,"type":"int","url":"app=core&module=applications&controller=plugins","order":16,"original":"plugins","delete":0},{"id":18,"name":"Logs","parent":4,"type":"int","url":"app=core&module=support&controller=systemLogs","order":17,"original":"logs","delete":0},{"id":19,"name":"Task","parent":4,"type":"int","url":"app=core&module=settings&controller=advanced&do=tasks","order":18,"original":"task","delete":0},{"id":20,"name":"SQL Toolbox","parent":4,"type":"int","url":"app=core&module=support&controller=sql","order":19,"original":"sql-toolbox","delete":0},{"id":21,"name":"Support","parent":4,"type":"int","url":"app=core&module=support&controller=support","order":20,"original":"support","delete":0},{"id":22,"name":"Error Logs","parent":4,"type":"int","url":"app=core&module=support&controller=errorLogs","order":21,"original":"error-logs","delete":0},{"id":23,"name":"System Check","parent":4,"type":"int","url":"app=core&module=support&controller=support&do=systemCheck","order":22,"original":"system-check","delete":0},{"id":24,"name":"PHP Info","parent":4,"type":"int","url":"app=core&module=support&controller=support&do=phpinfo","order":23,"original":"php-info","delete":0}]}';

    public static function importMenus( $menus )
    {
        if( !is_array( $menus ) ) {
            $menus = json_decode( $menus, true );
        }

        if( is_array( $menus ) and count( $menus ) ) {
            if( isset( $menus[ 0 ] ) ) {
                $roots = $menus[ 0 ];

                foreach( $roots as $menu ) {
                    $id = $menu[ 'id' ];
                    unset( $menu[ 'id' ] );
                    $m = new \IPS\storm\Menu;
                    foreach( $menu as $key => $val ) {
                        $m->{$key} = $val;
                    }
                    $m->save();

                    if( isset( $menus[ $id ] ) ) {
                        $children = $menus[ $id ];
                        foreach( $children as $child ) {
                            unset( $child[ 'parent' ] );
                            unset( $child[ 'id' ] );

                            $c = new \IPS\storm\Menu;
                            $c->parent = $m->id;
                            foreach( $child as $k => $v ) {
                                $c->{$k} = $v;
                            }
                            $c->save();
                        }
                    }
                }

                \IPS\storm\Menu::kerching();

            }
        }
    }

    public static function addMenu( $menu )
    {
        $parent = $menu[ 'parent' ];

        try {

            if( $parent === 0 ) {
                $parent = 0;
            } else {
                $parent = \IPS\Db::i()->select( '*', 'storm_menu', [ 'menu_name = ?', $parent ] )->first();
                $parent = $parent[ 'menu_id' ];
            }

            unset( $menu[ 'parent' ] );
            $menus = new \IPS\storm\Menu;
            $menus->parent = $parent;
            foreach( $menu as $k => $m ) {
                $menus->{$k} = $m;
            }

            $menus->save();
        } catch( \Exception $e ) {
        }
    }
}