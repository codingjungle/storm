<?php

/**
 * @brief       Apps Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Apps
{

    protected static $instance = null;
    public $addToStack = false;
    protected $app = null;
    protected $dir = null;
    protected $dev = null;

    final public function __construct( $app )
    {
        if( !( $app instanceof \IPS\Application ) ) {
            $this->app = \IPS\Application::load( $app );
        } else {
            $this->app = $app;
        }

        $this->dir = \IPS\ROOT_PATH . "/applications/" . $this->app->directory;
        $this->dev = $this->dir . '/dev/';

        if( !is_dir( $this->dev ) ) {
            mkdir( $this->dev, 0777, true );
        }
    }

    public static function i( $app )
    {
        if( static::$instance === null ) {
            static::$instance = new static( $app );
        }

        return static::$instance;
    }

    public function javascript()
    {
        $order = [];
        $js = $this->dev . 'js/';

        if( !is_dir( $js ) ) {
            mkdir( $js, 0777, true );
        }

        $xml = new \IPS\Xml\XMLReader;
        $xml->open( $this->dir . '/data/javascript.xml' );
        $xml->read();

        while( $xml->read() ) {
            if( $xml->nodeType != \XMLReader::ELEMENT ) {
                continue;
            }

            if( $xml->name == 'file' ) {
                $loc = $js . $xml->getAttribute( 'javascript_location' );
                $path = $loc . '/' . $xml->getAttribute( 'javascript_path' );
                $file = $path . '/' . $xml->getAttribute( 'javascript_name' );
                $order[ $path ][ $xml->getAttribute( 'javascript_position' ) ] = $xml->getAttribute( 'javascript_name' );
                $content = $xml->readString();

                if( !is_dir( $loc ) ) {
                    mkdir( $loc, 0777, true );
                }

                if( !is_dir( $path ) ) {
                    mkdir( $path, 0777, true );
                }

                \file_put_contents( $file, $content );
            }
        }

        $txt = 'order.txt';

        if( is_array( $order ) and count( $order ) ) {
            foreach( $order as $key => $val ) {
                $file = $key . '/' . $txt;
                $content = '';

                if( is_array( $val ) and count( $val ) ) {
                    ksort( $val );
                    foreach( $val as $k => $v ) {
                        $content .= $v . PHP_EOL;
                    }
                }

                \file_put_contents( $file, $content );
            }
        }

        return 'storm_apps_return_javascript';
    }

    public function templates()
    {
        $cssDir = $this->dev . 'css';
        $html = $this->dev . 'html';
        $resources = $this->dev . 'resources';

        if( !is_dir( $cssDir ) ) {
            mkdir( $cssDir, 0777, true );
        }
        if( !is_dir( $html ) ) {
            mkdir( $html, 0777, true );
        }
        if( !is_dir( $resources ) ) {
            mkdir( $resources, 0777, true );
        }

        $xml = new \IPS\Xml\XMLReader;
        $xml->open( $this->dir . '/data/theme.xml' );
        $xml->read();

        while( $xml->read() ) {
            if( $xml->nodeType != \XMLReader::ELEMENT ) {
                continue;
            }

            if( $xml->name == 'template' ) {
                $template = [
                    'group'     => $xml->getAttribute( 'template_group' ),
                    'name'      => $xml->getAttribute( 'template_name' ),
                    'variables' => $xml->getAttribute( 'template_data' ),
                    'content'   => $xml->readString(),
                    'location'  => $xml->getAttribute( 'template_location' ),
                ];

                $location = $html . '/' . $template[ 'location' ] . '/';
                $path = $location . $template[ 'group' ] . '/';
                $file = $path . $template[ 'name' ] . '.phtml';

                if( !is_dir( $location ) ) {
                    mkdir( $location, 0777, true );
                }

                if( !is_dir( $path ) ) {
                    mkdir( $path, 0777, true );
                }

                $header = '<ips:template parameters="' . $template[ 'variables' ] . '" />' . PHP_EOL;
                $content = $header . $template[ 'content' ];
                \file_put_contents( $file, $content );
            } else {
                if( $xml->name == 'css' ) {
                    $css = [
                        'location' => $xml->getAttribute( 'css_location' ),
                        'path'     => $xml->getAttribute( 'css_path' ),
                        'name'     => $xml->getAttribute( 'css_name' ),
                        'content'  => $xml->readString(),
                    ];

                    $location = $cssDir . '/' . $css[ 'location' ] . '/';

                    if( !is_dir( $location ) ) {
                        mkdir( $location, 0777, true );
                    }

                    if( $css[ 'path' ] === '.' ) {
                        $path = $location;
                    } else {
                        $path = $location . $css[ 'path' ] . '/';
                        if( !is_dir( $path ) ) {
                            mkdir( $path, 0777, true );
                        }
                    }

                    $file = $path . $css[ 'name' ];
                    \file_put_contents( $file, $css[ 'content' ] );
                } else {
                    if( $xml->name == 'resource' ) {
                        $resource = [
                            'location' => $xml->getAttribute( 'location' ),
                            'path'     => $xml->getAttribute( 'path' ),
                            'name'     => $xml->getAttribute( 'name' ),
                            'content'  => base64_decode( $xml->readString() ),
                        ];

                        $location = $resources . '/' . $resource[ 'location' ] . '/';
                        $path = $location . $resource[ 'path' ] . '/';
                        $file = $path . $resource[ 'name' ];

                        if( !is_dir( $location ) ) {
                            mkdir( $location, 0777, true );
                        }

                        if( !is_dir( $path ) ) {
                            mkdir( $path, 0777, true );
                        }

                        \file_put_contents( $file, $resource[ 'content' ] );
                    }
                }
            }
        }

        return 'storm_apps_return_templates';
    }

    public function email()
    {
        $email = $this->dev . 'email/';

        if( !is_dir( $email ) ) {
            mkdir( $email, 0777, true );
        }

        $xml = new \IPS\Xml\XMLReader;
        $xml->open( $this->dir . '/data/emails.xml' );
        $xml->read();

        while( $xml->read() and $xml->name == 'template' ) {
            if( $xml->nodeType != \XMLReader::ELEMENT ) {
                continue;
            }

            $insert = [];

            while( $xml->read() and $xml->name != 'template' ) {
                if( $xml->nodeType != \XMLReader::ELEMENT ) {
                    continue;
                }

                switch( $xml->name ) {
                    case 'template_name':
                        $insert[ 'template_name' ] = $xml->readString();
                        break;
                    case 'template_data':
                        $insert[ 'template_data' ] = $xml->readString();
                        break;
                    case 'template_content_html':
                        $insert[ 'template_content_html' ] = $xml->readString();
                        break;
                    case 'template_content_plaintext':
                        $insert[ 'template_content_plaintext' ] = $xml->readString();
                        break;
                }
            }

            $header = '<ips:template parameters="' . $insert[ 'template_data' ] . '" />' . PHP_EOL;

            if( isset( $insert[ 'template_content_plaintext' ] ) ) {
                $plainText = $header . $insert[ 'template_content_plaintext' ];
                \file_put_contents( $email . $insert[ 'template_name' ] . '.txt', $plainText );
            }

            if( isset( $insert[ 'template_content_html' ] ) ) {
                $plainText = $header . $insert[ 'template_content_html' ];
                \file_put_contents( $email . $insert[ 'template_name' ] . '.phtml', $plainText );
            }
        }

        return 'storm_apps_return_email';
    }

    public function language()
    {
        $xml = new \IPS\Xml\XMLReader;
        $xml->open( $this->dir . "/data/lang.xml" );
        $xml->read();
        $xml->read();
        $xml->read();
        $lang = [];
        $langJs = [];
        $member = \IPS\Member::loggedIn()->language();
        /* Start looping through each word */
        while( $xml->read() ) {
            if( $xml->name != 'word' OR $xml->nodeType != \XMLReader::ELEMENT ) {
                continue;
            }

            $key = $xml->getAttribute( 'key' );
            $value = $xml->readString();
            $js = (int)$xml->getAttribute( 'js' );
            $lang[ $key ] = $value;

            if( $js ) {
                $langJs[ $key ] = $value;
            }
            if( $this->addToStack ) {
                $member->words[ $key ] = $value;
            }
        }

        \file_put_contents( $this->dev . "lang.php", "<?php\n\n \$lang = " . var_export( $lang, true ) . ";\n" );
        \file_put_contents( $this->dev . "jslang.php", "<?php\n\n \$lang = " . var_export( $langJs, true ) . ";\n" );

        return 'storm_apps_return_lang';
    }
}