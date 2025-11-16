<?php
/**
 * @brief      GeneratorAbstract Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\storm\Proxy\Generator;

use Exception;
use IPS\Patterns\Singleton;
use IPS\storm\Application;
use IPS\storm\Proxy;
use IPS\storm\Writers\ClassGenerator;

use function defined;
use function header;
use function implode;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * GeneratorAbstract Class
 *
 */
class GeneratorAbstract extends Singleton
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static ?Singleton $instance = null;

    protected string $save = '';

    public function __construct()
    {
        Application::initAutoloader();
        $this->save = Proxy::i()->path . 'metadata';
    }

    public function writeClass(
        string $class,
        string $implements,
        ?array $body = null,
        string $ns = 'stormProxy',
        string $funcName = 'get'
    ): void {
        try {
            $file = ClassGenerator::i()
                ->setNameSpace($ns)
                ->setClassName($class)
                ->setFileName($implements)
                ->setExtension('php')
                ->setPath($this->save . DIRECTORY_SEPARATOR . 'providers');

            if ($body) {
                $file->addInterfaces(['stormProxy', $implements]);
                $file->addMethod($funcName, 'return [\'' . implode("','", $body) . '\'];');
            } else {
                $file->setExtends([$ns, $implements]);
            }

            $file->save();
        } catch (Exception $e) {
        }
    }
}

