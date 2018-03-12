<?php

/**
 * @brief       Sync Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.7
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
 * sync
 */
class _sync extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\storm\Sync';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'sync_manage' );
		parent::execute();
	}

	public function manage(){

	    $class = $this->nodeClass;
	    $top = $class::topElements();
        \IPS\Output::i()->sidebar[ 'actions' ][ 'sync' ] = [
            'icon'  => 'refresh',
            'title' => 'Sync',
            'link'  => \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=sync&do=sync' ),
        ];
	    \IPS\Output::i()->output .= $top;
	    parent::manage();


    }

    protected function sync()
    {
        \IPS\storm\Sync::send();
        \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=sync' ) );
    }
}