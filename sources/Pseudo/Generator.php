<?php

/**
 * @brief       Forums Singleton
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.4
 */

namespace IPS\storm\Pseudo;

use IPS\nexus\Hosting\_Exception;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Generator extends \IPS\Patterns\Singleton
{

    use \IPS\storm\Pseudo\Data;

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
     */
    protected static $instance = null;

    protected $postData = [];

    final public function __construct()
    {
        $this->postData = \IPS\storm\Pseudo\PostData::getPostData();
    }

    protected function getForum()
    {
        $db = \IPS\Db::i()->select( "*", "forums_forums", [ 'parent_id = ?', -1 ], "RAND()" )->first();

        try
        {
            $db = \IPS\Db::i()->select( "*", "forums_forums", [ 'parent_id = ?', $db[ 'id' ] ], "RAND()" )->first();
            $rand = rand( 1, 10 );
            $rand = $rand / 3;
            if( is_int( $rand ) )
            {
                try
                {
                    $db = \IPS\Db::i()
                                 ->select( "*", "forums_forums", [ 'parent_id = ?', $db[ 'id' ] ], "RAND()" )
                                 ->first();
                }
                catch( \Exception $e )
                {
                }
            }
        }
        catch( \Exception $e )
        {
        }

        return \IPS\forums\Forum::constructFromData( $db );
    }

    protected function getTopic()
    {
        try
        {
            $dbFirst = \IPS\Db::i()->select( "*", "forums_topics", [], "id ASC" )->first();
            $dbLast = \IPS\Db::i()->select( "*", "forums_topics", [], "id DESC" )->first();
            $range = rand( $dbFirst[ 'id' ], $dbLast[ 'id' ] );
            $db = \IPS\Db::i()->select( "*", "forums_topics", [ 'id = ?', $range ], "id DESC" )->first();
        }
        catch( \Exception $e )
        {
            $db = \IPS\Db::i()->select( "*", "forums_topics", [], "RAND()" )->first();
        }
        return \IPS\forums\Topic::constructFromData( $db );
    }

    protected function getMember()
    {
        $db = \IPS\Db::i()->select( "*", "core_members", [], "RAND()" )->first();
        return \IPS\Member::constructFromData( $db );
    }

    public static function getTime( $date = null )
    {
        $rand = rand( 1, 3 );
        $cur = time();
        if( !$date )
        {
            $date = \IPS\Settings::i()->getFromConfGlobal( 'board_start' );
        }

        $diff = $cur - $date;

        switch( $rand )
        {
            case 1:
                $time = 60;
                $diff = round( $diff / $time);
                $foo = rand( 1, $diff );
                break;
            case 2:
                $time = 3600;
                $diff = round( $diff / $time);
                $foo = rand( 1, $diff );
                break;
            case 3:
                $time = 84000;
                $diff = round( $diff / $time);
                $foo = rand( 1, $diff );
                break;
        }

        $time = $date + ( $foo * $time );

        if( $time > $cur )
        {
            $time = $cur;
        }

        return $time;
    }

    public function generateForum( $category = true, $start = false )
    {
        $parent = null;
        if( $start )
        {
            try
            {
                $parent = \IPS\Db::i()->select( "*", "forums_forums", [], "RAND()" )->first();
                $parent = \IPS\forums\Forum::constructFromData( $parent );
                try {
                    $rand = rand( 1, 10);
                    if( \IPS\Db::i()->select( '*', 'forums_forums', [ 'parent_id = ?', $parent ] )->count() > $rand) {
                        throw new \Exception;
                    }
                }
                catch( \Exception $e ){
                    $category = false;
                }
            }
            catch( \Exception $e )
            {
                $parent = static::generateForum( true );
            }
        }

        $rand = \array_rand( $this->adjective, 1 );
        $rand2 = \array_rand( $this->noun, 1 );
        $name = \str_replace( "_", " ", $this->adjective[ $rand ] . " " . $this->noun[ $rand2 ] );
        $name = \ucwords( mb_strtolower( $name ) );
        $desc = $this->adjectiveGloss[ $rand ] . "; " . $this->nounGloss[ $rand2 ];
        $findType = ( $rand + $rand2 ) / 17;
        $type = "normal";
        $makeCat = $rand / 29;

        if( !$category )
        {
            if( $parent == null )
            {
                try
                {
                    $parent = $this->getForum();
                }
                catch( \Exception $e )
                {
                    $parent = static::generateForum( true );
                }
            }
            $parent = $parent->id;
            if( \is_int( $findType ) )
            {
                $type = "qa";
            }

        }
        else
        {
            $parent = -1;
            $type = "category";
        }

        $toSave = array(
            'forum_name' => $name,
            'forum_description' => $desc,
            'forum_type' => $type,
            'forum_parent_id' => $parent
        );

        if( $type == "qa" )
        {
            $toSave[ 'forum_preview_posts_qa' ] = [];
            $toSave[ 'forum_can_view_others_qa' ] = 1;
            $toSave[ 'forum_sort_key_qa' ] = "last_post";
            $toSave[ 'forum_permission_showtopic_qa' ] = 1;
        }
        else
        {
            $toSave[ 'forum_sort_key' ] = "last_post";
        }

        $f = new \IPS\forums\Forum;
        $f->saveForm( $f->formatFormValues( $toSave ) );

        $insert = [
            'app' => "forums",
            'perm_type' => "forum",
            "perm_type_id" => $f->id,
            "perm_view" => "*",
            "perm_2" => "*",
            "perm_3" => "*",
            "perm_4" => "*",
            "perm_5" => "*"
        ];

        \IPS\Db::i()->insert( 'core_permission_index', $insert );
        \IPS\storm\Generator::create( "forums", $f->id );
        if( $category )
        {
            return $f;
        }
        else if( $start )
        {
            $rand = rand( 1, 12 );

            for( $i = 0; $i < $rand; $i++ )
            {
                $this->generateTopic( $f );
            }
        }
    }

    public function generateTopic( \IPS\forums\Forum $forum = null )
    {
        if( !$forum )
        {
            $forum = $this->getForum();
        }

        $member = $this->getMember();
        $rand = array_rand( $this->adjective, 1 );
        $rand2 = array_rand( $this->noun, 1 );
        $name = str_replace( "_", " ", $this->adjective[ $rand ] . " " . $this->noun[ $rand2 ] );
        $name = \ucwords( mb_strtolower( $name ) );
        $time = static::getTime();

        $topic = \IPS\forums\Topic::createItem(
            $member,
            $member->ip_address,
            \IPS\DateTime::ts( $time ),
            $forum
        );

        $topic->title = $name;
        $topic->save();
        $post = $this->generatePost( $topic, $member, true );
        $topic->topic_firstpost = $post->pid;
        $topic->save();
        $posts = rand( 1, 12 );
        \IPS\storm\Generator::create( "topics", $topic->tid );
        for( $i = 0; $i < $posts; $i++ )
        {
            $this->generatePost( $topic );
        }
    }

    public function generatePost( \IPS\forums\Topic $topic = null, \IPS\Member $member = null, $first = false )
    {
        $rand = array_rand( $this->postData, 1 );
        $content = "<p>" . $this->postData[ $rand ] . "</p>";

        $double = $rand / 17;

        if( is_int( $double ) )
        {
            if( $rand == 421 )
            {
                $cur = 0;
            }
            else
            {
                $cur = $rand + 1;
            }

            $content .= "<p>" . $this->postData[ $cur ] . "</p>";
        }

        if( !$topic )
        {
            $topic = $this->getTopic();
            $comment = $topic->comments(1, 0, 'date', 'desc' );
            $time = $comment->post_date;
            $time = static::getTime( $time );
        }
        else
        {
            $time = $topic->start_date;

            if( !$first ) {
                $time = $topic->last_post;
                $time = static::getTime( $time );
            }
        }

        if( !$member )
        {
            $member = $this->getMember();
        }

        $post = \IPS\forums\Topic\Post::create(
            $topic,
            $content,
            $first,
            null,
            true,
            $member,
            \IPS\DateTime::ts( $time )
        );

        \IPS\storm\Generator::create( "posts", $post->pid );
        if( $first )
        {
            return $post;
        }
    }
}
