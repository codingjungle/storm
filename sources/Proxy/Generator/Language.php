<?php
/**
 * @brief      Language Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */


namespace IPS\storm\Proxy\Generator;

use IPS\Lang;
use IPS\storm\Proxy;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Language Class
 *
 * @mixin Language
 */
class Language extends GeneratorAbstract
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
                'IPS\\Lang::addToStack:0',
                'IPS\\Lang::checkKeyExists',
                'IPS\\Lang::get',
                'IPS\\Lang::saveCustom:1',
                'IPS\\Lang::copyCustom:1',
                'IPS\\Lang::copyCustom:2',
                'IPS\\Lang::deleteCustom:1',
            ],
            'provider' => 'langs',
            'language' => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name' => 'langs',
            'source' => [
                'contributor' => 'return_array',
                'parameter' => 'extensionLookup\\LanguageProvider::get',
            ],
        ];

        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta, 'storm_json');

        $toWrite = [];
        $lang = Lang::load(Lang::defaultLanguage());

        foreach ($lang->words as $key => $val) {
            $toWrite[] = $key;
        }

        $this->writeClass('Langs', 'LanguageProvider', $toWrite);
    }
}

