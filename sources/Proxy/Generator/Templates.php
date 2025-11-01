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
use IPS\Application;
use IPS\Patterns\Singleton;
use IPS\storm\Profiler\Debug;
use IPS\storm\Writers\ClassGenerator;
use IPS\storm\Writers\FileGenerator;
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
use function implode;
use function ksort;
use function randomString;
use function str_replace;
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

    public function create(): void
    {
        $body = Store::i()->read('storm_metadata_final');
        $tempClass = [];
        $templates = Store::i()->read('storm_templates');
        $phpStormMeta = Store::i()->read('storm_phpstorm_templates');

        if (defined('STORM_ALT_THEMES') && STORM_ALT_THEMES === true) {
            $altTemplates = Store::i()->read('storm_alt_templates');
        }

        if (empty($templates) === false) {
            foreach ($templates as $key => $template) {
                $key = str_replace(Application::getRootPath() . '/applications/', '', $key);
                $ogkey = $og = $tpl = explode(DIRECTORY_SEPARATOR, $key);
                if (isset($og[2]) && $og[2] === 'email') {
                    continue;
                }
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
                                } catch (Exception|ReflectionException) {
                                }

                                $newParams[$param->getPosition()] = $data;
                            }
                        } catch (Exception $e) {
                        }
                    }
                }

                $app = $og[0] ?? null;
                $folder = $og[4] ?? null;
                $location = $og[3] ?? null;
                unset($og[0], $og[1], $og[2], $og[3], $og[4]);
                $file = str_replace('.phtml', '', implode('.', $og));
                $altTemplates[$folder . '.' . $app . '.' . $location][] = [
                    'func' => $file,
                    'params' => $newParams
                ];
                $phpStormMeta[$ori] = 'stormProxy\\' . $ori;
                $tempClass[$temp][$template['method']] = [
                    'name' => $template['method'],
                    'params' => $newParams
                ];
            }
        }
        ksort($phpStormMeta);
        Store::i()->write($phpStormMeta, 'storm_phpstorm_templates');
        Store::i()->write($tempClass, 'storm_template_class');
        $this->makeTempClasses();
        if (defined('STORM_ALT_THEMES') && STORM_ALT_THEMES === true) {
            Store::i()->write($altTemplates, 'storm_alt_templates');
            $this->makeAltTemplates();
        }

        $body[] = <<<EOF
    registerArgumentsSet('Locations', 'admin','front','global');
    expectedArguments(\\IPS\\Theme::getTemplate(), 2, argumentsSet('Locations'));
    expectedArguments(\\IPS\\Output::js(), 2, argumentsSet('Locations'));
    expectedArguments(\\IPS\\Output::css(), 2, argumentsSet('Locations'));
EOF;

        $toWrite = [];

        foreach (Store::i()->read('storm_extensions') as $key => $val) {
            $toWrite[] = "'" . $key . "'";
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

        $body[] = <<<EOF
        override(\IPS\Theme::getTemplate(), map([
EOF;
        $templates = Store::i()->read('storm_phpstorm_templates');

        foreach ($templates as $ori => $template) {
            $body[] = "'{$ori}' => '{$template}',";
        }

        $body[] = "]));";

        $body[] = <<<EOF
    override(\\IPS\\nucleus\\Template::get(), map([
EOF;
        $altTemplates = Store::i()->read('storm_alt_templates');

        $parts = '';
        foreach ($altTemplates as $k => $v) {
            $ns = str_replace('.', '_', $k);
            $parts .=  "'{$k}' => 'stormProxy\\{$ns}',\n";
        }

        $body[] = $parts;

        $body[] = "]));";

        $body[] = <<<EOF
    override(\\IPS\\storm\\Tpl::get(), map([
EOF;
        $body[] = $parts;

        $body[] = "]));";

        Store::i()->write($body, 'storm_metadata_final');
    }

    /**
     * @param array $classes
     */
    public function makeTempClasses()
    {
        $classes = Store::i()->read('storm_template_class');
        foreach ($classes as $key => $templates) {
            try {
                $nc = ClassGenerator::i()
                    ->setPath($this->save . '/templates/')
                    ->setNameSpace('stormProxy')
                    ->setClassName($key)
                    ->setFileName($key);

                foreach ($templates as $template) {
                    $nc->addMethod($template['name'], '', $template['params'], ['returnType' => 'string']);
                }

                $nc->save();
            } catch (Exception $e) {
                Debug::log($e);
            }
        }
    }

    public function makeAltTemplates(): void
    {
        //0 = app, 3 = location, 4 = group, 5 =
        if (defined('STORM_ALT_THEMES') && STORM_ALT_THEMES === true) {
            $altTemplates = Store::i()->read('storm_alt_templates');
            $nc = FileGenerator::i()
                ->setPath($this->save)
                ->setFilename('altTemplates');

            foreach ($altTemplates as $k => $v) {
                $ns = str_replace('.', '_', $k);
                $nc = ClassGenerator::i()
                    ->setPath($this->save . DIRECTORY_SEPARATOR . 'altTemplates' )
                    ->setNameSpace('stormProxy')
                    ->setClassName($ns)
                    ->setFileName($ns);
                foreach ($v as $vv) {
                    $nc->addMethod($vv['func'],'',$vv['params'], ['returnType' => 'string']);
                }
                $nc->save();
            }
        }
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
}

