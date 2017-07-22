<?php

/**
 * @brief       Base Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Sources\Types;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Base extends \IPS\Patterns\Singleton
{

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
     */
    protected static $instance = null;

    /**
     * @var \IPS\Application
     */
    protected $appNode = '';

    protected $blanks = \IPS\ROOT_PATH . '/applications/storm/sources/Sources/blanks';

    protected $content = null;


    /* this is here or the autoloader will spaz out */
    public function __construct( array $values, $app )
    {
        $this->appNode = $app;
        $this->directory = $this->appNode->directory;
        $this->brief = $this->classType;
        foreach( $values as $key => $val ) {
            $key = mb_substr( $key, mb_strlen( 'storm_class_' ) );
            $this->data[ $key ] = $val;
        }

        $this->process();
    }

    protected function process()
    {
        if( isset( $this->prefix ) and $this->prefix ) {
            $this->prefix = $this->prefix . "_";
        }

        if( isset( $this->database ) and $this->database ) {
            $this->database = $this->directory . '_' . $this->database;
        }

        if( isset( $this->namespace ) and $this->namespace ) {
            $this->namespace = 'IPS\\' . $this->directory . '\\' . $this->namespace;
        } else {
            $this->namespace = 'IPS\\' . $this->directory;
        }

        if( isset( $this->item_node_class ) and $this->item_node_class ) {
            $this->item_node_class = 'IPS\\' . $this->directory . '\\' . $this->item_node_class;
        }

        if( isset( $this->classname ) and $this->classname ) {
            $this->classname = \IPS\storm\Settings::mbUcfirst( $this->classname );
        } else {
            $this->classname = "Forms";
        }

        $ns = $this->namespace . "\\" . $this->className;

        if( $this->classname != "Forms" ) {
            if( class_exists( $ns ) ) {
                return;
            }
        }

        $this->brief = \IPS\storm\Settings::mbUcfirst( $this->directory );

        if( isset( $this->extends ) and $this->extends ) {
            $this->extends = "extends " . $this->extends;
        }

        if( isset( $this->implements ) and is_array( $this->implements ) and count( $this->implements ) ) {
            $new = [];
            //lets loop thru it to add in ln's and lets get rid of any dupes
            foreach( $this->implements as $imp ) {
                $new[ $imp ] = $imp . "\n";
            }

            $this->implements = "implements " . rtrim( implode( ',', $new ) );
        }

        $this->module = \mb_strtolower($this->classname);

        $this->permtype = $this->module;

        $this->application = $this->directory;

        $this->app = \IPS\storm\Settings::mbUcfirst($this->directory );

        $this->header = $this->buildHeader();

        $type = $this->type;
        $this->{$type}();
        $this->compile();
        $dir = \IPS\ROOT_PATH . '/applications/' . $this->directory . '/sources/' . $this->getDir();

        $file = $this->classname . ".php";

        $toWrite = $dir . '/' . $file;

        if( !file_exists( $dir ) ) {
            \mkdir( $dir, 0777, true );
        }

        \file_put_contents( $toWrite, $this->content );
        \chmod( $toWrite, 0777 );
        \IPS\storm\Proxyclass::i()->build( $toWrite );
    }

    protected function compile(){
        $find = [];
        $replace = [];
        foreach( $this->data as $key => $val ){
            $find[] = $key;
            $replace[] = $val;
        }

        $this->content = \str_replace( $find, $replace, $this->content);

    }

    protected function ar(){
        $this->brief = 'Active Record';
        $this->content = \file_get_contents( $this->blanks.'/ar.txt' );
    }

    protected function buildHeader(){
        return \file_get_contents( $this->blanks . "header.txt" );
    }

    protected function getDir()
    {
        $ns = \str_replace( 'IPS\\'.$this->directory.'\\', '', $this->namespace );
        $ns = \explode( '\\', $ns );
        $ns = \implode( '/',$ns );

        if( $ns == $this->directory ) {
            return $this->classname;
        } else {
            if( $ns != $this->classname ) {
                return $ns;
            }
        }

        return $this->classname;
    }
}