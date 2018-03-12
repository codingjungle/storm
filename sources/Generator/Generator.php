<?php

/**
 * @brief       Generator Active Record
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.4
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Generator extends \IPS\Patterns\ActiveRecord
{
    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static $databasePrefix = 'generator_';

    /**
     * @brief    [ActiveRecord] ID Database Column
     */
    public static $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Database table
     */
    public static $databaseTable = 'storm_generator';

    /**
     * @brief   [ActiveRecord] Database ID Fields
     * @note    If using this, declare a static $multitonMap = array(); in the child class to prevent duplicate loading queries
     */
    protected static $databaseIdFields = [ 'id' ];

    /**
     * @brief   Bitwise keys
     */
    protected static $bitOptions = array();

    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $multitons;

    public static function create( $type, $id )
    {
        $d = new static;
        $d->type = $type;
        $d->gid = $id;
        $d->save();
    }

    public static function finished( $type )
    {
        if ( $type == 'members' OR $type == 'delete' )
        {
            foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_clubs' ), 'IPS\Member\Club' ) as $club )
            {
                $club->recountMembers();
            }
        }
    }

    public function process()
    {
        try
        {
            switch( $this->type )
            {
                case "members":
                    $d = \IPS\Member::load( $this->gid );
                    $d->delete();
                    break;
                case "forums":
                    $d = \IPS\forums\Forum::load( $this->gid );
                    $d->delete();
                    break;
                case "topics":
                    $d = \IPS\forums\Topic::load( $this->gid );
                    $d->delete();
                    break;
                case "posts":
                    $d = \IPS\forums\Topic\Post::load( $this->gid );
                    $d->delete();
                    break;
            }
        }
        catch( \Exception $e )
        {
        }

        $this->delete();
    }
}