<?php

/**
 * @brief       ClassMethods Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       4.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Writers\Traits;

use Exception;
use IPS\storm\Writers\ClassGenerator;
use IPS\storm\Writers\GeneratorAbstract;
use ReflectionNamedType;

use function array_key_exists;
use function array_pop;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function method_exists;
use function trim;

use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;

trait ClassMethods
{
    /**
     * an array of class method's
     *
     * @var array
     */
    protected array $methods = [];
    protected array $body = [];

    public function addClassBody($body): void
    {
        $this->body[] = $body;
    }

    public function writeMethods(): void
    {
        if (empty($this->body) === false) {
            $this->output(implode("\n\n", $this->body));
        }

        if (empty($this->methods) === false) {
            foreach ($this->methods as $name => $method) {
                if (isset($this->removeMethods[$name])) {
                    continue;
                }
                $this->output("\n\n");
                if ($method['document'] && is_array($method['document'])) {
                    $this->output($this->tab . "/**\n");
                    $returned = false;

                    foreach ($method['document'] as $item) {
                        if (mb_strpos($item, '@return') === 0) {
                            $this->output("{$this->tab}*\n");
                            $returned = true;
                        }
                        $this->output("{$this->tab}* {$item}\n");

                        if ($returned === false && mb_strpos($item, '@') === false) {
                            $this->output("{$this->tab}*\n");
                        }
                    }
                    $this->output("{$this->tab}*/\n");
                }

                $final = null;
                $static = null;
                $abstract = null;

                if (isset($method['abstract']) && $method['abstract'] === true) {
                    $abstract = 'abstract ';
                }
                if (isset($method['final']) && $method['final'] === true) {
                    $final = 'final ';
                }

                if (isset($method['static']) && $method['static'] === true) {
                    $static = ' static';
                }

                $visibility = $method['visibility'];

                if ($visibility === T_PUBLIC) {
                    $visibility = 'public';
                } elseif ($visibility === T_PROTECTED) {
                    $visibility = 'protected';
                } elseif ($visibility === T_PRIVATE) {
                    $visibility = 'private';
                } else {
                    $visibility = 'public';
                }

                $this->output($this->tab . $abstract . $final . $visibility . $static . ' function ' . $name . '(');

                if (empty($method['params']) !== true && is_array($method['params'])) {
                    $this->writeParams($method['params']);
                }

                $this->output(')');

                if (isset($method['returnType']) && $method['returnType']) {
                    $this->output(': ' . $method['returnType']);
                }

                $body = $this->replaceMethods[$name] ?? trim($method['body']);
                if ($abstract === null) {
                    $wrap = false;
                    if (mb_strpos($body, '{') !== 0) {
                        $wrap = true;
                    }

                    $this->output("{\n\n{$this->tab}{$this->tab}");
                    $this->output('' . $body . '');
                    $this->output("\n{$this->tab}}");
                } else {
                    $this->output(";");
                }
                if (isset($this->afterMethod[$name])) {
                    $this->output("\n");

                    foreach ($this->afterMethod[$name] as $after) {
                        $this->output($this->tab . $this->tab2space($after) . "\n");
                    }
                }
            }
        }
    }

    public function writeParams(array $params ): void
    {
            $this->output(' ');
        $built = $this->buildParams($params);


            $this->output($built);
            $this->output(' ');
    }

    /**
     * @param        $name
     * @param string $body
     * @param array $params
     * @param array $extra
     *
     * @return $this
     */
    public function addMethod($name, string $body, array $params = [], array $extra = []): void
    {
        $this->methods[trim($name)] = [
            'name'       => $name,
            'abstract'   => $extra['abstract'] ?? false,
            'static'     => $extra['static'] ?? false,
            'visibility' => $extra['visibility'] ?? 'public',
            'final'      => $extra['final'] ?? false,
            'document'   => $extra['document'] ?? null,
            'params'     => $params,
            'returnType' => $extra['returnType'] ?? '',
            'body'       => $body,
        ];
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getMethod($name): ?array
    {
        return $this->methods[$name] ?? null;
    }

    public function addMixin($class, $doImport = false): void
    {
        $og = explode('\\', $class);
        if ($doImport === true && count($og) >= 2) {
            $this->addImport($class);
            $class = array_pop($og);
        }
        if (mb_substr($class, 0, 1) !== '\\') {
            $class = '\\' . $class;
        }
        $this->classComment[] = '@mixin ' . $class;
    }
}
