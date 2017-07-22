<?php

/**
 * @brief       Sources Singleton
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Sources extends \IPS\Patterns\Singleton
{

    /**
     * @brief   Singleton Instances
     * @note    This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
     */
    protected static $instance = null;

    protected $member = null;

    protected $lang = null;

    protected $appKey = null;

    protected $app = null;

    public function __construct(){
        $this->member = \IPS\Member::loggedIn();
        $this->lang = $this->member->language();
        $this->appKey = \IPS\Request::i()->appKey;
        $this->app = \IPS\Application::load( $this->appKey );
    }

    public function form()
    {
        $form = \IPS\storm\Forms::i( $this->elements(), null, 'classes' );

        if( $vals = $form->values() )
        {
            $this->process( $vals, $app );
            $msg = $this->lang->addToStack( 'storm_class_created', false, [ 'sprintf' => [ $this->type, $this->className ] ] );
            $url = \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$app->directory}&tab=class");
            \IPS\Output::i() ->redirect( $url, $msg );
        }

        return $form;
    }

    protected function elements()
    {
        $el = [
            [
                'class' => "Select",
                'name' => "type",
                'default' => "select",
                'required' => true,
                'validation' => array( $this, 'selectCheck'),
                'options' => [
                    'options' => $this->classType(),
                    'toggles' => $this->toggles(),
                ],
            ],
            [
                'class' => "Text",
                'name' => 'namespace',
                'options' => [
                    'placeholder' => "Namespace",
                ],
                'prefix' => "IPS\\{$this->app->directory}\\",
            ],
            [
                'class' => "Text",
                'name' => "className",
                'required' => true,
                'options' => [
                    'placeholder' => 'Class Name',
                ],
                'validation' => array( $this, 'classCheck' ),
                'prefix' => '_'
            ],
            [
                'class' => "Text",
                'name' => "database",
                'appearRequired' => true,
                'prefix' => $this->app->directory . '_',
                'validation' => array( $this, 'noBlankCheck')
            ],
            [
                'name' => "prefix",
                'suffix' => "_"
            ],
            [
                'name' => "item_node_class",
                'appearRequired' => true,
                'prefix' => "IPS\\{$this->app->directory}\\",
                'validation' => array( $this, 'itemNodeCheck')
            ],
            [
                'name' => "extends",
                'validation' => array($this, 'extendsCheck'),
            ],
            [
                'class' => "Stack",
                'name' => "implements",
                'validation' => array( $this, 'implementsCheck'),
            ],
            [
                'class' => 'stack',
                'name' => 'traits',
                'validation' => array( $this, 'traitsCheck')
            ],
            [
                'name' => 'interfaceName',
                'validate' => [ $this, 'interfaceClassCheck']
            ],
            [
                'name' => 'traitName',
                'validate' => [ $this, 'traitClassCheck']
            ]
        ];
        $el[ 'prefix' ] = 'storm_class_';
        return $el;
    }

    protected function process($vals){}

    public function classCheck( $data )
    {

        $ns = \IPS\storm\Settings::mbUcfirst( \IPS\Request::i()->storm_class_namespace );
        $class = \IPS\storm\Settings::mbUcfirst( $data );
        if( $ns )
        {
            $class = "\\IPS\\" . $this->app->directory . "\\" . $ns . "\\" . $class;
        }
        else
        {
            $class = "\\IPS\\" . $this->app->directory . "\\" . $class;
        }

        if( $data != "Forms" )
        {
            if( class_exists( $class ) )
            {
                throw new \InvalidArgumentException( 'storm_classes_class_no_exist' );
            }
        }
    }

    public function traitClassCheck( $data ){
        $ns = \IPS\storm\Settings::mbUcfirst( \IPS\Request::i()->storm_class_namespace );
        $class = \IPS\storm\Settings::mbUcfirst( $data );
        if( $ns )
        {
            $class = "\\IPS\\" . $this->app->directory . "\\" . $ns . "\\" . $class;
        }
        else
        {
            $class = "\\IPS\\" . $this->app->directory . "\\" . $class;
        }
            if( trait_exists( $class ) )
            {
                throw new \InvalidArgumentException( 'storm_sources_trait_exists' );
            }
    }

    public function interfaceClassCheck( $data ){
            $ns = \IPS\storm\Settings::mbUcfirst( \IPS\Request::i()->storm_class_namespace );
            $class = \IPS\storm\Settings::mbUcfirst( $data );
            if( $ns )
            {
                $class = "\\IPS\\" . $this->app->directory . "\\" . $ns . "\\" . $class;
            }
            else
            {
                $class = "\\IPS\\" . $this->app->directory . "\\" . $class;
            }
            if( interface_exists( $class ) )
            {
                throw new \InvalidArgumentException( 'storm_sources_interface_exists' );
            }
    }

    public function noBlankCheck( $data )
    {
        if( !$data )
        {
            throw new \InvalidArgumentException( 'storm_classes_no_blank' );
        }
    }

    public function extendsCheck( $data )
    {
        if( $data and !class_exists( $data ) )
        {
            throw new \InvalidArgumentException( 'storm_classes_extended_class_no_exist' );
        }
    }

    public function implementsCheck( $data )
    {
        if( is_array( $data ) and count( $data ) )
        {
            foreach( $data as $implement )
            {
                if( !interface_exists( $implement ) )
                {
                    throw new \InvalidArgumentException( 'storm_classes_implemented_no_interface' );
                }
            }
        }
    }

    public function traitsCheck( $data ){
        if( is_array( $data ) and count( $data ) )
        {
            foreach( $data as $trait )
            {
                if( !trait_exists( $trait ) )
                {
                    $lang = $this->lang( 'storm_sources_no_trait', false, [ 'sprintf' => [ $trait ] ] );
                    $this->lang->parseOutputForDisplay($lang);
                    throw new \InvalidArgumentException($lang);
                }
            }
        }
    }

    public function itemNodeCheck( $data )
    {
        if( $data )
        {
            $class = "IPS\\{$this->app->directory}\\{$data}";
            if( !class_exists( $class ) )
            {
                throw new \InvalidArgumentException( 'storm_class_node_item_missing' );
            }
        }
    }

    public function selectCheck( $data )
    {
        if( $data == "select" )
        {
            throw new \InvalidArgumentException( 'storm_classes_type_no_selection' );
        }
    }

    protected function classType(){
        return [
            'select' => "Select Type",
            'normal' => "Class",
            'singleton' => "Singleton",
            'ar' => "Active Record",
            'model' => "Node",
            'item' => "Item",
            'comment' => "Comment",
            'interface' => 'Interface',
            'trait' => 'Trait',
            'forms' => "Forms Class",
        ];
    }

    protected function toggles(){
        return [
            'normal' => [
                'namespace',
                'className',
                'extends',
                'implements',
                'traits'
            ],
            'interface' => [
                'namespace',
                'interfaceName'
            ],
            'trait' => [
                'namespace',
                'traitName'
            ],
            'singleton' => [
                'namespace',
                'className',
                'implements',
                'traits'
            ],
            'ar' => [
                'namespace',
                'className',
                'database',
                'prefix',
                'traits'
            ],
            'model' => [
                'namespace',
                'className',
                'implements',
                'database',
                'prefix',
                'traits'
            ],
            'item' => [
                'namespace',
                'className',
                'implements',
                'item_node_class',
                'database',
                'prefix',
                'traits'
            ],
            'comment' => [
                'namespace',
                'className',
                'implements',
                'item_node_class',
                'database',
                'prefix',
                'traits'
            ],
        ];
    }
}
