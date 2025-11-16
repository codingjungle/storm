<?php

use IPS\IPS;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use const IPS\ROOT_PATH;

//require_once ROOT_PATH . '/init.php';
require_once ROOT_PATH . '/applications/storm/sources/Helpers/Helpers.php';

class Bootstrap extends IPS
{
    public static bool $override = false;
    public static array $constants = [
        'STORM_MY_APPS' => [],
    ];
    protected static array $hf = [
        'IPS\\Db' => ['ips' => 'system/Db/Db.php', 'hook' => 'Db.php'],
        'IPS\\Theme' => ['ips' => 'system/Theme/Theme.php', 'hook' => 'Theme.php'],
        'IPS\\Theme\\Dev\\Template' => ['ips' => 'system/Theme/Dev/Template.php', 'hook' => 'Template.php'],
        'IPS\\Log' => ['ips' => 'system/Log/Log.php', 'hook' => 'Log.php']
    ];

    public static function init()
    {
        $vendor = ROOT_PATH . '/applications/storm/sources/Vendor/autoload.php';
        require $vendor;
        spl_autoload_register(array('\Bootstrap', 'autoloader'), true, true);
//        \set_exception_handler(array('\Bootstrap', 'exceptionHandler' ));

        foreach (static::$constants as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * Autoloader
     *
     * @param string $classname Class to load
     * @return  void
     */
    public static function autoloader($classname)
    {
        /* Separate by namespace */
        $bits = explode('\\', ltrim($classname, '\\'));

        /* If this doesn't belong to us, try a PSR-0 loader or ignore it */
        $vendorName = array_shift($bits);
        if ($vendorName !== 'IPS') {
            return;
        }

        /* Work out what namespace we're in */
        $class = array_pop($bits);
        $namespace = empty($bits) ? 'IPS' : ('IPS\\' . implode('\\', $bits));
        $lookUp = "{$namespace}\\{$class}";

        if (isset(static::$hf[$lookUp])) {
            if (!class_exists("{$namespace}\\{$class}", false)) {
                $path = ROOT_PATH . '/hooks/';
                $hookInfo = static::$hf[$lookUp];
                $ipsFile = ROOT_PATH . '/' . $hookInfo['ips'];
                $hookFile = ROOT_PATH . '/applications/storm/sources/Hooks/' . $hookInfo['hook'];
                $mtime = filemtime($ipsFile);
                $name = str_replace(ROOT_PATH, '', $ipsFile);
                $name = str_replace(["\\", '/'], '_', $name);
                $filename = $name . '_' . $mtime . '.php';

                if (!file_exists($path . $filename) && file_exists($ipsFile)) {
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    $fs = new Filesystem();
                    $finder = new Finder();
                    $finder->in($path)->files()->name($name . '*.php');

                    foreach ($finder as $f) {
                        $fs->remove($f->getRealPath());
                    }

                    $content = file_get_contents($ipsFile);
                    $content = preg_replace('#\b(?<![\'|"])class ' . $class . '\b#', 'class _' . $class, $content);
                    if (!file_exists($path . $filename)) {
                        file_put_contents($path . $filename, $content);
                    }
                }

                require_once $path . $filename;
                require_once $hookFile;

                if (interface_exists($lookUp, false)) {
                    return;
                }

                /* Is it a trait? */
                if (trait_exists($lookUp, false)) {
                    return;
                }

                /* Is it an enumeration? */
                if (function_exists('enum_exists') && enum_exists($lookUp, false)) {
                    return;
                }

                /* Does it exist? */
                if (class_exists($lookUp, false)) {
                    return;
                }

                /* Doesn't exist? */
                if (!class_exists("{$namespace}\\{$class}", false)) {
                    trigger_error(
                        "Class {$classname} could not be loaded. Ensure it is in the correct namespace. storm",
                        E_USER_ERROR
                    );
                }
            }
        }
    }

    public static function exceptionHandler($exception)
    {
        if (\IPS\IN_DEV === true) {
            throw $exception;
        } else {
            parent::exceptionHandler($exception);
        }
    }
}

Bootstrap::init();