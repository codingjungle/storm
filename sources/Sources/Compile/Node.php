<?php

/**
 * @brief       Node Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       3.0.9
 * @version     -storm_version-
 */

namespace IPS\storm\Sources\Compile;

class _ActiveRecord extends \IPS\storm\Sources\Compile
{
    protected function content(){
        $this->brief = 'Node';
        
        if( is_array( $this->implements ) and in_array( '\\IPS\\Model\\Permissions', $this->implements ) ){
            $this->perms = $this->getFile( 'node/perms.txt');
        }
        else{
            $this->perms = '';
        }
        
        if( is_array( $this->implements ) and in_array( '\\IPS\\Model\\Ratings', $this->implements ) ){
            $this->ratings = $this->getFile( 'node/ratings.txt');
        }
        else{
            $this->ratings = '';
        }
        
        if( isset( $this->content_item_class ) and $this->content_item_class ){
            $this->itemclass = $this->getFile( 'node/itemclass.txt');
        }
        else{
            $this->itemclass = '';
        }
        
        if( isset( $this->subnode_class ) and $this->subnode_class ){
            $this->subnode = $this->getFile( 'node/subnode.txt');
        }
        else{
            $this->subnode = '';
        }
        
        $this->content = $this->getFile( 'node.txt' );
    }
}