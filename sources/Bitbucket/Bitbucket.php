<?php

/**
 * @brief       Bitbucket Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.4
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Bitbucket extends \IPS\Patterns\ActiveRecord
{

    /**
     * @brief    [ActiveRecord] Database table
     */
    public static $databaseTable = 'storm_webhooks';

    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static $databasePrefix = "";

    /**
     * @brief    [ActiveRecord] ID Database Column
     */
    public static $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $multitons;

    /**
     * @brief    [ActiveRecord] Database ID Fields
     */
    protected static $databaseIdFields = [];

    /**
     * @brief    Bitwise keys
     */
    protected static $bitOptions = [];

    public static function createNewBitbucket()
    {
        $push = file_get_contents( 'php://input' );

        $push = json_decode( $push );

        $repo = $push->repository->name;

        $push = $push->push;

        foreach( $push->changes as $k => $val )
        {

            try
            {
                $info = $val->new;

                $new = new \IPS\storm\Bitbucket;

                $new->type = "Push";

                $new->link = $info->target->links->html->href;

                $new->repo = $repo;

                $new->hash = $info->target->hash;

                $new->message = $info->target->message;

                $new->branch = $info->name;

                $new->branchLink = $info->links->html->href;

                $new->username = $info->target->author->user->username;

                $new->displayname = $info->target->author->user->display_name;

                $time = strtotime( $info->target->date );

                $new->date = $time ?: \time();

                $new->save();
            }
            catch( \Exception $e )
            {
            }
        }
    }

    public function get_message()
    {
        return $this->_data[ 'message' ];
    }

    public function get_hash()
    {
        return mb_substr( $this->_data[ 'hash' ], 0, 7 );
    }

    public function get_branch()
    {
        if( $this->_data[ 'repo' ] === "babble" and $this->_data[ 'branch' ] == "2.1.4" )
        {
            return "2.2.0";
        }

        return $this->_data[ 'branch' ];
    }

    public function get_date()
    {
        return \IPS\DateTime::ts( $this->_data[ 'date' ] );
    }
}