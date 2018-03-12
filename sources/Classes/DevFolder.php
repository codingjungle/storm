<?php

/**
 * @brief       DevFolder Singleton
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.4
 */

namespace IPS\storm\Classes;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _DevFolder extends \IPS\Patterns\Singleton
{

    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $instance = null;

    protected $app = null;

    protected $blansk = null;

    public function form()
    {
        $this->app = \IPS\Application::load( \IPS\Request::i()->appKey );
        $this->blanks = \IPS\ROOT_PATH . "/applications/storm/sources/Classes/blanks/";

        $form = \IPS\storm\Forms::i( $this->elements() );

        if( $vals = $form->values() )
        {
            $this->process( $vals );
        }

        return $form;
    }

    protected function elements()
    {
        $e = [];
        $app = $this->app;
        $validate = function( $data )
        {
            if( $data == "select" )
            {
                throw new \InvalidArgumentException( 'storm_classes_type_no_selection' );
            }
        };
        $e[ 'prefix' ] = 'storm_devfolder_';

        $e[] = [
            'class' => "Select",
            'name' => "type",
            'default' => "select",
            'required' => true,
            'validation' => $validate,
            'appearRequired' => true,
            'options' => [
                'options' => $this->classTypes(),
                'toggles' => $this->toggles(),
            ],
        ];

        $e[] = [
            'class' => "Select",
            'name' => 'loc',
            'appearRequired' => true,
            'validation' => function( $data )
            {
                if( $data == "select" )
                {
                    throw new \InvalidArgumentException( 'storm_devfolder_loc_error' );
                }
            },
            'options' => [
                'options' => [ 'select' => "Select", 'admin' => 'Admin', 'front' => "Front", 'global' => "Global" ]
            ]
        ];

        $e[] = [
            'class' => "Text",
            'name' => 'group',
            'appearRequired' => true,
            'validation' => function( $data )
            {
                if( !$data )
                {
                    throw new \InvalidArgumentException( 'storm_devfolder_group_error' );
                }
            }
        ];

        $fileValidation = function( $data ) use ( $app )
        {
            if( !$data )
            {
                throw new \InvalidArgumentException( 'storm_devfolder_filename_error' );
            }

            $devFolder = \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/";
            $file = "{$data}";
            $type = \IPS\Request::i()->storm_devfolder_type;
            $loc = \IPS\Request::i()->storm_devfolder_loc;
            $group = \IPS\Request::i()->storm_devfolder_group;

            if( $type === "template" )
            {
                $location = '/' . $loc . '/' . $group . '/';
                $dir = $devFolder . "html" . $location;
                $fileName = $dir . "{$file}.phtml";
            }
            $js = [
                'jsModule',
                'jsWidget',
                'jsController'
            ];
            if( in_array( $type, $js ) )
            {
                $location = "/" . $loc . "/controllers/" . $group . "/";
                $dir = $devFolder . "js" . $location;
                $fileName = $dir . "{$file}.js";
            }

            if( in_array( $type, $js ) )
            {
                $location = "/" . $loc . "/controllers/" . $group . "/";
                $dir = $devFolder . "js" . $location;

                if( !file_exists( $dir ) )
                {
                    \mkdir( $dir, 0777, true );
                }

                $module = "ips.{$app->directory}.{$file}";
                $fileName = $dir . $module . ".js";

                if( $type === "jsController" )
                {
                    $fileName = "ips." . $group . $file . ".js";
                }
            }

            if( file_exists( $fileName ) )
            {
                throw new \InvalidArgumentException( 'storm_devfolder_filename_exist' );
            }
        };

        $e[] = [
            'class' => "Text",
            'name' => 'filename',
            'appearRequired' => true,
            'validation' => $fileValidation
        ];

        $e[] = [
            'class' => "Text",
            'name' => 'widgetname',
            'appearRequired' => true,
            'validation' => function( $data )
            {
                if( !$data )
                {
                    throw new \InvalidArgumentException( 'storm_devfolder_widgetname_error' );
                }
            }
        ];

        $e[] = [
            'class' => "Stack",
            'name' => "args"
        ];

        return $e;
    }

    protected function classTypes()
    {
        return [
            'select' => "Select",
            'template' => "Template",
            'jsWidget' => "JS Widget",
            'jsModule' => "JS Module",
            'jsController' => "JS Controller"
        ];
    }

    protected function toggles()
    {
        return [
            'template' => [
                'args',
                'loc',
                'group',
                'filename'
            ],
            'jsModule' => [
                'loc',
                'group',
                'filename'
            ],
            'jsWidget' => [
                'loc',
                'group',
                'filename',
                'widgetname'
            ],
            'jsController' => [
                'loc',
                'group',
                'filename'
            ],
        ];
    }

    protected function process( $vals )
    {
        $app = $this->app;
        $blanks = $this->blanks;
        $devFolder = \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/";
        $file = "{$vals['storm_devfolder_filename']}";

        if( $vals[ 'storm_devfolder_type' ] === "template" )
        {
            $location = "/{$vals['storm_devfolder_loc']}/{$vals['storm_devfolder_group']}/";
            $dir = $devFolder . "html" . $location;
            if( !file_exists( $dir ) )
            {
                \mkdir( $dir, 0777, true );
            }
            $fileName = $dir . "{$file}.phtml";
            $args = [];

            if( is_array( $vals[ 'storm_devfolder_args' ] ) )
            {
                foreach( $vals[ 'storm_devfolder_args' ] as $v )
                {
                    $args[] = "$" . str_replace( "$", "", $v );
                }

                $args = implode( ",", $args );
            }
            else
            {
                $args = '';
            }

            $content = \file_get_contents( $blanks . "template.txt" );
            $content = str_replace( "#args#", $args, $content );
        }

        $js = [
            'jsModule',
            'jsWidget',
            'jsController'
        ];

        if( in_array( $vals[ 'storm_devfolder_type' ], $js ) )
        {
            $location = "/{$vals['storm_devfolder_loc']}/controllers/{$vals['storm_devfolder_group']}/";
            $dir = $devFolder . "js" . $location;

            if( !file_exists( $dir ) )
            {
                \mkdir( $dir, 0777, true );
            }

            $module = "ips.{$app->directory}.{$file}";
            $fileName = $dir . $module . ".js";

            if( $vals[ 'storm_devfolder_type' ] === "jsModule" or $vals[ 'storm_devfolder_type' ] === "jsController" )
            {
                if( $vals[ 'storm_devfolder_type' ] === "jsModule" )
                {
                    $content = \file_get_contents( $blanks . "jsModule.txt" );
                }
                else if( $vals[ 'storm_devfolder_type' ] === "jsController" )
                {
                    $module = "{$app->directory}.{$vals['storm_devfolder_loc']}.{$vals['storm_devfolder_group']}.{$file}";
                    $fileName = $dir . "ips.{$vals['storm_devfolder_group']}.{$file}.js";
                    $content = \file_get_contents( $blanks . "jsController.txt" );
                }

                $content = str_replace( "#module#", $module, $content );
            }
            else if( $vals[ 'storm_devfolder_type' ] === "jsWidget" )
            {
                $content = \file_get_contents( $blanks . "jsWidget.txt" );
                $content = str_replace( [ '#widget#', '#widgetname#' ],
                    [ $module, $vals[ 'storm_devfolder_widgetname' ] ], $content );
            }
        }

        $msg = \IPS\Member::loggedIn()->language()->addToStack( 'created', false, [ 'sprintf' => [ $fileName ] ] );
        \file_put_contents( $fileName, $content );
        \chmod( $fileName, 0777 );
        \IPS\Output::i()
                   ->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$app->directory}&tab=DevFolder" ),
                       $msg );
    }
}
