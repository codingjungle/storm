<?php

namespace IPS\storm\modules\front\other;

use IPS\Application;
use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Request;
use IPS\storm\Form;
use IPS\storm\Proxy\Generator\Applications;
use IPS\storm\Proxy\Generator\Database;
use IPS\storm\Proxy\Generator\Db;
use IPS\storm\Proxy\Generator\ErrorCodes;
use IPS\storm\Proxy\Generator\Extensions;
use IPS\storm\Proxy\Generator\GeneratorAbstract;
use IPS\storm\Proxy\Generator\Languages;
use IPS\storm\Proxy\Generator\Moderators;
use IPS\storm\Proxy\Generator\phpstormMeta;
use IPS\storm\Proxy\Generator\Templates;
use IPS\storm\Proxy\Generator\Url;
use IPS\storm\Settings;
use IPS\Theme;

use function base64_decode;
use function defined;
use function file_get_contents;
use function json_decode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * proxy
 */
class proxy extends Controller
{
    /**
     * Execute
     *
     * @return  void
     */
    public function execute(): void
    {
        parent::execute();
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage(): void
    {
    }

    protected function clearMetaData(): void
    {
        \IPS\storm\Proxy::i()->emptyDirectory(\IPS\storm\Proxy::i()->path);
        $message = 'MetaData: Proxy files cleared!';
        Output::i()->json(['message' => $message]);
    }

    protected function constants()
    {
        //\IPS\storm\Proxy::i()->emptyDirectory(\IPS\storm\Proxy::i()->path);
        \IPS\storm\Proxy::i()->clearJsonFiles();
        \IPS\storm\Proxy::i()->constants();
        $message = 'Constants proxy file built!';
        Output::i()->json(['message' => $message]);
    }

    protected function settings()
    {
        \IPS\storm\Proxy::i()->settings();

        $message = 'Settings proxy file built!';
        Output::i()->json(['message' => $message]);
    }

    protected function request()
    {
        \IPS\storm\Proxy::i()->request();

        $message = 'Request proxy file built!';
        Output::i()->json(['message' => $message]);
    }

    protected function store()
    {
        \IPS\storm\Proxy::i()->store();

        $message = 'Store proxy file built!';
        Output::i()->json(['message' => $message]);
    }

    protected function models(): void
    {
        \IPS\storm\Proxy::i()->buildModels();

        $message = 'DB Models proxies built!';
        Output::i()->json(['message' => $message]);
    }

    protected function nonOwnedModels(): void
    {
        \IPS\storm\Proxy::i()->buildNonOwnedModels();
        $message = 'Non-owned models disabled, change in settings.';
        if (Settings::i()->storm_proxy_do_non_owned === true) {
            $message = 'Non Owned DB Models proxies built!';
        }
        Output::i()->json(['message' => $message]);
    }

    protected function css(): void
    {
        \IPS\storm\Proxy::i()->css();

        $message = 'CSS proxy files built!';
        Output::i()->json(['message' => $message]);
    }

    protected function generators(): void
    {
        $phpstorm = 0;
        if (\IPS\DEV_WHOOPS_EDITOR === 'phpstorm') {
            $phpstorm = 1;
        }
        $html = Theme::i()->getTemplate('generators', 'storm', 'global')->main($phpstorm);
//        if (Request::i()->isAjax()) {
//            Output::i()->json(['html' => $html]);
//        } else {
            Output::i()->output = $html;
//        }
    }

    protected function phpCache(): void
    {
        \IPS\storm\Proxy::i()->build();
//        $path = \IPS\Application::getRootPath() . '/applications/storm/data/storm/';
//        $jsonMeta = json_decode(file_get_contents($path . 'defaults.json'), true);
//        $jsonMeta2 = json_decode(file_get_contents($path . 'defaults2.json'), true);
//        $jsonMeta += $jsonMeta2;
//        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta, 'storm_json');

        $message = 'MetaData: PHP Meta Caches completed.';
        Output::i()->json(['message' => $message]);
    }

    protected function phtmlCache(): void
    {
        \IPS\storm\Proxy::i()->build(['phtml']);
        $message = 'MetaData: PHTML Meta Caches completed.';
        Output::i()->json(['message' => $message]);
    }

    protected function applications(): void
    {
        Applications::run();
        Output::i()->json(['message' => 'Applications Registrar & Provider completed']);
    }

    protected function database(): void
    {
        Database::run();
        Output::i()->json(['message' => 'Databases Registrar & Provider completed']);
    }

    protected function languages(): void
    {
        Languages::run();
        Output::i()->json(['message' => 'Language Registrar & Provider completed']);
    }

    protected function extensions(): void
    {
        Extensions::i()->create();
        Output::i()->json(['message' => 'Extensions Registrar & Provider completed']);
    }

    protected function templates(): void
    {
        Templates::i()->create();
        Output::i()->json(['message' => 'Templates Registrar & Provider completed']);
    }

    protected function moderators(): void
    {
        Moderators::run();
        Output::i()->json(['message' => 'Moderators Perms Registrar & Provider completed']);
    }

    protected function url(): void
    {
        Url::run();
        Output::i()->json(['message' => 'Url Registrar & Provider completed']);
    }

    protected function errorCodes(): void
    {
        ErrorCodes::run();
        Output::i()->json(['message' => 'ErrorCodes Registrar & Provider completed']);
    }

    protected function phpstormMeta(): void
    {
        phpstormMeta::i()->create();
        Output::i()->json(['message' => 'phpstorm meta file created']);
    }

    protected function toolboxMeta(): void
    {
//        \IPS\storm\Proxy::i()->metaJson();
        $message = 'Wrapping up! completed!';
        Output::i()->json(['message' => $message]);
    }
}
