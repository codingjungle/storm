<?php

/**
 * @brief       Settings Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Storm
 * @since       2.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\modules\admin\configuration;

/* To prevent PHP errors (extending class does not exist) revealing path */
if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return    void
     */
    public function execute()
    {
        \IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
        parent::execute();
    }

    /**
     * ...
     *
     * @return    void
     */
    protected function manage()
    {
        \IPS\Output::i()->title = "Settings";
        $form = \IPS\storm\Settings::form();



        $pateched = \IPS\ROOT_PATH.DIRECTORY_SEPARATOR.'init_backup.php';

        if( !\file_exists( $pateched ) ) {
            \IPS\Output::i()->sidebar[ 'actions' ][ 'patch' ] = [
                'icon'  => 'plus',
                'title' => 'Patch init.php',
                'link'  => \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=settings&do=patchInit' ),
            ];
        }
        else{
            \IPS\Output::i()->sidebar[ 'actions' ][ 'patch' ] = [
                'icon'  => 'minus',
                'title' => 'UnPatch init.php',
                'link'  => \IPS\Http\Url::internal( 'app=storm&module=configuration&controller=settings&do=unPatchInit' ),
            ];
        }

        \IPS\Output::i()->output = $form;
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
    protected function getFields()
    {
        $table = \IPS\Request::i()->table;
        $fields = \IPS\Db::i()->query( "SHOW COLUMNS FROM " . \IPS\Db::i()
                ->real_escape_string( \IPS\Db::i()->prefix . $table ) );
        $f = [];
        foreach( $fields as $field ) {
            $f[ array_values( $field )[ 0 ] ] = array_values( $field )[ 0 ];
        }

        $data = new \IPS\storm\Forms\Select(
            'storm_query_columns',
            null,
            false,
            [
                'options' => $f,
                'parse'   => false,
            ],
            null,
            null,
            null,
            'js_storm_query_columns'
        );

        $send[ 'error' ] = 0;
        $send[ 'html' ] = $data->html();
        \IPS\Output::i()->json( $send );
    }

    protected function unPatchInit(){
        $path = \IPS\ROOT_PATH . DIRECTORY_SEPARATOR;
        $foo = $path. 'init.php';
        @unlink( $foo );
        @rename( $path.'init_backup.php', $path.'init.php');
        \IPS\Output::i()->redirect( $this->url, 'init.php unpatched');

    }

    protected function patchInit()
    {
        $path = \IPS\ROOT_PATH . DIRECTORY_SEPARATOR;
        $foo = $path. 'init.php';
        $content = \file_get_contents( $foo );

        rename($foo, $path.'init_backup.php');
//
//        $preg = "#public static function monkeyPatch\((.*?)public#msu";
//
//
//        $before = <<<EOF
//public static function monkeyPatch( \$namespace, \$finalClass, \$extraCode = '' )
//    {
//        \$extraCode = '';
//        \$realClass = "_{\$finalClass}";
//
//        if( isset( self::\$hooks[ "\\\\{\$namespace}\\\\{\$finalClass}" ] ) AND \\IPS\\RECOVERY_MODE === FALSE )
//        {
//            \$path = __DIR__ . "/hook_temp/";
//
//            if( !is_dir( \$path ) )
//            {
//                \\mkdir( \$path, 0777, true );
//            }
//
//            foreach( self::\$hooks[ "\\\\{\$namespace}\\\\{\$finalClass}" ] as \$id => \$data )
//            {
//                if( \\file_exists( ROOT_PATH . '/' . \$data[ 'file' ] ) )
//                {
//                    \$contents = "namespace {\$namespace}; " . str_replace( '_HOOK_CLASS_', \$realClass, file_get_contents( ROOT_PATH . '/' . \$data[ 'file' ] ) );
//
//                    \$hash = md5( \$contents );
//
//                    \$filename = \\str_replace( [ "\\\\", "/" ], "_", \$namespace . \$realClass . \$finalClass . \$data[ 'file' ] );
//
//                    \$fileHash = false;
//
//                    if( file_exists( \$path . \$filename ) )
//                    {
//                        \$fileHash = \\md5_file( \$path . \$filename );
//                    }
//
//                    if( \$hash != \$fileHash )
//                    {
//                        \\file_put_contents( \$path . \$filename, "<?php\\n\\n" . \$contents );
//                    }
//
//                    require_once( \$path . \$filename );
//
//                    \$realClass = \$data[ 'class' ];
//                }
//            }
//        }
//
//        \$reflection = new \ReflectionClass( "{\$namespace}\\\\_{\$finalClass}" );
//
//        if( eval( "namespace {\$namespace}; " . \$extraCode . ( \$reflection->isAbstract() ? 'abstract' : '' ) . " class {\$finalClass} extends {\$realClass} {}" ) === false )
//        {
//            trigger_error( "There was an error initiating the class {\$namespace}\\\\{\$finalClass}.", E_USER_ERROR );
//        }
//    }
//EOF;
//
//        $file = preg_replace_callback( $preg, function( $e ) use ( $before ) {
//            return $before . "\n\n  public";
//        }, $content );

        $r = <<<EOF
require __DIR__ . '/applications/storm/sources/Debug/Helpers.php';
class IPS
EOF;
        $file = \str_replace( 'class IPS', $r, $content );
        \file_put_contents( $foo, $file );

        \IPS\Output::i()->redirect( $this->url, 'init.php patched');
    }
}