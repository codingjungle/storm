<?php

/**
 * @brief       Classes Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.3
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Classes extends \IPS\Patterns\Singleton
{

    public static $instance = null;

    protected $type = null;

    protected $app = null;

    protected $nameSpace = null;

    protected $className = null;

    protected $extends = '';

    protected $implements = '';

    protected $brief = null;

    protected $application = null;

    protected $blanks = null;

    protected $formVersion = "1.0.7";

    protected $prefix = '';

    protected $database = '';

    protected $nodeItemClass = '';

    public function form()
    {
        $app = \IPS\Application::load( \IPS\Request::i()->appKey );
        $form = \IPS\storm\Forms::i( $this->elements( $app ), null, 'classes' );

        if( $vals = $form->values() )
        {
            $this->process( $vals, $app );
            $msg = \IPS\Member::loggedIn()
                              ->language()
                              ->addToStack( 'storm_class_created', false,
                                  [ 'sprintf' => [ $this->type, $this->className ] ] );
            \IPS\Output::i()
                       ->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$app->directory}&tab=class" ),
                           $msg );
        }

        return $form;
    }

    protected function elements( \IPS\Application $app )
    {
        $class = function( $data ) use ( $app )
        {
            $class = \IPS\storm\Settings::mbUcfirst( \IPS\Request::i()->storm_class_namespace );
            $data = \IPS\storm\Settings::mbUcfirst( $data );
            if( $class )
            {
                $ns = "\\IPS\\" . $app->directory . "\\" . $class . "\\" . $data;
            }
            else
            {
                $ns = "\\IPS\\" . $app->directory . "\\" . $data;
            }

            if( $data != "Forms" )
            {
                if( class_exists( $ns ) )
                {
                    throw new \InvalidArgumentException( 'storm_classes_class_no_exist' );
                }
            }
        };

        $extends = function( $data )
        {
            if( $data and !class_exists( $data ) )
            {
                throw new \InvalidArgumentException( 'storm_classes_extended_class_no_exist' );
            }
        };

        $implements = function( $data )
        {
            if( is_array( $data ) and count( $data ) )
            {
                foreach( $data as $implement )
                {
                    if( !class_exists( $implement ) )
                    {
                        throw new \InvalidArgumentException( 'storm_classes_implemented_no_interface' );
                    }
                }
            }
        };

        $validate = function( $data )
        {
            if( $data == "select" )
            {
                throw new \InvalidArgumentException( 'storm_classes_type_no_selection' );
            }
        };

        $classType = [
            'select' => "Select Type",
            'normal' => "Class",
            'singleton' => "Singleton",
            'ar' => "Active Record",
            'model' => "Node",
            'item' => "Content Item",
            'comment' => "Content Item Comment",
            'forms' => "Forms Class",
        ];

        $itemNodeValidation = function( $data ) use ( $app )
        {
            if( $data )
            {
                $class = "IPS\\{$app->directory}\\{$data}";
                if( !class_exists( $class ) )
                {
                    throw new \InvalidArgumentException( 'storm_class_node_item_missing' );
                }
            }
        };

        $toggles = [
            'normal' => [
                'namespace',
                'className',
                'extends',
                'implements',
            ],
            'singleton' => [
                'namespace',
                'className',
                'implements',
            ],
            'ar' => [
                'namespace',
                'className',
                'database',
                'prefix'
            ],
            'model' => [
                'namespace',
                'className',
                'implements',
                'database',
                'prefix'
            ],
            'item' => [
                'namespace',
                'className',
                'implements',
                'item_node_class',
                'database',
                'prefix'
            ],
            'comment' => [
                'namespace',
                'className',
                'implements',
                'item_node_class',
                'database',
                'prefix'
            ],
        ];

        $el = [
            [
                'class' => "Select",
                'name' => "type",
                'default' => "select",
                'required' => true,
                'validation' => $validate,
                'options' => [
                    'options' => $classType,
                    'toggles' => $toggles,
                ],
            ],
            [
                'class' => "Text",
                'name' => 'namespace',
                'options' => [
                    'placeholder' => "Namespace",
                ],
                'prefix' => "IPS\\{$app->directory}\\",
            ],
            [
                'class' => "Text",
                'name' => "className",
                'required' => true,
                'options' => [
                    'placeholder' => 'Class Name',
                ],
                'validation' => $class,
            ],
            [
                'class' => "Text",
                'name' => "database",
                'appearRequired' => true,
                'prefix' => $app->directory . '_',
                'validation' => function( $data )
                {
                    if( !$data )
                    {
                        throw new \InvalidArgumentException( 'storm_classes_no_blank' );
                    }
                }
            ],
            [
                'class' => "Text",
                'name' => "prefix",
                'suffix' => "_"
            ],
            [
                'class' => "Text",
                'name' => "item_node_class",
                'appearRequired' => true,
                'prefix' => "IPS\\{$app->directory}\\",
                'validation' => $itemNodeValidation
            ],
            [
                'class' => "Text",
                'name' => "extends",
                'validation' => $extends,
            ],
            [
                'class' => "Stack",
                'name' => "implements",
                'validation' => $implements,
            ],
        ];
        $el[ 'prefix' ] = 'storm_class_';
        return $el;
    }

    public function process( $data, $app )
    {
        $this->blanks = \IPS\ROOT_PATH . "/applications/storm/sources/Classes/blanks/";
        $this->type = $data[ 'storm_class_type' ];
        $this->application = $app;
        $this->app = \IPS\storm\Settings::mbUcfirst( $app->directory );

        if( isset( $data[ 'storm_class_prefix' ] ) )
        {
            $this->prefix = $data[ 'storm_class_prefix' ] . "_";
        }

        if( isset( $data[ 'storm_class_database' ] ) )
        {
            $this->database = $app->directory.'_'.$data[ 'storm_class_database' ];
        }

        if( isset( $data[ 'storm_class_namespace' ] ) and $data[ 'storm_class_namespace' ] )
        {
            $this->nameSpace = 'IPS\\' . $app->directory . '\\' . $data[ 'storm_class_namespace' ];
        }
        else
        {
            $this->nameSpace = 'IPS\\' . $app->directory;
        }

        if( isset( $data[ 'storm_class_item_node_class' ] ) and $data[ 'storm_class_item_node_class' ] )
        {
            $nic = $data[ 'storm_class_item_node_class' ];
            $this->nodeItemClass = "IPS\\{$app->directory}\\{$nic}";
        }

        if( isset( $data[ 'storm_class_className' ] ) )
        {
            $this->className = \IPS\storm\Settings::mbUcfirst( $data[ 'storm_class_className' ] );
        }
        else
        {
            $this->className = "Forms";
        }

        $ns = $this->nameSpace . "\\" . $this->className;

        if( $this->className != "Forms" )
        {
            if( class_exists( $ns ) )
            {
                return;
            }
        }

        $this->brief = \IPS\storm\Settings::mbUcfirst( $app->directory );

        if( isset( $data[ 'storm_class_extends' ] ) and $data[ 'storm_class_extends' ] )
        {
            $this->extends = "extends " . $data[ 'storm_class_extends' ];
        }

        if( isset( $data[ 'storm_class_implements' ] ) and is_array( $data[ 'storm_class_implements' ] ) and count( $data[ 'storm_class_implements' ] ) )
        {
            //lets loop thru it to add in ln's and lets get rid of any dupes
            foreach( $data[ 'storm_class_implements' ] as $imp )
            {
                $new[ $imp ] = $imp . "\n";
            }

            $this->implements = "implements " . rtrim( implode( ',', $new ) );
        }

        $template = $this->{$this->type}();
        $dir = \IPS\ROOT_PATH . '/applications/' . $app->directory . '/sources/' . $this->getDir();
        $file = $this->className . ".php";
        $toWrite = $dir . '/' . $file;

        if( !file_exists( $dir ) )
        {
            \mkdir( $dir, 0777, true );
        }

        \file_put_contents( $toWrite, $template );
        \chmod( $toWrite, 0777 );
        \IPS\storm\Proxyclass::i()->build( $toWrite );
    }

    protected function getDir()
    {
        $ns = explode( '\\', $this->nameSpace );
        $ns = array_pop( $ns );

        if( $ns == $this->application->directory )
        {
            return $this->className;
        }
        else
        {
            if( $ns != $this->className )
            {
                return $ns;
            }
        }

        return $this->className;
    }

    protected function comment()
    {
        $path = $this->blanks . "comment.txt";

        return $this->build( $path, 'Content Comment Class' );
    }

    protected function build( $path, $brief )
    {
        $content = \file_get_contents( $path );

        return $this->replacementValues( $content, $brief );
    }

    protected function replacementValues( $content, $brief = "Class" )
    {
        $find = [
            '#header#',
            '#brief#',
            '#app#',
            '#applications#',
            '#namespace#',
            '#classname#',
            '#extends#',
            '#implements#',
            '#permtype#',
            '#database#',
            '#prefix#',
            '#nodeItemClass#',
            '#module#'
        ];

        $replacements = [
            $this->buildHeader( $brief ),
            $brief,
            $this->app,
            $this->application->directory,
            $this->nameSpace,
            $this->className,
            $this->extends,
            $this->implements,
            \mb_strtolower( $this->className ),
            $this->database,
            $this->prefix,
            $this->nodeItemClass,
            \mb_strtolower($this->className )
        ];

        return str_replace( $find, $replacements, $content );
    }

    protected function buildHeader( $brief )
    {
        $path = $this->blanks . "header.txt";
        $content = \file_get_contents( $path );
        if( $brief == "Forms Class" )
        {
            $content = str_replace( "*/", "* forms version {$this->formVersion}\n*/", $content );
        }
        return $content;
    }

    protected function item()
    {
        $path = $this->blanks . "item.txt";

        return $this->build( $path, 'Content Item Class' );
    }

    protected function model()
    {
        $path = $this->blanks . "model.txt";

        return $this->build( $path, 'Node' );
    }

    protected function ar()
    {
        $path = $this->blanks . "ar.txt";

        return $this->build( $path, 'Active Record' );
    }

    protected function normal()
    {
        $path = $this->blanks . "normal.txt";

        return $this->build( $path, 'Class' );
    }

    protected function forms()
    {
        $path = $this->blanks . "forms.txt";

        return $this->build( $path, 'Forms Class' );

    }

    protected function singleton()
    {
        $path = $this->blanks . "singleton.txt";

        return $this->build( $path, 'Singleton' );
    }

    protected function reservedKeywords(){
        $keywords = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor');

        $predefined_constants = array('__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__', '__TRAIT__');
    }
}
