<?php

/**
 * @brief       Proxyclass Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Proxyclass extends \IPS\Patterns\Singleton
{

    public static $instance = null;

    protected $save = 'proxyclasses';

    public function run( $data = [] )
    {
        $i = 0;
        $includes = \IPS\Request::i()->includes;

        if( isset( \IPS\Data\Store::i()->storm_proxyclass_files ) )
        {
            $iterator = \IPS\Data\Store::i()->storm_proxyclass_files;
            $totalFiles = $data[ 'total' ];
            $limit = 50;

            foreach( $iterator as $key => $file )
            {
                $i++;
                $filePath = $file[ 0 ];
                $this->build( $filePath, $includes );
                unset( $iterator[ $key ] );
                if( $i == $limit )
                {
                    break;
                }
            }

            \IPS\Data\Store::i()->delete( 'storm_proxyclass_files' );
        }

        if( $i )
        {
            if( is_array( $iterator ) and count( $iterator ) )
            {
                \IPS\Data\Store::i()->storm_proxyclass_files = $iterator;
            }

            if( $data[ 'current' ] )
            {
                $offset = $data[ 'current' ] + $i;
            }
            else
            {
                $offset = $i;
            }

            return [ 'total' => $totalFiles, 'current' => $offset, 'progress' => $data[ 'progress' ] ];
        }
        else
        {
            $this->buildConstants();
            return null;
        }
    }

    public function build( $file, $includes = 0)
    {
        $ds = DIRECTORY_SEPARATOR;

        $root = \IPS\ROOT_PATH;

        $save = $root . $ds . $this->save . $ds;

        if( !is_dir( $save ) )
        {
            return;
        }

        $content = \file_get_contents( $file );
        $content = \preg_replace( '!/\*.*?\*/!s', '', $content );
        $content = \preg_replace( '/\n\s*\n/', "\n", $content );
        preg_match( '#\$databaseTable(.*?)\=(.*?)[\'|"](.*?)[\'|"]\;#msu', $content, $match);
        $db = null;
        if( isset( $match[3] ) ){
            $db = $match[3];
        }
        \preg_match( '/namespace(.+?)([^\;]+)/', $content, $matched );

        $namespace = null;

        if( isset( $matched[ 0 ] ) )
        {
            $namespace = $matched[ 0 ];
        }
        $regEx = '#(?:(?<!\w))(?:[^\w]|\s+)(?:(?:(?:abstract|final|static)\s+)*)class\s+([-a-zA-Z0-9_]+)?#';

        $run = function( $matches ) use ( $namespace, $save, $db, $includes )
        {
            if( isset( $matches[ 1 ] ) )
            {
                if( mb_substr( $matches[ 1 ], 0, 1 ) === '_' )
                {
                    $content = '';
                    $append = \ltrim( $matches[ 1 ], '\\' );
                    $class = \str_replace( '_', '', \ltrim( $matches[ 1 ], '\\' ) );

                    $extra = '';
                    $testClass = \str_replace( 'namespace ', '', $namespace ) . '\\' . $class;
                    $isSettings = false;
                    //took less than 5 minutes to implement this 'ultra complex' code
                    try
                    {
                        if( $db and method_exists( $testClass, 'db' ) )
                        {
                            if( $testClass::db()->checkForTable( $testClass::$databaseTable ) )
                            {
                                $foo = $testClass::db()->getTableDefinition( $testClass::$databaseTable );
                                if( isset( $foo[ 'columns' ] ) )
                                {
                                    foreach( $foo[ 'columns' ] as $key => $val )
                                    {
                                        if( mb_substr( $key, 0, mb_strlen( $testClass::$databasePrefix ) ) == $testClass::$databasePrefix
                                        )
                                        {
                                            $key = mb_substr( $key, mb_strlen( $testClass::$databasePrefix ) );
                                        }
                                        $extra .= "public \${$key} = '';\n";
                                    }
                                }
                            }
                        }

                        if( $testClass === 'IPS\Settings' ){
                            $isSettings = true;
                            $load = $testClass::i()->getData();
                            foreach( $load as $key => $val ){
                                $extra .= "public \${$key} = '';\n";
                            }
                        }
                    }
                    catch( \Exception $e ){};

//                    if( !$isSettings ) {
                        $alt = \str_replace( [
                            "\\",
                            " ",
                            ";",
                        ], "_", $namespace );
//                    }
//                    else{
//                        $alt = 'IPS_Settings_lone';
//                    }
                    if( !\is_file( $save . $alt . '.php' ) )
                    {
                        $content = "<?php\n\n";

                        if( $namespace )
                        {
                            $content .= $namespace . ";\n";
                        }
                    }
                    $content .= str_replace( '_', '', $matches[ 0 ] ) . ' extends ' . $append . '{' . PHP_EOL . $extra . '}' . "\n";
                    $createdClass[ \str_replace( 'namespace ', '', $namespace ) ][] = $class;

                    \file_put_contents( $save . $alt . ".php", $content, FILE_APPEND );
                    \chmod( $save . $alt . ".php", 0777 );
                }
            }
        };
        preg_replace_callback( $regEx, $run, $content, 1 );

    }

    public function buildConstants(){
        $load = \IPS\IPS::defaultConstants();
        $ds = DIRECTORY_SEPARATOR;
        $root = \IPS\ROOT_PATH;
        $save = $root . $ds . $this->save . $ds;
        $extra = "\n";
        foreach( $load as $key => $val ){
            if( !is_numeric( $val ) ){
                $val = "'".$val."'";
            }
            $extra .= 'define( "IPS\\'.$key.'",'. $val.");\n";
        }
        $php = <<<EOF
<?php
{$extra}
EOF;
        \file_put_contents( $save .  "IPS_Constants_lone.php", $php );
        \chmod( $save .  "IPS_Constants_lone.php", 0777 );
    }

    public function generateSettings(){
        $ds = DIRECTORY_SEPARATOR;
        $root = \IPS\ROOT_PATH;
        $save = $root . $ds . $this->save . $ds;

        if( !file_exists($save .  "IPS_Settings_lone.php") )
        {
            return false;
        }

        $load = \IPS\Settings::i()->getData();
        $extra = "\n";
        foreach( $load as $key => $val ){
            $extra .= "public \${$key} = '';\n";
        }
        $php = <<<EOF
<?php

namespace IPS;

class Settings extends _Settings {
{$extra}
}
EOF;
        \file_put_contents( $save .  "IPS_Settings_lone.php", $php );
        \chmod( $save .  "IPS_Settings_lone.php", 0777 );
    }

    public function dirIterator()
    {
        $ds = DIRECTORY_SEPARATOR;
        $root = \IPS\ROOT_PATH;
        $save = $root . $ds . $this->save . $ds;

        if( \is_dir( $save ) )
        {
            $files = \glob( $save . "*" );

            foreach( $files as $file )
            {
                if( \is_file( $file ) )
                {
                    \unlink( $file );
                }
            }

            \rmdir( $save );
        }

        if( !is_dir( $save ) )
        {
            \mkdir( $save );
            \chmod( $save, 0777 );
        }

        $exclude = [
            $this->save,
            '.htaccess',
            'datastore',
            'plugins',
            'dev',
            'admin',
            'api',
            'interface',
            'uploads',
            'data',
            'extensions',
            'hooks',
            'setup',
            'modules',
            'tasks',
            'widgets',
            'Plugin',
            '3rdparty',
            '3rd_party',
            'themes',
            'conf_global.php',
            'index.php',
            'sitemap.php',
            'constants.php',
            'init.php',
            'error.php',
            '404error.php',
            'StormTemplates',
        ];

        $filter = function( $file, $key, $iterator ) use ( $exclude )
        {
            if( !\in_array( $file->getFilename(), $exclude ) )
            {
                return true;
            }

            return false;
        };

        $dirIterator = new \RecursiveDirectoryIterator(
            $root,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator( $dirIterator, $filter ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $iterator = new \RegexIterator( $iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );
        $iterator = iterator_to_array( $iterator );

        if( isset( \IPS\Data\Store::i()->storm_proxyclass_files ) )
        {
            unset( \IPS\Data\Store::i()->storm_proxyclass_files );
        }

        \IPS\Data\Store::i()->storm_proxyclass_files = $iterator;

        return count( $iterator );
    }
}