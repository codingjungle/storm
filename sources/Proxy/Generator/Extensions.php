<?php
/**
 * @brief      Extensions Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\storm\Proxy\Generator;

use Exception;
use IPS\Application;
use IPS\storm\Proxy;
use IPS\storm\Writers\FileGenerator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function defined;
use function header;
use function is_dir;
use function str_replace;

use const DIRECTORY_SEPARATOR;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Extensions Class
 *
 * @mixin Extensions
 */
class Extensions extends GeneratorAbstract
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static ?\IPS\Patterns\Singleton $instance  = null;

    public function __construct()
    {
        parent::__construct();
        $this->save .= DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR;
    }

    /**
     * creates the jsonMeta for the json file and writes the provider to disk
     */
    public function create()
    {
        $name = [];
        foreach (Application::roots() as $key) {
            $path = \IPS\Application::getRootPath() . '/applications/' . $key->directory . '/data/defaults/extensions/';
            if (is_dir($path)) {
                try {
                    $files = (new Finder())->in($path)->files()->name('*.txt');

                    /**
                     * @var SplFileInfo $file
                     */
                    foreach ($files as $file) {
                        $baseName = $file->getBasename('.txt');
                        $name[] = $baseName;
                        $find = [
                            '{subpackage}',
//                            '{date}',
                            '{app}',
                            '{class}',
                            '<?php',
                        ];
                        $replace = [
                            $key->directory,
//                            date('d M Y'),
                            $key->directory,
                            $file->getBasename('.txt'),
                            '',
                        ];

                        $content = str_replace($find, $replace, $file->getContents());

                        FileGenerator::i()
                            ->setFileName($baseName)
                            ->setPath($this->save)
                            ->addBody($content)
                            ->save();
                    }
                } catch (Exception $e) {
                }
            }
        }

        $jsonMeta = \IPS\storm\Proxy\Generator\Store::i()->read('storm_json');

        $jsonMeta['registrar'][] = [
            'signature'  => [
                'IPS\\Application::extensions:1',
                'IPS\\Application::allExtensions:1',
            ],
            'signatures' => [
                [
                    'class'  => Application::class,
                    'method' => 'extensions',
                    'index'  => 1,
                    'type'   => 'type',
                ],

            ],
            'provider'   => 'extensionLookup',
            'language'   => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name'   => 'extensionLookup',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'stormProxy\\ExtensionsNameProvider::get',
            ],
        ];

        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta, 'storm_json');

        $this->writeClass('Extensions', 'ExtensionsNameProvider', $name);
    }
}

