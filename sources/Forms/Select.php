<?php

/**
 * @brief       Select Class
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.4
 */

namespace IPS\storm\Forms;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Select extends \IPS\Helpers\Form\Select
{
    /**
     * Validate
     *
     * @throws    \OutOfRangeException
     * @return    TRUE
     */
    public function validate()
    {
        return true;
    }
}