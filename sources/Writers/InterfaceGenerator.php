<?php

/**
 * @brief       InterfaceGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */

namespace IPS\storm\Writers;

use IPS\Patterns\Singleton;
use IPS\storm\Writers\Traits\ClassMethods;
use IPS\storm\Writers\Traits\Constants;
use IPS\storm\Writers\Traits\Properties;

/**
 * Class _ClassGenerator
 *
 * @package  IPS\storm\Writers
 */
class InterfaceGenerator extends GeneratorAbstract
{
    use ClassMethods;
    use Constants;
    use Properties;


    protected static ?Singleton $instance = null;
    protected bool $doImports = true;

    /**
     * @param        $name
     * @param string $body
     * @param array $params
     * @param array $extra
     *
     * @return $this
     */
    public function addMethod($name, string $body, array $params = [], array $extra = []): static
    {
        $this->methods[trim($name)] = [
            'name'       => $name,
            'static'     => $extra['static'],
            'visibility' => $extra['visibility'],
            'final'      => $extra['final'],
            'document'   => $extra['document'],
            'params'     => $params,
            'returnType' => $extra['returnType'],
        ];

        return $this;
    }

    protected function writeBody(): void
    {
        $tab = $this->tab;

        foreach ($this->methods as $name => $method) {
            if (isset($this->removeMethods[$name])) {
                continue;
            }
            $this->output("\n{$tab}");
            if ($method['document'] && is_array($method['document'])) {
                $this->output("\n");
                $this->output($tab . "/**\n");
                foreach ($method['document'] as $item) {
                    $this->output("{$tab}* {$item}\n");
                }
                $this->output("{$tab}*/\n{$tab}");
            }

            $final = null;
            $static = null;

            if (isset($method['final']) && $method['final'] === true) {
                $final = 'final ';
            }

            if (isset($method['static']) && $method['static'] === true) {
                $static = ' static';
            }

            $this->output($final . $method['visibility'] . $static . ' function ' . $name . '(');

            if (empty($method['params']) !== true && is_array($method['params'])) {
                $built = [];

                foreach ($method['params'] as $param) {
                    if (!isset($param['name'])) {
                        continue;
                    }
                    $p = ' ';
                    if (isset($param['hint'])) {
                        $p .= $param['hint'] . ' ';
                    }

                    $p .= '$' . $param['name'];

                    if (isset($param['value'])) {
                        $val = '';
                        if ($param['value'] === '[]') {
                            $val = '[]';
                        } else {
                            if ($param['value'] === 'true' || $param['value'] === 'false') {
                                $val = $param['value'];
                            } else {
                                if ($param['value'] === 'null') {
                                    $val = 'null';
                                } else {
                                    if (is_string($param['value'])) {
                                        $val = " '" . $param['value'] . "'";
                                    }
                                }
                            }
                        }
                        $p .= ' = ' . $val;
                    }
                    $built[] = $p;
                }
                $this->output(implode(', ', $built));
            }
            $this->output(')');

            if (isset($method['returnType']) && $method['returnType']) {
                $this->output(': ' . $method['returnType']);
            }

            $this->output(';');
        }
        $this->output("\n}");
    }

    public function writeSourceType(): void
    {
        $this->output("\ninterface {$this->className}");
        $this->output("\n{");
    }
}
