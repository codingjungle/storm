<?php
/**
 * @brief      Applications Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */


namespace IPS\storm\Proxy\Generator;

use IPS\Application;
use IPS\storm\Proxy;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Applications Class
 *
 */
class Applications extends GeneratorAbstract
{
    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static ?\IPS\Patterns\Singleton $instance = null;

    /**
     * creates the jsonMeta for the json file and writes the provider to disk.
     */
    public function create()
    {
        $jsonMeta = \IPS\storm\Proxy\Generator\Store::i()->read('storm_json');
        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Application::load",
                "IPS\\Application::appIsEnabled",
                "IPS\\Email::buildFromTemplate:0",
                "IPS\\Application::appsWithExtension:0",
                'IPS\\Lang::saveCustom:0',
                'IPS\\Lang::copyCustom:0',
                'IPS\\Lang::copyCustom:3',
                'IPS\\Lang::deleteCustom:0',
                'IPS\\Theme::getTemplate:1',
                'IPS\\Application::extension:0',
                'IPS\\Application::allExtensions:0',
                'IPS\\Output::js:1',
                'IPS\\Output::css:1',
            ],
            'provider'  => 'AppName',
            'language'  => 'php',
        ];
        $jsonMeta['providers'][] = [
            'name'   => 'AppName',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'stormProxy\\AppNameProvider::get',
            ],
        ];
        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta, 'storm_json');
        $apps = [];

        /**
         * @var Application $app
         */
        foreach (Application::roots() as $app) {
            $apps[] = $app->directory;
        }

        $this->writeClass('apps', 'AppNameProvider', $apps);
    }
}

