<?php

/**
 * @brief       Standard Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.9
 * @version     -storm_version-
 */

namespace IPS\storm\Sources\Compile;

class _Standard extends \IPS\storm\Sources\Compile
{
    protected function content(){
        $this->brief = 'Standard';
        $this->content = $this->getFile( 'standard.txt' );
    }
}