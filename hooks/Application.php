//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_Application extends _HOOK_CLASS_
{

    public function assignNewVersion( $long, $human )
    {
        parent::assignNewVersion( $long, $human );
        $this->version = $human;
        \IPS\storm\Headerdoc::i()->process( $this );
    }

    public function build()
    {
        \IPS\storm\Headerdoc::i()->addIndexHtml( $this );
        parent::build();
    }

    public function installJavascript( $offset=null, $limit=null ){
        parent::installJavascript($offset, $limit);
        \IPS\storm\Proxyclass::i()->generateSettings();
    }

    public function installOther()
    {
        if ( \IPS\IN_DEV and defined( 'CJ_STORM_BUILD_DEV' ) and CJ_STORM_BUILD_DEV ) {
            $dir = \IPS\ROOT_PATH . "/applications/" . $this->directory . "/dev/";
            if ( !file_exists( $dir ) and $this->directory !== "storm" ) {
                $app = new \IPS\storm\Apps( $this );
                $app->addToStack = true;
                $app->email();
                $app->javascript();
                $app->language();
                $app->templates();
            }
        }

        parent::installOther();
    }
}
