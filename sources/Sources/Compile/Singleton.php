<?php

/**
 * @brief       Singleton Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.9
 * @version     -storm_version-
 */

namespace IPS\storm\Sources\Compile;

class _Singleton extends \IPS\storm\Sources\Compile
{
    protected function content(){
        $this->brief = 'Singleton';
        $this->content = $this->getFile( 'singleton.txt' );
    }
}