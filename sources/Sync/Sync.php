<?php

/**
 * @brief       Sync Node
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] :
            'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class _Sync extends \IPS\Node\Model
{
    /**
     * @brief    [ActiveRecord] Multiton Store
     */
    protected static $multitons;

    /**
     * @brief    [ActiveRecord] Default Values
     */
    protected static $defaultValues = null;

    /**
     * @brief    [ActiveRecord] Database Table
     */
    public static $databaseTable = 'storm_sync';

    /**
     * @brief    [ActiveRecord] Database Prefix
     */
    public static $databasePrefix = 'sync_';

    /**
     * @brief    [ActiveRecord] ID Database Column
     */
    public static $databaseColumnId = 'id';

    /**
     * @brief    [ActiveRecord] Database ID Fields
     */
    protected static $databaseIdFields = [ 'sync_id' ];

    /**
     * @brief    [Node] Order Database Column
     */
    public static $databaseColumnOrder = null;

    /**
     * @brief    [Node] Parent ID Database Column
     */
    public static $databaseColumnParent = null;

    /**
     * @brief   [Node] Parent ID Root Value
     * @note    This normally doesn't need changing though some legacy areas use -1 to indicate a root node
     */
    public static $databaseColumnParentRootValue = 0;

    /**
     * @brief    [Node] Enabled/Disabled Column
     */
    public static $databaseColumnEnabledDisabled = null;

    /**
     * @brief    [Node] Show forms modally?
     */
    public static $modalForms = false;

    /**
     * @brief    [Node] Node Title
     */
    public static $nodeTitle = 'Sync';

    /**
     * @brief    [Node] ACP Restrictions
     * @code
    array(
     * 'app'        => 'core',                // The application key which holds the restrictrions
     * 'module'    => 'foo',                // The module key which holds the restrictions
     * 'map'        => array(                // [Optional] The key for each restriction - can alternatively use
     * "prefix"
     * 'add'            => 'foo_add',
     * 'edit'            => 'foo_edit',
     * 'permissions'    => 'foo_perms',
     * 'delete'        => 'foo_delete'
     * ),
     * 'all'        => 'foo_manage',        // [Optional] The key to use for any restriction not provided in the map
     * (only needed if not providing all 4)
     * 'prefix'    => 'foo_',                // [Optional] Rather than specifying each  key in the map, you can specify
     * a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
     * @encode
     */
    protected static $restrictions = [
        'app'    => 'storm',
        'module' => 'sync',
        'prefix' => 'sync_',
    ];


    /**
     * @brief    Bitwise values for members_bitoptions field
     */
    public static $bitOptions = [
        'bitoptions' => [
            'bitoptions' => [],
        ],
    ];

    /**
     * @brief    [Node] Title search prefix.  If specified, searches for '_title' will be done against the language
     *           pack.
     */
    public static $titleSearchPrefix = null;

    /**
     * @brief    [Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as
     *           the key
     */
    public static $titleLangPrefix = null;

    /**
     * @brief    [Node] Prefix string that is automatically prepended to permission matrix language strings
     */
    public static $permissionLangPrefix = '';

    /**
     * @brief    [Node] Moderator Permission
     */
    public static $modPerm = '';

    /**
     * @brief    Follow Area Key
     */
    public static $followArea = '';

    /**
     * @brief    Cached URL
     */
    protected $_url = null;

    /**
     * @brief    URL Base
     */
    public static $urlBase = '';

    /**
     * @brief    URL Base
     */
    public static $urlTemplate = '';

    /**
     * @brief    SEO Title Column
     */
    public static $seoTitleColumn = null;

    /**
     * @brief    Content Item Class
     */
    public static $contentItemClass = null;


    public static function recieve()
    {
        $conf = \IPS\Settings::i();
        $keyHash = sha1( $conf->getFromConfGlobal( 'base_url' ) . $conf->getFromConfGlobal( 'board_start' ) );

        $key = \IPS\Request::i()->key;
        $app = \IPS\Request::i()->app;

        if( $key === $keyHash ) {
            $app = \IPS\Application::load( $app );
            $ftp = $conf->storm_ftp_path;
            $path = $ftp . '/' . $app->directory . '.tar';
            if( file_exists( $path ) ) {
                /* Test the phar */
                $application = new \PharData( $path, 0, null, \Phar::TAR );
                /* Get app directory */
                $appdata = json_decode( file_get_contents( "phar://" . $path . '/data/application.json' ), true );
                $appDirectory = $appdata[ 'app_directory' ];

                /* Extract */
                $application->extractTo( \IPS\ROOT_PATH . "/applications/" . $appDirectory, null, true );
                static::_checkChmod( \IPS\ROOT_PATH . '/applications/' . $appDirectory );
                unset( $appdata[ 'app_directory' ], $appdata[ 'app_protected' ], $appdata[ 'application_title' ] );

                foreach( $appdata as $column => $value ) {
                    $column = preg_replace( "/^app_/", "", $column );
                    $app->$column = $value;
                }

                $app->save();

                /* Determine our current version and the last version we ran */
                $currentVersion = $app->long_version;
                $allVersions = $app->getAllVersions();
                $longVersions = array_keys( $allVersions );
                $humanVersions = array_values( $allVersions );
                $lastRan = $currentVersion;

                if( count( $allVersions ) ) {
                    $latestLVersion = array_pop( $longVersions );
                    $latestHVersion = array_pop( $humanVersions );

                    \IPS\Db::i()->insert( 'core_upgrade_history', [
                        'upgrade_version_human' => $latestHVersion,
                        'upgrade_version_id'    => $latestLVersion,
                        'upgrade_date'          => time(),
                        'upgrade_mid'           => (int)\IPS\Member::loggedIn()->member_id,
                        'upgrade_app'           => $app->directory,
                    ] );
                }

                /* Now find any upgrade paths since the last one we ran that need to be executed */
                $upgradeSteps = $app->getUpgradeSteps( $lastRan );

                /* Did we find any? */
                if( count( $upgradeSteps ) ) {
                    $_next = array_shift( $upgradeSteps );
                    $app->installDatabaseUpdates( $_next );
                    foreach( $upgradeSteps as $up ) {
                        /* Get the object */
                        $_className = "\\IPS\\{$$app->directory}\\setup\\upg_{$up}\\Upgrade";
                        $_methodName = "step1";

                        if( class_exists( $_className ) ) {
                            $upgrader = new $_className;

                            /* If the next step exists, run it */
                            if( method_exists( $upgrader, $_methodName ) ) {
                                $result = $upgrader->$_methodName();
                                /* If the result is 'true' we move on to the next step, otherwise we need to run the same step again and store the data returned */
                                if( $result === true ) {
                                    $ranges = range( 2, 1000 );
                                    foreach( $ranges as $range ) {
                                        $next = 'step' . $range;
                                        if( method_exists( $upgrader, $next ) ) {
                                            $result = $upgrader->{$next}();

                                            if( $result !== true ) {
                                                break;
                                            }
                                        }
                                    }

                                }
                            }
                        }
                    }
                }
                $app->installJsonData();
                $app->installLanguages();
                $app->installEmailTemplates();
                $app->installSkins( true );
                $app->installJavascript();
            }
        }
    }

    protected static function _checkChmod( $directory )
    {
        if( !is_dir( $directory ) ) {
            throw new \UnexpectedValueException;
        }

        $it = new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS );
        foreach( new \RecursiveIteratorIterator( $it ) AS $f ) {
            if( $f->isDir() ) {
                @chmod( $f->getPathname(), \IPS\IPS_FOLDER_PERMISSION );
            } else {
                @chmod( $f->getPathname(), \IPS\IPS_FILE_PERMISSION );
            }
        }
    }

    public static function send()
    {
        set_time_limit( 0 );

        $trigger = [];
        foreach( static::roots() as $site ) {
            $trigger[] = [
                'key' => $site->key,
                'app' => $site->app,
                'url' => $site->interface_host,
            ];

            if( $site->ssh ) {
                $ftp = new \IPS\Ftp\Sftp(
                    $site->host,
                    $site->username,
                    $site->pass,
                    $site->port ?: 22
                );
            } else {
                $ftp = new \IPS\Ftp(
                    $site->host,
                    $site->username,
                    $site->pass,
                    $site->port ?: 21,
                    $site->secure,
                    $site->timeout
                );
            }

            $application = \IPS\Application::load( $site->app );
            $long = $application->long_version;
            $human = $application->version;
            $long++;
            $human++;
            $application->assignNewVersion( $long, $human );

            try {
                $application->build();
            } catch( \Exception $e ) {
                \IPS\Log::debug( $e );
                throw $e;
            }

            try {
                $pharPath = str_replace( '\\', '/', rtrim( \IPS\TEMP_DIRECTORY, '/' ) ) . '/' . $application->directory . ".tar";
                $download = new \PharData( $pharPath, 0, $application->directory . ".tar", \Phar::TAR );
                $download->buildFromIterator( new \IPS\Application\BuilderIterator( $application ) );
            } catch( \PharException $e ) {
                \IPS\Log::debug( $e );
                throw $e;
            }

            $file = rtrim( \IPS\TEMP_DIRECTORY, '/' ) . '/' . $application->directory . ".tar";
            $ftp->upload( $application->directory . ".tar", $file );

            /* Cleanup */
            unset( $download );
            \Phar::unlinkArchive( $pharPath );
        }

        static::trigger( $trigger );
    }

    protected static function trigger( array $triggers )
    {
        if( is_array( $triggers ) and count( $triggers ) ) {
            foreach( $triggers as $trigger ) {
                $url = \IPS\Http\Url::external( $trigger[ 'url' ] );
                $url->setQueryString( [ 'key' => $trigger[ 'key' ], 'app' => $trigger[ 'app' ] ] )->request( 2 )->get();
            }
        }
    }

    public function get__title()
    {
        $name = $this->host . ' ' . \IPS\Application::load( $this->app )->_title;

        return $name;
    }

    /**
     * [Node] Add/Edit Form
     *
     * @param    \IPS\Helpers\Form $form The form
     *
     * @return    void
     */
    public function form( &$form )
    {

        $el[ 'prefix' ] = 'storm_ftp_';

        $el[] = [
            'name'    => 'key',
            'require' => true,
        ];

        $el[] = [
            'name'    => 'interface_host',
            'require' => true,
        ];

        $el[] = [
            'name'    => 'app',
            'class'   => 'node',
            'options' => [
                'class'    => 'IPS\Application',
                'subnodes' => false,
            ],
            'require' => true,
        ];

        $el[] = [
            'name'    => 'host',
            'require' => true,
        ];

        $el[] = [
            'name' => 'username',
        ];

        $el[] = [
            'name'    => 'pass',
            'class'   => 'password',
            'require' => true,
        ];

        $el[] = [
            'name'    => 'port',
            'class'   => '#',
            'default' => $this->port ?: 21,
        ];

        $el[] = [
            'name'    => 'timeout',
            'class'   => '#',
            'default' => $this->timeout ?: 10,
        ];

        $el[] = [
            'name'  => 'secure',
            'class' => 'yn',
        ];

        $el[] = [
            'name'  => 'ssh',
            'class' => 'yn',
        ];

        $form = \IPS\storm\Forms::i( $el, $this, 'default', $form );
    }

    public static function topElements()
    {
        $conf = \IPS\Settings::i();
        $key = sha1( $conf->getFromConfGlobal( 'base_url' ) . $conf->getFromConfGlobal( 'board_start' ) );
        $e[] = [
            'type'    => 'dummy',
            'name'    => 'storm_remote_key_use',
            'desc'    => 'storm_remote_key_use_desc',
            'default' => $key,
        ];
        $e[] = [
            'type'    => 'dummy',
            'name'    => 'storm_remote_url',
            'desc'    => 'storm_remote_url_desc',
            'default' => \IPS\Settings::i()->base_url . 'applications/storm/interface/sync/sync.php',
        ];

        $e[] = [
            'type'    => 'dummy',
            'name'    => 'storm_cron_task',
            'default' => '<strong>' . PHP_BINDIR . '/php -d memory_limit=-1 -d max_execution_time=0 ' . \IPS\ROOT_PATH . '/applications/storm/interface/sync/task.php' . '</strong>',
            'desc'    => 'storm_cron_task_desc',
        ];
        $e[] = [
            'name' => 'storm_ftp_path',
        ];

        $form = \IPS\storm\Forms::i( $e, $conf );

        if( $vals = $form->values() ) {
            $form->saveAsSettings( $vals );
            \IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=sync' ) );
        }

        return "<div class='ipsPad'>" . $form . "</div>";
    }

    /**
     * [Node] Format form values from add/edit form for save
     *
     * @param    array $values Values from the form
     *
     * @return    array
     */
    public function formatFormValues( $values )
    {
        if( $values[ 'storm_ftp_app' ] instanceof \IPS\Application ) {

            $values[ 'storm_ftp_app' ] = $values[ 'storm_ftp_app' ]->directory;
        }
        $new = [];
        foreach( $values as $key => $val ) {
            $key = str_replace( 'storm_ftp_', '', $key );
            $new[ $key ] = $val;
        }

        return $new;
    }

    /**
     * [Node] Save Add/Edit Form
     *
     * @param    array $values Values from the form
     *
     * @return    void
     */
    public function saveForm( $values )
    {
        parent::saveForm( $values );
    }
}