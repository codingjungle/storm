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

namespace IPS\storm\Sources;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

abstract class _Compile extends \IPS\Patterns\Singleton
{
    protected static $instance = null;
    
    /**
     * @var \IPS\Application
     */
    protected $appNode = '';

    protected $blanks = \IPS\ROOT_PATH . '/applications/storm/sources/Sources/Blanks';

    protected $content = null;

    public function process( array $values, $app )
    {

        $this->appNode = $app;
        
        $this->directory = $this->appNode->directory;
       
        $this->application = $this->directory;
        
        $this->app = \IPS\storm\Settings::mbUcfirst($this->directory );
        
        $this->header = $this->buildHeader();
        foreach( $values as $key => $val ) {
            $key = \mb_strtolower( \mb_substr( $key, \mb_strlen( 'storm_class_' ) ) );
            $this->{$key} = $val;
        }
        
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
        } else if( isset($this->interfaceName) and $this->interfaceName )
        {
            $this->classname = \IPS\storm\Settings::mbUcfirst( $this->interfaceName );
        }
        else if( isset( $this->traitName ) and $this->traitName ){
            $this->classname = \IPS\storm\Settings::mbUcfirst( $this->traitName );
        }else{
            $this->classname = "Forms";
        }

        $this->classname_lower = \mb_strtolower( $this->classname );
        
        $ns = $this->namespace . "\\" . $this->className;

        if( $this->classname != "Forms" ) {
            if( class_exists( $ns ) ) {
                return;
            }
        }

        if( isset( $this->extends ) and $this->extends ) {
            $this->extends = "extends " . $this->extends;
        }
 
        $this->module = \mb_strtolower($this->classname);

        $this->permtype = $this->module;
        
        $this->content();
        
        $this->build();
        
        $this->writeFile( );
    }
    
    protected function build(){
        if( isset( $this->implements ) and is_array( $this->implements ) and count( $this->implements ) ) {
            $new = [];
            //lets loop thru it to add in ln's and lets get rid of any dupes
            foreach( $this->implements as $imp ) {
                $new[ $imp ] = $imp . "\n";
            }
        
            $this->implements = "implements " . rtrim( implode( ',', $new ) );
        }
    
        if( isset( $this->traits ) and is_array( $this->traits ) and count( $this->traits ) ){
            $this->traits = 'use '.rtrim( implode( ',', $this->traits ) );
        }
        
        $find = [];
        $replace = [];
        
        foreach( $this->data as $key => $val ){
            $find[] = '#'.$key.'#';
            $replace[] = $val;
        }
        
        $this->content = \str_replace( $find, $replace, $this->content);
    }
    
    abstract protected function content();
    
    protected function buildHeader(){
        return \file_get_contents( $this->blanks .'/'. "header.txt" );
    }

    protected function getFile( $file ){
        return \file_get_contents( $this->blanks.'/'.$file );
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
    
    protected function writeFile(){
    
        $dir = \IPS\ROOT_PATH . '/applications/' . $this->directory . '/sources/' . $this->getDir();
    
        $file = $this->classname . ".php";
        
        if( !file_exists( $dir ) ) {
            \mkdir( $dir, 0777, true );
        }
        
        \file_put_contents( $dir.'/'.$file, $this->content );
        \chmod( $dir.'/'.$file, 0777 );
        \IPS\storm\Proxyclass::i()->build( $dir.'/'.$file );
    }
}
