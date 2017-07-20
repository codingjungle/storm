<?php

/**
 * @brief       Application Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.3
 * @version     -storm_version-
 */

namespace IPS\storm;

/**
 * Fixer Application Class
 */
class _Application extends \IPS\Application
{
    public function acpMenu()
    {
        if( \IPS\IN_DEV )
        {
            $dir = \IPS\ROOT_PATH . "/applications/" . $this->directory . "/dev/";
            if( !file_exists( $dir ) )
            {
                $app = new \IPS\storm\Apps( $this );
                $app->addToStack = true;
                $app->email();
                $app->javascript();
                $app->language();
                $app->templates();
            }
        }

        return parent::acpMenu(); // TODO: Change the autogenerated stub
    }

    public function installOther()
    {
        \IPS\storm\Menu::importMenus( \IPS\storm\Menu::$installMenus );
        parent::installOther(); // TODO: Change the autogenerated stub
    }
}