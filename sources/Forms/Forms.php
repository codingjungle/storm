<?php

/**
 * @brief       Forms Forms Class
 * @author      <a href='http://codingjungle.com'>Michael Edwards</a>
 * @copyright   (c) 2017 Michael Edwards
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     3.0.4
 * forms version 1.0.6
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Forms
{
    /**
     * @brief multiton store
     */
    protected static $instance = [];

    /**
     * instantiate Forms class
     *
     * @param array $elements the array of elements to build
     * @param object $object a record element for the form
     * @param string $name name of the form
     * @param \IPS\Helpers\Form|null $form can pass an existing form object
     * @param string $id html id of the form
     * @param string $submitLang lang string for submit button
     * @param null $action where it post to
     * @param array $attributes any addition attributes that need to be pass
     *
     * @return mixed
     */
    public static function i( array $elements, $object = null, $name = 'default', $form = null, $id = 'form', $submitLang = 'save', $action = null, $attributes = [] )
    {
        if( !$name )
        {
            $name = md5( rand( 1, 100000 ) );
        }

        if( !isset( static::$instance[ $name ] ) )
        {
            $class = get_called_class();
            static::$instance[ $name ] = new $class();
            static::$instance[ $name ]->elements = $elements;
            static::$instance[ $name ]->obj = $object;

            if( $form instanceof \IPS\Helpers\Form )
            {
                static::$instance[ $name ]->form = $form;
            }
            else
            {
                static::$instance[ $name ]->form = new \IPS\Helpers\Form( $id, $submitLang, $action, $attributes );
            }

            if( $id )
            {
                static::$instance[ $name ]->form->id = $id;
            }
        }

        return static::$instance[ $name ]->run();
    }

    /**
     * @brief for use in run once the object is instantiated
     * @var \IPS\Helpers\Form|null
     */
    protected $form = null;

    /**
     * @brief form helpers store
     * @var array
     */
    protected $elements = [];

    /**
     * @brief the form record object
     * @var null
     **/
    protected $obj = null;

    /**
     * @brief the language prefix
     * @var null
     */
    protected $langPrefix = null;

    /**
     * @brief the class map for form elements
     * @var array
     */
    protected $classMap = [
        'address' => 'Address',
        'addy' => 'Address',
        'captcha' => 'Captcha',
        'checkbox' => 'Checkbox',
        'cb' => 'Checkbox',
        'checkboxset' => 'CheckboxSet',
        'cbs' => 'CheckboxSet',
        'codemirror' => 'Codemirror',
        'cm' => 'Codemirror',
        'color' => 'Color',
        'custom' => 'Custom',
        'date' => 'Date',
        'daterange' => 'DateRange',
        'dr' => 'DateRange',
        'editor' => 'Editor',
        'email' => 'Email',
        'ftp' => 'Ftp',
        'item' => 'Item',
        'keyvalue' => 'KeyValue',
        'kv' => 'KeyValue',
        'matrix' => 'Matrix',
        'member' => 'Member',
        'node' => 'Node',
        'number' => 'Number',
        '#' => 'Number',
        'password' => 'Password',
        'pw' => 'Password',
        'poll' => 'Poll',
        'radio' => 'Radio',
        'rating' => 'Rating',
        'search' => 'Search',
        'select' => 'Select',
        'socialgroup' => 'SocialGroup',
        'sg' => 'SocialGroup',
        'sort' => 'Sort',
        'stack' => 'Stack',
        'tel' => 'Tel',
        'text' => 'Text',
        'textarea' => 'TextArea',
        'ta' => 'TextArea',
        'timezone' => 'TimeZone',
        'translatable' => 'Translatable',
        'trans' => 'Translatable',
        'upload' => 'Upload',
        'up' => 'Upload',
        'url' => 'Url',
        'widthheight' => 'WidthHeight',
        'wh' => 'WidthHeight',
        'yesno' => 'YesNo',
        'yn' => 'YesNo'
    ];

    /**
     * _Forms constructor.
     */
    final protected function __construct()
    {
    }

    /**
     * executes and builds the form
     *
     * @return \IPS\Helpers\Form|null
     */
    public function run()
    {
        $langPrefix = '';
        if( isset( $this->elements[ 'prefix' ] ) )
        {
            $this->langPrefix = $langPrefix = $this->elements[ 'prefix' ];
            unset( $this->elements[ 'prefix' ] );
        }

        $typesWName = [
            'tab',
            'header',
            'sidebar',
            'helper',
            'dummy',
            'matrix',
            'hidden',
        ];

        foreach( $this->elements as $el )
        {
            if( !is_array( $el ) or !count( $el ) )
            {
                continue;
            }

            if( isset( $el[ 'type' ] ) )
            {
                $type = $el[ 'type' ];
            }
            else
            {
                $type = 'helper';
            }

            if( in_array( $type, $typesWName ) )
            {
                if( isset( $el[ 'name' ] ) )
                {
                    $name = $langPrefix . $el[ 'name' ];
                }
                else
                {
                    throw new \InvalidArgumentException( var_dump( $el ) );
                }
            }

            $this->setExtra( $el );

            switch( $type )
            {
                case 'tab':
                    $this->form->addTab( $langPrefix . $name . '_tab' );
                    break;
                case 'header':
                    $this->form->addHeader( $langPrefix . $name . '_header' );
                    break;
                case 'sidebar':
                    if( \IPS\Member::loggedIn()->language()->checkKeyExists( $name ) )
                    {
                        $element = \IPS\Member::loggedIn()->language()->addToStack( $name );
                    }

                    $this->form->addSidebar( $element );
                    break;
                case 'separator':
                    $this->form->addSeparator();
                    break;
                case 'message':
                    if( isset( $el[ 'msg' ] ) )
                    {
                        $lang = $el[ 'msg' ];
                    }
                    else
                    {
                        throw new \InvalidArgumentException;
                    }

                    $css = '';
                    if( isset( $el[ 'css' ] ) )
                    {
                        $css = $el[ 'css' ];
                    }

                    $parse = true;
                    if( isset( $el[ 'parse' ] ) )
                    {
                        $parse = $el[ 'parse' ] ? true : false;
                    }

                    $id = null;
                    if( isset( $el[ 'id' ] ) )
                    {
                        $id = $el[ 'id' ];
                    }

                    $this->form->addMessage( $lang, $css, $parse, $id );
                    break;
                case 'helper':
                    if( !isset( $el[ 'customClass' ] ) )
                    {
                        if( isset( $el[ 'class' ] ) )
                        {
                            $class = $el[ 'class' ];

                            if( isset( $this->classMap[ $class ] ) )
                            {
                                $class = $this->classMap[ $class ];
                            }

                            $class = '\\IPS\\Helpers\\Form\\' . $class;
                        }
                        else
                        {
                            $class = '\\IPS\\Helpers\\Form\\Text';
                        }
                    }
                    else
                    {
                        $class = $el[ 'customClass' ];
                    }

                    if( !class_exists( $class, true ) )
                    {
                        throw new \InvalidArgumentException( json_encode( $el ) );
                    }
                    $default = null;

                    if( is_object( $this->obj ) )
                    {
                        $obj = $this->obj;
                        $prop = $el[ 'name' ];
                        if( $obj->{$prop} )
                        {
                            $default = $obj->{$prop};
                        }
                        else
                        {
                            $prop = $langPrefix . $prop;
                            if( $obj->{$prop} )
                            {
                                $default = $obj->{$prop};
                            }
                        }

                        if( $default == null )
                        {
                            if( isset( $el[ 'default' ] ) or isset( $el[ 'def' ] ) )
                            {
                                $default = isset( $el[ 'default' ] ) ? $el[ 'default' ] : $el[ 'def' ];
                            }
                        }
                    }
                    else
                    {
                        if( isset( $el[ 'default' ] ) or isset( $el[ 'def' ] ) )
                        {
                            $default = isset( $el[ 'default' ] ) ? $el[ 'default' ] : $el[ 'def' ];
                        }
                    }

                    $required = false;
                    if( isset( $el[ 'required' ] ) )
                    {
                        $required = $el[ 'required' ];
                    }

                    $options = [];
                    if( isset( $el[ 'options' ] ) )
                    {
                        $options = $el[ 'options' ];
                    }
                    else if( isset( $el[ 'ops' ] ) )
                    {
                        $options = $el[ 'ops' ];
                    }

                    if( \is_array( $options ) and \count( $options ) )
                    {
                        if( isset( $options[ 'toggles' ] ) )
                        {
                            foreach( $options[ 'toggles' ] as $key => $val )
                            {
                                foreach( $val as $k => $v )
                                {
                                    $options[ 'toggles' ][ $key ][ $k ] = 'js_' . $langPrefix . $v;
                                }
                            }
                        }

                        if( isset( $options[ 'togglesOn' ] ) )
                        {
                            foreach( $options[ 'togglesOn' ] as $key => $val )
                            {
                                $options[ 'togglesOn' ][] = 'js_' . $langPrefix . $val;
                            }
                        }

                        if( isset( $options[ 'togglesOff' ] ) )
                        {
                            foreach( $options[ 'togglesOff' ] as $key => $val )
                            {
                                $options[ 'togglesOff' ][] = 'js_' . $langPrefix . $val;
                            }
                        }
                    }

                    $validation = null;
                    if( isset( $el[ 'validation' ] ) )
                    {
                        $validation = $el[ 'validation' ];
                    }
                    else if( isset( $el[ 'v' ] ) )
                    {
                        $validation = $el[ 'v' ];
                    }

                    $prefix = null;
                    if( isset( $el[ 'prefix' ] ) )
                    {
                        $prefix = $el[ 'prefix' ];
                    }

                    $suffix = null;
                    if( isset( $el[ 'suffix' ] ) )
                    {
                        $suffix = $el[ 'suffix' ];
                    }

                    $id = null;
                    if( isset( $el[ 'id' ] ) )
                    {
                        $id = $el[ 'id' ];
                    }
                    else
                    {
                        if( !isset( $el[ 'skip_id' ] ) )
                        {
                            $id = "js_" . $name;
                        }
                    }

                    $element = new $class( $name, $default, $required, $options, $validation, $prefix, $suffix, $id );

                    if( isset( $el[ 'appearRequired' ] ) or isset( $el[ 'ap' ] ) )
                    {
                        $element->appearRequired = true;
                    }

                    if( isset( $el[ 'label' ] ) )
                    {
                        $element->label = $el[ 'label' ];
                    }

                    if( isset( $el[ 'description' ] ) )
                    {
                        $desc = $el[ 'description' ];
                        if( \IPS\Member::loggedIn()->language()->checkKeyExists( $desc ) )
                        {
                            if( isset( $el[ 'desc_sprintf' ] ) )
                            {
                                $sprintf = $el[ 'desc_sprintf' ];
                                if( !is_array( $sprintf ) )
                                {
                                    $sprintf = [ $sprintf ];
                                }
                                $desc = \IPS\Member::loggedIn()
                                                   ->language()
                                                   ->addToStack( $desc, false, [ 'sprintf' => $sprintf ] );
                            }
                            else
                            {
                                $desc = \IPS\Member::loggedIn()->language()->addToStack( $desc );
                            }
                        }

                        \IPS\Member::loggedIn()->language()->words[ $name . '_desc' ] = $desc;
                    }

                    $tab = null;
                    $after = null;

                    if( isset( $el[ 'tab' ] ) )
                    {
                        $tab = $langPrefix . $el[ 'tab' ] . '_tab';
                    }

                    if( isset( $el[ 'after' ] ) )
                    {
                        $after = $langPrefix . $el[ 'after' ];
                    }

                    $this->form->add( $element, $after, $tab );
                    break;
                case 'dummy':
                    $default = null;
                    if( isset( $el[ 'default' ] ) )
                    {
                        $default = $el[ 'default' ];
                    }

                    $desc = '';
                    if( isset( $el[ 'desc' ] ) )
                    {
                        if( \IPS\Member::loggedIn()->language()->checkKeyExists( $el[ 'desc' ] ) )
                        {
                            $desc = \IPS\Member::loggedIn()->language()->addToStack( $el[ 'desc' ] );
                        }
                        else
                        {
                            $desc = $el[ 'desc' ];
                        }
                    }

                    $warning = '';

                    if( isset( $el[ 'warning' ] ) )
                    {
                        if( \IPS\Member::loggedIn()->language()->checkKeyExists( $el[ 'warning' ] ) )
                        {
                            $warning = \IPS\Member::loggedIn()->language()->addToStack( $el[ 'warning' ] );
                        }
                        else
                        {
                            $warning = $el[ 'warning' ];
                        }
                    }

                    if( isset( $el[ 'id' ] ) )
                    {
                        $id = $el[ 'id' ];
                    }
                    else
                    {
                        $id = $name . "_js";
                    }

                    $this->form->addDummy( $name, $default, $desc, $warning, $id );
                    break;
                case 'html':
                    if( !isset( $el[ 'html' ] ) )
                    {
                        throw new \InvalidArgumentException;
                    }
                    $this->form->addHtml( $el[ 'html' ] );
                    break;
                case 'matrix':
                    if( isset( $el[ 'matrix' ] ) )
                    {
                        if( !( $el[ 'matrix' ] instanceof \IPS\Helpers\Form\Matrix ) )
                        {
                            throw new \InvalidArgumentException;
                        }
                    }

                    $this->form->addMatrix( $name, $el[ 'matrix' ] );
                    break;
                case 'hidden':
                    $this->form->hiddenValues[ $name ] = $el[ 'default' ];
                    break;
            }
        }

        return $this->form;
    }

    /**
     * adds a header/tab/sidebar to an element
     * @param $el
     */
    final protected function setExtra( $el )
    {

        if( isset( $el[ 'header' ] ) )
        {
            $this->form->addHeader( $this->langPrefix . $el[ 'header' ] . '_header' );
        }

        if( isset( $el[ 'tab' ] ) )
        {
            $this->form->addTab( $this->langPrefix . $el[ 'tab' ] . '_tab' );
        }

        if( isset( $el[ 'sidebar' ] ) )
        {
            $sideBar = $this->langPrefix . $el[ 'sidebar' ] . '_sidebar';
            if( \IPS\Member::loggedIn()->language()->checkKeyExists( $sideBar ) )
            {
                $sideBar = \IPS\Member::loggedIn()->language()->addToStack( $sideBar );
            }

            $this->form->addSidebar( $sideBar );
        }

    }
}