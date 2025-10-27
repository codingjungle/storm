<?php

namespace IPS\storm\extensions\storm\ProxyHelpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\storm\Proxy\Generator\Store;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * ProxyHelpers
 */
class ProxyHelpers
{
    /**
     * add property to \IPS\Data\Store DocComment
     *
     * @param array $classDoc
     */
    public function store(&$classDoc)
    {

        $classDoc[] = ['pt' => 'p', 'prop' => 'download', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'dtversions', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'acpBulletin', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'administrators', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'applications', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'bannedIpAddresses', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'cms_databases', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'cms_fieldids', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'emoticons', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'furl_configuration', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'groups', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'languages', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'maxAllowedPacket', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'moderators', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'modules', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'nexusPackagesWithReviews', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'profileSteps', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'rssFeeds', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'settings', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'storageConfigurations', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'themes', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'formularize_output', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'formularize_validation', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'formularize_ra', 'type' => 'array'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'formularize_folders', 'type' => 'array'];
    }

    /**
     * add property to \IPS\Request proxy DocComment
     *
     * @param array $classDoc
     */
    public function request(&$classDoc)
    {
        $classDoc[] = ['pt' => 'p', 'prop' => 'myApp', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'app', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'module', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'controller', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'id', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'pid', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'do', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'appKey', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'tab', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'adsess', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'group', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'new', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => '_new', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'path', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'c', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'd', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'application', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'type', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'limit', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'password', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'club', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'page', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'perPage', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'value', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'sortby', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'sortdirection', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'parent', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'filter', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'params', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'input', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'action', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'chunk', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'chunks', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'last', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'enabled', 'type' => 'int'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'gitApp', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'alpha', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'omega', 'type' => 'int'];
    }

    /**
    * returns a list of classes available to run on classes
    * @return array
    * $heelpers[ class\to\look\for ][] = class\of\helper\class;
    * @param $helpers
    */
    public function map(&$helpers)
    {
        //see toolbox\extensions\toolbox\ProxyHelpers\proxy.php::map()
    }

    public function phpstormMeta(&$body): void
    {
        $body[] = <<<eof
    exitPoint(\IPS\Output::error());
    exitPoint(\IPS\Output::sendOutput());
    exitPoint(\IPS\Output::json());
    exitPoint(\IPS\Output::redirect());
    exitPoint(\IPS\Output::showOffline());
    exitPoint(\IPS\Output::showBanned());
    exitPoint(\_p());
    exitPoint(\_d());
    override(\IPS\Settings::i(), map([
        '' => 'IPS\_Settings'
    ]));
    override(\IPS\Request::i(), map([
        '' => 'IPS\_Request'
    ]));    
    override(\IPS\Data\Store::i(), map([
        '' => 'IPS\\Data\\_Store'
    ]));
    override(\IPS\Theme::getTemplate(), map([
eof;
        $templates = Store::i()->read('storm_phpstorm_templates');

        foreach ($templates as $ori => $template) {
            $body[] = "'{$ori}' => '{$template}',";
        }

        $body[] = "]));";
    }
}
