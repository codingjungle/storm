<?php

namespace IPS\storm\modules\front\other;

use IPS\Application;
use IPS\Dispatcher\Controller;
use IPS\Output;
use IPS\Request;
use IPS\storm\Form;
use IPS\storm\Proxy\Generator\Applications;
use IPS\storm\Proxy\Generator\Db;
use IPS\storm\Proxy\Generator\ErrorCodes;
use IPS\storm\Proxy\Generator\Extensions;
use IPS\storm\Proxy\Generator\GeneratorAbstract;
use IPS\storm\Proxy\Generator\Language;
use IPS\storm\Proxy\Generator\Moderators;
use IPS\storm\Proxy\Generator\phpstormMeta;
use IPS\storm\Proxy\Generator\Templates;
use IPS\storm\Proxy\Generator\Url;
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

    protected function constants()
    {
        \IPS\storm\Proxy::i()->emptyDirectory(\IPS\storm\Proxy::i()->path);
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
        \IPS\storm\Proxy::i()->rebuildModels((bool) Request::i()->mixin);

        $message = 'DB Models proxies built!';
        Output::i()->json(['message' => $message]);
    }

    protected function nonOwnedModels(): void
    {
        \IPS\storm\Proxy::i()->rebuildNonOwnedModels();
        $message = 'Non Owned DB Models proxies built!';
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
        $path = \IPS\Application::getRootPath() . '/applications/storm/data/storm/';
        $jsonMeta = json_decode(file_get_contents($path . 'defaults.json'), true);
        $jsonMeta2 = json_decode(file_get_contents($path . 'defaults2.json'), true);
        $jsonMeta += $jsonMeta2;
        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta, 'storm_json');

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
        $this->steps('Applications Registrar & Provider completed', Applications::class);
    }

    protected function database(): void
    {
        $this->steps('Databases Registrar & Provider completed', Db::class);
    }

    protected function languages(): void
    {
        $this->steps('Language Registrar  & Provider  completed', Language::class);
    }

    protected function extensions(): void
    {
        $this->steps('Extensions Registrar & Provider  completed', Extensions::class);
    }

    protected function templates(): void
    {
        $this->steps('Templates Registrars & Providers completed', Templates::class);
    }

    protected function moderators(): void
    {
        $this->steps('Moderators Perms Registrar & Provider completed', Moderators::class);
    }

    protected function url(): void
    {
        $this->steps('Url Registrar & Provider completed', Url::class);
    }

    protected function errorCodes(): void
    {
        $this->steps('ErrorCodes Registrars and Providers completed', ErrorCodes::class);
    }

    protected function phpstormMeta(): void
    {
        $this->steps('phpstorm meta file created', phpstormMeta::class);
    }

    protected function toolboxMeta(): void
    {
        \IPS\storm\Proxy::i()->metaJson();
        $message = 'Wrapping up! completed!';
        Output::i()->json(['message' => $message]);
    }

    private function steps(string $message, string $class)
    {
        $class::i()->create();
        Output::i()->json(['message' => $message]);
    }
}
