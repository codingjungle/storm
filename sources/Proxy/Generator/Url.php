<?php
/**
 * @brief      Url Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */


namespace IPS\storm\Proxy\Generator;

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\storm\Proxy;
use Symfony\Component\Finder\Finder;

use function array_filter;
use function array_unshift;
use function defined;
use function file_exists;
use function file_get_contents;
use function header;
use function is_dir;
use function iterator_to_array;
use function json_decode;
use function preg_replace;

use const DIRECTORY_SEPARATOR;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Url Class
 *
 */
class Url extends GeneratorAbstract
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
        $ds = DIRECTORY_SEPARATOR;
        $root = \IPS\Application::getRootPath();
        $jsonMeta = \IPS\storm\Proxy\Generator\Store::i()->read('storm_json');

        $jsonMeta['registrar'][] = [
            'signature' => [
                'IPS\\Http\\Url::internal:0',
            ],
            'provider'  => 'url',
            'language'  => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name'   => 'url',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'stormProxy\\HttpProvider::get',
            ],
        ];


        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Http\\Url::internal:1",
            ],
            'provider'  => 'urlBase',
            'language'  => 'php',
        ];


        $jsonMeta['providers'][] = [
            'name'           => 'urlBase',
            'lookup_strings' => [
                'admin',
                'front',
            ],
        ];

        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Http\\Url::internal:2",
            ],
            'provider'  => 'furl',
            'language'  => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name'   => 'furl',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'stormProxy\\FurlProvider::get',
            ],
        ];

        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta,'storm_json');

        try {
            $toWrite = [];
            $sql = Db::i()->select('*', 'core_modules', null, 'sys_module_position')->join('core_permission_index', [
                'core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=core_modules.sys_module_id',
                'core',
                'module',
            ]);

            $modules = iterator_to_array($sql);

            foreach ($modules as $module) {
                $toWrite['app=' . $module['sys_module_application']] = 'app=' . $module['sys_module_application'];
                $base = 'app=' . $module['sys_module_application'] . '&module=' . $module['sys_module_key'];
                $toWrite[$base] = $base;
                $dir = $root . $ds . 'applications' . $ds . $module['sys_module_application'] . $ds . 'modules' . $ds . $module['sys_module_area'] . $ds . $module['sys_module_key'];

                if (is_dir($dir)) {
                    try {
                        $finder = new Finder();
                        $finder->in($dir)->name('*.php')->files();

                        foreach ($finder as $file) {
                            $fileName = $file->getBasename('.php');
                            $toWrite[$base . '&controller=' . $fileName] = $base . '&controller=' . $fileName;
                        }
                    } catch (Exception $e) {
                    }
                }
            }

            $this->writeClass('Http', 'HttpProvider', $toWrite);
        } catch (Exception $e) {
        }

        $this->buildFurlConf();
    }

    /**
     * creates the jsonMeta for the json file and writes the provider to disk.
     */
    public function buildFurlConf()
    {
        $applications = Application::applications();
        foreach ($applications as $k => $app) {
            if ($app->default) {
                unset($applications[$k]);
                array_unshift($applications, $app);
                break;
            }
        }

        $definitions = [];

        foreach ($applications as $app) {
            $path = \IPS\Application::getRootPath() . "/applications/{$app->directory}/data/furl.json";
            if (file_exists($path)) {
                $data = json_decode(preg_replace('/\/\*.+?\*\//s', '', file_get_contents($path)), true);
                /* @var array $pages */
                $pages = $data['pages'];
                foreach ($pages as $k => $page) {
                    $definitions[$k] = $k;
                }
            }
        }
        $this->writeClass('Furl', 'FurlProvider', $definitions);
    }
}

