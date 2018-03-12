<?php

/**
 * @brief       Menu Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.2
 * @version     -storm_version-
 */

namespace IPS\storm\modules\admin\configuration;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * menu
 */
class _menu extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\storm\Menu';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'menu_manage' );
		parent::execute();
	}

	protected function foo(){
        $sql = \IPS\Db::i()->select( '*', 'storm_menu', null, 'menu_order asc' );
        $menus = new \IPS\Patterns\ActiveRecordIterator( $sql, 'IPS\storm\Menu');
        $store = [];
        foreach( $menus as $menu ){
            $store[ $menu->parent ][] = $menu->foo();
        }

        print_r( json_encode( $store ) );exit;
    }
    protected function reorder()
    {
        $parent = parent::reorder();

        if (\IPS\Request::i()->isAjax()) {
            \IPS\storm\Menu::kerching();
        }

        return $parent;
    }
}