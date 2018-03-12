//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_applicationBuilderIterator extends _HOOK_CLASS_
{

    /**
     * Current value
     *
     * @return    void
     */
    public function current()
    {
        $file = (string) parent::current();

        if ( mb_substr( str_replace( '\\', '/', $file ),
                        mb_strlen( \IPS\ROOT_PATH . "/applications/" . $this->application->directory ) + 1, 6 ) === 'hooks/'
        ) {
            $temporary = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );

            \file_put_contents( $temporary, \IPS\Plugin::addExceptionHandlingToHookFile( $file ) );

            register_shutdown_function( function ( $temporary ) {
                unlink( $temporary );
            }, $temporary );

            return $temporary;
        }
        else {
            if ( is_file( $file ) and ( mb_strpos( $file, '3rdparty' ) === false or mb_strpos( $file,
                                                                                               '3rd_party' ) === false or mb_strpos( $file, 'vendor' ) === false )
            ) {
                if ( !\IPS\storm\Headerdoc::i()->can( $this->application ) ) {
                    return $file;
                }

                $path = new \SplFileInfo( $file );

                if ( $path->getExtension() == "js" and $path->getFilename() === "babble.js" ) {
                    $temp = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );

                    $content = \file_get_contents( $file );

                    $replace = \preg_replace( "#var privateKey = '(.*?)';#", "var privateKey = '';", $content );

                    \file_put_contents( $temp, $replace );

                    return $temp;
                }

                if ( $path->getExtension() == "php" ) {
                    $temporary = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );

                    $contents = \file_get_contents( $file );

                    foreach ( $this->application->extensions( 'storm', 'Headerdoc' ) as $class ) {
                        if ( method_exists( $class, 'headerDocFinalize' ) ) {
                            $contents = $class->headerDocFinalize( $contents, $this->application );
                        }
                    }

                    \file_put_contents( $temporary, $contents );

                    register_shutdown_function( function ( $temporary ) {
                        unlink( $temporary );
                    }, $temporary );

                    return $temporary;
                }
            }

            return $file;
        }
    }
}
