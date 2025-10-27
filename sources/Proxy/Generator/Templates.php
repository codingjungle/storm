<?php
/**
 * @brief      Templates Singleton
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox\Proxy
 * @since      -storm_since_version-
 * @version    -storm_version-
 */

namespace IPS\storm\Proxy\Generator;

use Exception;
use IPS\Patterns\Singleton;
use IPS\storm\Profiler\Debug;
use IPS\storm\Proxy;
use IPS\storm\Writers\ClassGenerator;
use IPS\Theme;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;

use function array_pop;
use function array_values;
use function defined;
use function explode;
use function file_put_contents;
use function function_exists;
use function header;
use function ksort;
use function mb_strtolower;
use function md5;
use function rand;
use function random_int;
use function randomString;
use function str_replace;
use function time;
use function trim;

use const DIRECTORY_SEPARATOR;


if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Templates Class
 *
 * @mixin Templates
 */
class Templates extends GeneratorAbstract
{

    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static ?Singleton $instance = null;

    public function create()
    {
        $jsonMeta = Store::i()->read('storm_json');
//        $jsonMeta[ 'registrar' ][] = [
//            'signature' => [
//                "IPS\\Theme::getTemplate:0",
//            ],
//            'provider'  => 'templateGroup',
//            'language'  => 'php',
//        ];
        //this pisses me off, this use to work!
        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Theme::getTemplate:0",
            ],
            'signatures' => [
                [
                    'class' => Theme::class,
                    'method' => 'getTemplate',
                    'index' => 0,
                    'type' => 'type',
                ],

            ],
            'provider' => 'templateClass',
            'language' => 'php',
        ];
        $jsonMeta['registrar'][] = [
            'signature' => [
                'IPS\\Theme::getTemplate:2',
                'IPS\\Output::js:2',
                'IPS\\Output::css:2',
            ],
            'provider' => 'templateLocation',
            'language' => 'php',
        ];
        $jsonMeta['providers'][] = [
            'name' => 'templateLocation',
            'lookup_strings' => [
                'admin',
                'front',
                'global',
            ],
        ];

        $tempStore = [];
        $tempClass = [];
        $templates = Store::i()->read('storm_templates');
        $phpStormMeta = Store::i()->read('storm_phpstorm_templates');
        if (empty($templates) === false) {
            foreach ($templates as $key => $template) {
                $key = str_replace(\IPS\Application::getRootPath() . '/applications/', '', $key);
                $tpl = explode(DIRECTORY_SEPARATOR, $key);
                array_pop($tpl);
                $temp = array_pop($tpl);
                $ori = $temp;
                $newParams = [];

                if ($temp === 'global') {
                    $temp = 'nglobal';
                }

                if (!empty($template['params'])) {
                    $rand = trim($template['method']) . randomString(20) . randomString(20);
                    $fun = 'function ' . $rand . '( ' . $template['params'] . ' ) {}';
                    @eval($fun);
                    if (function_exists($rand)) {
                        try {
                            $reflection = new ReflectionFunction($rand);
                            $params = $reflection->getParameters();

                            /** @var ReflectionParameter $param */
                            foreach ($params as $param) {
                                $data = [
                                    'name' => $param->getName()
                                ];

                                if ($param->getType()) {
                                    $data['hint'] = $param->getType();
                                }

                                try {
                                    $data['value'] = $param->getDefaultValue();
                                } catch (Exception | ReflectionException) {
                                }

                                $newParams[$param->getPosition()] = $data;
                            }
                        } catch (Exception $e) {
                        }
                    }
                }

                $tempStore[$ori] = [
                    'lookup_string' => $ori
                ];
                $phpStormMeta[$ori] = 'stormProxy\\' . $ori;
                $tempClass[$temp][$template['method']] = [
                    'name' => $template['method'],
                    'params' => $newParams
                ];
            }
        }

        ksort($tempStore);
        ksort($phpStormMeta);
        Store::i()->write($phpStormMeta, 'storm_phpstorm_templates');
        $tempStore = array_values($tempStore);
        $jsonMeta['providers'][] = [
            'name' => 'templateClass',
            'items' => $tempStore,
        ];
        Store::i()->write($jsonMeta, 'storm_json');
        Store::i()->write($tempClass, 'storm_template_class');
        $this->makeTempClasses($tempClass);
    }

    public function amendFile(string $file, string $method, array $params)
    {
        $content = trim(file_get_contents($file));
        $funcNames = preg_match_all('#function (.*?)\(#msu', $content, $matching);
        $v = array_values($matching[1]);
        $found = array_combine($v, $v);
        $append = 0;
        if (!isset($found[$method])) {
            $cc = array_reverse(explode(PHP_EOL, $content));
            $newDoc = [];

            foreach ($cc as $line => $value) {
                if ($value === "}") {
                    unset($cc[$line]);
                    break;
                }
            }
            $cc = implode("\n", array_reverse($cc));

            $toWrite = 'public function ' . $method . '(';
            $pp = [];
            if (empty($params) === false) {
                foreach ($params as $data) {
                    $paramBody = '';
                    if (isset($data['hint'])) {
                        $paramBody .= ' ' . $data['hint'] . ' ';
                    }
                    $paramBody .= '$' . $data['name'];
                    if (isset($data['value'])) {
                        $val = $data['value'];
                        $paramBody .= ' = ';
                        if (is_int($val)) {
                            $paramBody .= $val;
                        } elseif (is_bool($val)) {
                            $paramBody .= $val === false ? 'false' : 'true';
                        } elseif ($val === 'null' || $val === null) {
                            $paramBody .= 'null';
                        } else {
                            $paramBody .= '"' . $val . '"';
                        }
                    }
                    $pp[] = $paramBody;
                }

                $toWrite .= implode(', ', $pp);
            }
            $toWrite .= '){}';
            $cc .= "\n\n" . $toWrite . "\n\n}";
            file_put_contents($file, $cc);
        }
    }

    /**
     * @param array $classes
     */
    public function makeTempClasses(array $classes)
    {
        foreach ($classes as $key => $templates) {
            try {
                $nc = ClassGenerator::i()
                    ->setPath($this->save . '/templates/')
                    ->setNameSpace('stormProxy')
                    ->setClassName($key)
                    ->setFileName($key);

                foreach ($templates as $template) {
                    $nc->addMethod($template['name'], '', $template['params']);
                }

                $nc->save();
            } catch (Exception $e) {
                Debug::log($e);
            }
        }
    }
}

