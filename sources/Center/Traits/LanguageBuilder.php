<?php

/**
 * @brief       Language Trait
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Traits;

\IPS\storm\Application::initAutoloader();

use Exception;
use IPS\Application;
use IPS\storm\Profiler\Debug;
use IPS\storm\Writers\FileGenerator;
use Symfony\Component\Filesystem\Filesystem;

use function defined;
use function header;
use function is_file;
use function var_export;

use const IPS\IPS_FOLDER_PERMISSION;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

trait LanguageBuilder
{
    /**
     * @param             $key
     * @param             $value
     * @param Application $application
     */
    protected function addToLangs($key, $value, Application $application)
    {
        $lang = [];
        $dir = Application::getRootPath() . "/applications/{$application->directory}/dev/";
        $file = $dir . 'lang.php';

        try {
            $fs = new Filesystem();

            if (!$fs->exists($dir)) {
                $fs->mkdir($dir, IPS_FOLDER_PERMISSION);
                $fs->chmod($dir, IPS_FOLDER_PERMISSION);
            }

            if (is_file($file)) {
                require $file;
            }

            $lang[$key] = $value;
            $body = "\$lang = " . var_export($lang, true) . ";\n\n";

            FileGenerator::i()
                ->setFileName('lang')
                ->setPath($dir)
                ->addBody($body)
                ->save();
        } catch (Exception $e) {
            Debug::log($e, 'Languages Creationg');
        }
    }
}
