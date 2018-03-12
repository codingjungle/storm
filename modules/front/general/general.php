<?php

/**
 * @brief       General Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       2.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\modules\front\general;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * general
 */
class _general extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {

        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        // This is the default method if no 'do' parameter is specified
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
    protected function backtrace()
    {
        $bt = \IPS\Request::i()->id;
        $back = [];

        if( \IPS\Data\Store::i()->exists( 'storm_bt' ) )
        {
            $back = \IPS\Data\Store::i()->storm_bt;
        }

        $output = "Nothing found";

        if( isset( $back[ $bt ] ) )
        {
            $bt = $back[ $bt ];
            $bt[ 'backtrace' ] = str_replace( "\\\\", "\\", $bt[ 'backtrace' ] );
            $output = "<code>" . $bt[ 'query' ] . "</code><br><pre class=\"prettyprint lang-php \">" . $bt[ 'backtrace' ] . "</pre>";
        }

        \IPS\Output::i()->output = "<div class='ipsPad'>{$output}</div>";
    }

    protected function cache()
    {
        $bt = \IPS\Request::i()->id;
        $back = [];

        if( \IPS\Data\Store::i()->exists( 'storm_cache' ) )
        {
            $back = \IPS\Data\Store::i()->storm_cache;
        }

        $output = "Nothing found";

        if( isset( $back[ $bt ] ) )
        {
            $bt = $back[ $bt ];
            $bt[ 'backtrace' ] = str_replace( "\\\\", "\\", $bt[ 'backtrace' ] );
            $output = "<div>Type: " . $bt[ 'type' ] . "</div><div>Key: " . $bt[ 'key' ] . "</div><br><pre class='prettyprint lang-php'>" . $bt[ 'backtrace' ] . "</pre>";
        }

        \IPS\Output::i()->output = "<div class='ipsPad'>{$output}</div>";
    }

}
