<?php

/**
 * @brief       Upgrade Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.7
 * @version     -storm_version-
 */

namespace IPS\storm\setup\upg_30007;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 3.0.7 Upgrade Code
 */
class _Upgrade
{
	/**
	 * ...
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{

        $menu = [
            'parent' => 'Storm',
            'name' => 'Sync',
            'type' => 'int',
            'url' => 'app=storm&module=configuration&controller=sync',
            'original' => 'sync'
        ];
        \IPS\storm\Menu::addMenu($menu);
        \IPS\storm\Menu::kerching();

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}