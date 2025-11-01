<?php

namespace IPS\storm\Proxy\Generator;

use Exception;
use IPS\Application;
use Symfony\Component\Finder\Finder;

use function array_unshift;
use function file_exists;
use function file_get_contents;
use function implode;
use function is_dir;
use function iterator_to_array;
use function json_decode;
use function preg_replace;

use const DIRECTORY_SEPARATOR;

class Url
{

    public static function run() :void
    {
        $body = Store::i()->read('storm_metadata_final');
        $body[] = <<<EOF
    registerArgumentsSet('urlBase', 'admin','front', null);
    expectedArguments(\\IPS\\Http\\Url::internal(), 1, argumentsSet('urlBase')); 
EOF;

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
            $path = Application::getRootPath() . "/applications/{$app->directory}/data/furl.json";
            if (file_exists($path)) {
                $data = json_decode(
                    preg_replace(
                        '/\/\*.+?\*\//s',
                        '',
                        file_get_contents($path)
                    ),
                    true
                );

                /* @var array $pages */
                $pages = $data['pages'];

                foreach ($pages as $k => $page) {
                    $definitions[$k] = "'" . $k . "'";
                }
            }
        }

        $definitions = implode(',', $definitions);
        $body[] = <<<EOF
    registerArgumentsSet('furl', {$definitions});
    expectedArguments(\\IPS\\Http\\Url::internal(), 2, argumentsSet('furl')); 
EOF;

        $toWrite = [];

        try {
            $ds = DIRECTORY_SEPARATOR;
            $root = \IPS\Application::getRootPath();
            $sql = \IPS\Db::i()->select('*', 'core_modules', null, 'sys_module_position')->join('core_permission_index', [
                'core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=core_modules.sys_module_id',
                'core',
                'module',
            ]);

            $modules = iterator_to_array($sql);

            foreach ($modules as $module) {
                $toWrite['app=' . $module['sys_module_application']] = '"app=' . $module['sys_module_application'] . '"';
                $base = 'app=' . $module['sys_module_application'] . '&module=' . $module['sys_module_key'];
                $toWrite[$base] = '"'.$base.'"';
                $dir = $root . $ds . 'applications' . $ds . $module['sys_module_application'] . $ds . 'modules' . $ds . $module['sys_module_area'] . $ds . $module['sys_module_key'];

                if (is_dir($dir)) {
                    try {
                        $finder = new Finder();
                        $finder->in($dir)->name('*.php')->files();

                        foreach ($finder as $file) {
                            $fileName = $file->getBasename('.php');
                            $toWrite[$base . '&controller=' . $fileName] = '"'.$base . '&controller=' . $fileName.'"';
                        }
                    } catch (Exception $e) {
                    }
                }
            }

        } catch (Exception $e) {
        }
        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('HttpProvider', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Http\\Url::internal()', 'i' => 0]
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('HttpProvider'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}