//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    exit;
}

class storm_hook_adminGlobalThemeHook extends _HOOK_CLASS_
{

    /* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'globalTemplate' => 
  array (
    0 => 
    array (
      'selector' => '#ipsLayout_header',
      'type' => 'add_inside_start',
      'content' => '{{$devBar = \IPS\storm\Menu::devBar(); }}{$devBar|raw}',
    ) )
), parent::hookData() );
}
/* End Hook Data */
    public function tabs( $tabNames, $activeId, $defaultContent, $url, $tabParam = 'tab' )
    {
        if ( \IPS\Request::i()->app == "core" and \IPS\Request::i()->module == "applications" and \IPS\Request::i()->controller == "developer" and !\IPS\Request::i()->do ) {
            $tabNames[ 'class' ] = 'dev_class';
            $tabNames[ 'DevFolder' ] = 'storm_dev_folder';
            $tabNames['ClassDev'] = 'New Sources Tab';
            $tabNames['StormLangs'] = 'Language';
        }

        return parent::tabs( $tabNames, $activeId, $defaultContent, $url, $tabParam );

    }

    public function globalTemplate( $title, $html, $location = [] )
    {
        if( !\IPS\Settings::i()->storm_settings_disable_menu)
        {
            $version = \IPS\Application::load( 'core' );
            if( $version->long_version < 101110 )
            {
                \IPS\Output::i()->cssFiles = \array_merge(
                    \IPS\Output::i()->cssFiles,
                    \IPS\Theme::i()->css(
                        'devbar/devbar2.css',
                        'storm',
                        'admin'
                    )
                );

            }
            else
            {
                \IPS\Output::i()->cssFiles = \array_merge(
                    \IPS\Output::i()->cssFiles,
                    \IPS\Theme::i()->css(
                        'devbar/devbar.css',
                        'storm',
                        'admin'
                    )
                );
            }
        }
        $parent = parent::globalTemplate( $title, $html, $location );
        if( defined( 'CJ_STORM_PROFILER_ACP' ) and CJ_STORM_PROFILER_ACP ) {
            $parent = \str_replace( '</body>', "<!--ipsQueryLog--></body>", $parent );
        }
        return $parent;
    }
}
