<?php

/**
 * @brief       ModuleBuilder Trait
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  toolbox
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Traits;

\IPS\storm\Application::initAutoloader();

use Exception;
use IPS\Application;
use IPS\Application\Module;
use IPS\Db;
use IPS\Member;
use IPS\Node\Controller;
use IPS\storm\Writers\ClassGenerator;
use IPS\toolbox\Generator\DTClassGenerator;
use IPS\toolbox\Generator\DTFileGenerator;
use IPS\toolbox\Profiler\Debug;
use OutOfRangeException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use UnderflowException;
use Laminas\Code\Generator\DocBlock\Tag\ReturnTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\PropertyValueGenerator;

use function array_replace_recursive;
use function count;
use function defined;
use function file_exists;
use function file_get_contents;
use function header;
use function in_array;
use function is_array;
use function is_file;
use function json_decode;
use function mb_strtolower;

use function trim;

use const IPS\IPS_FOLDER_PERMISSION;
use const T_PROTECTED;
use const T_PUBLIC;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * ModuleBuilder Trait
 *
 * @mixin LanguageBuilder
 */
trait ModuleBuilder
{

    /**
     * @param Application $application
     * @param             $classname
     * @param             $namespace
     * @param             $type
     */
    protected function buildModule(Application $application, string $classname, string $namespace, string $type): void
    {
        $type = mb_strtolower($type);
        if (!in_array($type, ['node', 'item'])) {
            return;
        }

        $classLower = mb_strtolower($classname);

        try {
            $lang = Member::loggedIn()->language()->get('__app_' . $application->directory);
        } catch (UnderflowException $e) {
            $lang = $application->directory;
        }

        $this->addToLangs('menutab__' . $application->directory, $lang, $application);
        $methods = [];
        $classGenerator =  ClassGenerator::i()->setClassName($classLower)->setFileName($classLower);
        if ($type === 'node') {
            $config = [
                'document' => [
                    '@brief Node Class',
                    '@var \\' . $namespace . '\\' . $classname . '::class',
                ],
                'static' => false,
                'visibility' => T_PROTECTED,
            ];
            $classGenerator->addProperty(
                'nodeClass',
                '\\' . $namespace . '\\' . $classname . '::class',
                $config
            );
            $extends = Controller::class;
            $location = 'admin';
        } else {
            $config = [
                'document' => [
                    '@brief ContentModel Class',
                    '@var \\' . $namespace . '\\' . $classname . '::class',
                ],
                'static' => false,
                'visibility' => T_PROTECTED,
            ];
            $classGenerator->addProperty(
                'contentModel',
                '\\' . $namespace . '\\' . $classname . '::class',
                $config
            );
            $extends = \IPS\Content\Controller::class;
            $location = 'front';
        }

        $classGenerator->setExtends($extends);
        $classGenerator->addImport($extends);

        $ns = 'IPS\\' . $application->directory . '\\modules\\' . $location . '\\' . $classLower;
        $classGenerator->setNameSpace($ns);
        $path = \IPS\ROOT_PATH . '/applications/' . $application->directory . '/modules/' . $location . '/' . $classLower;
        $classGenerator->setPath($path);
        $modules = $this->getModules($application);
        $key = $classLower;

        try {
            $module = Module::get($application->directory, $key, $location);
        } catch (OutOfRangeException $e) {
            $module = new Module();
            $module->application = $application->directory;
            $module->area = $location;
        }

        $module->key = $key;
        $module->protected = 0;
        $module->default_controller = '';
        $module->save();
        $modules[$location][$module->key] = [
            'default_controller' => $module->default_controller,
            'protected'          => $module->protected,
        ];

        $this->addToLangs('menu__' . $application->directory . '_' . $module->key, $module->key, $application);
        $this->writeModules($modules, $application);
        $targetDir = \IPS\Application::getRootPath() .
            "/applications/{$application->directory}/modules/{$location}/{$module->key}/";
        $fs = new Filesystem();

        try {
            if (!$fs->exists($targetDir)) {
                $fs->mkdir($targetDir, IPS_FOLDER_PERMISSION);
                $fs->chmod($targetDir, IPS_FOLDER_PERMISSION);
            }
        } catch (Exception $e) {
        }

        $restriction = null;

        if ($location === 'admin') {
            /* Create a restriction? */
            $restrictions = [];
            if (
                is_file(
                    \IPS\Application::getRootPath() .
                    "/applications/{$application->directory}/data/acprestrictions.json"
                )
            ) {
                $file = \IPS\Application::getRootPath() .
                    '/applications/' .
                    $application->directory .
                    '/data/acprestrictions.json';
                if (file_exists($file)) {
                    $restrictions = json_decode(file_get_contents($file), true);
                }
            }

            $restrictions[$module->key][$classname]["{$classLower}_manage"] = "{$classLower}_manage";

            try {
                Application::writeJson(
                    \IPS\Application::getRootPath() .
                    "/applications/{$application->directory}/data/acprestrictions.json",
                    $restrictions
                );
            } catch (RuntimeException $e) {
            }

            $restriction = "{$classLower}_manage";
            $body = $restriction ? '\IPS\Dispatcher::i()->checkAcpPermission( \'' . $restriction . '\' );' : '';
            $body .= "\n\nparent::execute();";
            $config = [
                'document' => [
                    '@inheritDoc',
                ]
            ];
            $classGenerator->addMethod('execute', $body, [], $config);
        }

        try {
            $package = Member::loggedIn()->language()->get("__app_{$application->directory}");
        } catch (UnderflowException $e) {
            $package = $application->directory;
        }

        $ver = empty($application->version) === true ? $application->version : 'Pre 1.0.0';
        $docBlock = [
            '@brief ' . $classLower . ' Controller',
            '@copyright -storm_copyright-',
            '@package IPS Social Suite',
            '@subpackage ' . $package,
            '@since ' . $ver,
        ];

        $classGenerator->setDocumentComment($docBlock);
        $classGenerator->addClassComments($classLower . ' Class');
        $classGenerator->save();

        $this->addToLangs(
            'menu__' . $application->directory . '_' . $module->key . '_' . $module->key,
            $module->key,
            $application
        );

        if ($location === 'admin') {
            /* Add to the menu */
            $file = \IPS\Application::getRootPath() . '/applications/' . $application->directory . '/data/acpmenu.json';
            $menu = [];
            if (file_exists($file)) {
                $menu = json_decode(file_get_contents($file), true);
            }

            $menu[$module->key][$classLower] = [
                'tab'         => $application->directory,
                'controller'  => $classLower,
                'do'          => '',
                'restriction' => $restriction,
            ];

            try {
                Application::writeJson(
                    \IPS\Application::getRootPath() . "/applications/{$application->directory}/data/acpmenu.json",
                    $menu
                );
            } catch (RuntimeException $e) {
            }
        }
    }

    /**
     * gets the exist modules for an application/location.
     *
     * @param Application $application
     *
     * @return array
     */
    protected function getModules(Application $application): array
    {
        $file = \IPS\Application::getRootPath() . "/applications/{$application->directory}/data/modules.json";
        $json = [];
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
        }

        $modules = [];
        $extra = [];
        $db = [];

        foreach (
            Db::i()->select('*', 'core_modules', [
                'sys_module_application=?',
                $application->directory,
            ]) as $row
        ) {
            $db[] = $row;
            $extra[$row['sys_module_area']][$row['sys_module_key']] = [
                'default'            => $row['sys_module_default'],
                'id'                 => $row['sys_module_id'],
                'default_controller' => $row['sys_module_default_controller'],
                'protected'          => $row['sys_module_protected'],
            ];
        }

        if (is_array($json) && count($json)) {
            $modules = $json;
        } else {
            foreach ($db as $row) {
                $modules[$row['sys_module_area']][$row['sys_module_key']] = [
                    'default_controller' => $row['sys_module_default_controller'],
                    'protected'          => $row['sys_module_protected'],
                ];
            }
        }

        try {
            if (!is_file($file)) {
                Application::writeJson($file, $modules);
            }

            /* We get the ID and default flag from the local DB to prevent devs syncing defaults */
            return array_replace_recursive($modules, $extra);
        } catch (Exception $e) {
            return $modules;
        }
    }

    /**
     * writes the modules to the apps json file.
     *
     * @param array $json
     * @param Application $application
     */
    protected function writeModules(array $json, Application $application)
    {
        foreach ($json as $location => $module) {
            /* @var array $module */
            foreach ($module as $name => $data) {
                /* @var array $data */
                foreach ($data as $k => $v) {
                    if (!in_array($k, ['protected', 'default_controller'])) {
                        unset($json[$location][$name][$k]);
                    }
                }
            }
        }

        try {
            Application::writeJson(\IPS\Application::getRootPath() . "/applications/{$application->directory}/data/modules.json", $json);
        } catch (RuntimeException $e) {
        }
    }
}
