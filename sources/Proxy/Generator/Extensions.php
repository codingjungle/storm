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
use IPS\Patterns\Singleton;
use IPS\storm\Writers\FileGenerator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function defined;
use function header;
use function implode;
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
    protected static ?Singleton $instance = null;

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
            $path = Application::getRootPath() . '/applications/' . $key->directory . '/data/defaults/extensions/';
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
        $body = Store::i()->read('storm_metadata_final');
        $toWrite = [];

        foreach ($name as $val) {
            $toWrite[] = "'" . $val . "'";
        }
        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('Extensions', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Application::extensions()', 'i' => 1],
            ['f' => '\\IPS\\Application::allExtensions()', 'i' => 1]
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('Extensions'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}

