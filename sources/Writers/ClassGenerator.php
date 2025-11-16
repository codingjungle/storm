<?php

/**
 * @brief       ClassGenerator Class
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
use IPS\storm\Writers\Traits\Imports;
use IPS\storm\Writers\Traits\Properties;

/**
 * Class ClassGenerator
 *
 * @package IPS\storm\Writers
 */
class ClassGenerator extends GeneratorAbstract
{
    use ClassMethods;
    use Constants;
    use Imports;
    use Properties;


    protected static ?Singleton $instance = null;
    /**
     * an array of implements
     *
     * @var array
     */
    protected array $interfaces = [];

    /**
     * the parent class
     *
     * @var string
     */
    protected string $extends = '';

    /**
     * class type, final/abstract
     *
     * @var string
     */
    protected string $type = '';

    /**
     * an array of traits class uses
     *
     * @var array
     */
    protected array $classUses = [];

    protected bool $doImports = true;

    /**
     * an array of methods to replace in class
     *
     * @var array
     */
    protected array $replaceMethods = [];

    /**
     * an array of methods to remove from class
     *
     * @var array
     */
    protected array $removeMethods = [];

    protected array $afterMethod = [];


    protected bool $isFinal = false;

    protected bool $isAbstract = false;

    public static function convertValue($value): mixed
    {
        if (is_array($value)) {
            $return = var_export($value, true);

            if (count($value) >= 1) {
                $string = explode("\n", $return);
                $return = '';
                $i = 0;
                foreach ($string as $item) {
                    if ($i !== 0) {
                        $return .= '    ';
                    }

                    $return .= $item . "\n";
                    $i++;
                }
            } else {
                $return = '[]';
            }

            return $return;
        } else {
            $value = empty($value) === false ? trim($value) : $value;
        }

        if ((int)$value || is_numeric($value)) {
            return $value;
        }

        if ($value === false || $value === 'false') {
            return 'false';
        }

        if ($value === true || $value === 'true') {
            return 'true';
        }
        if ($value === null || mb_strtolower($value) === 'null') {
            return 'null';
        }

        if (
            mb_strpos($value, '"') === 0 ||
            mb_strpos($value, "'") === 0 ||
            mb_strpos($value, '[') === 0 ||
            mb_strpos($value, 'array') === 0 ||
            mb_strpos($value, '::') !== false
        ) {
            return $value;
        }

        return "'" . $value . "'";
    }

    public static function paramsFromString($params): string
    {
        $continue = true;
        $rand = 'foo' . random_int(1, 20000) . random_int(1, 20000) . random_int(1, 30000) . md5(
                time() + rand(1, 10000)
            );
        $newParams = [];
        $class = <<<EOF
class {$rand} {
    public function foo({$params}){}
}
EOF;
        if (!class_exists($rand) && eval($class) === false) {
            $continue = false;
        }

        if ($continue) {
            $reflection = new ReflectionClass($rand);
            $methods = $reflection->getMethods();
            foreach ($methods as $method) {
                $params = $method->getParameters();
                $newParams = [];
                /** @var ReflectionParameter $param */
                foreach ($params as $param) {
                    $position = $param->getPosition();
                    $newParams[$position]['name'] = $param->getName();
                    $hint = $param->getType();
                    if ($hint instanceof ReflectionType) {
                        $newParams[$position]['hint'] = $hint->getName();
                        $newParams[$position]['nullable'] = (bool)$hint->allowsNull();
                    }

                    if ($param->isPassedByReference()) {
                        $newParams[$position]['reference'] = true;
                    }
                    $value = 'none';
                    if ($param->isDefaultValueAvailable() === true) {
                        if ($param->isDefaultValueConstant()) {
                            $value = $param->getDefaultValueConstantName();
                        } else {
                            $value = $param->getDefaultValue();
                            if (is_string($value) === true) {
                                $value = "'" . $value . "'";
                            }
                            if (is_string($value) && $value === '') {
                                $value = "''";
                            }
                        }
                    }

                    if ($value !== 'none') {
                        $newParams[$position]['value'] = $value;
                    }
                }
            }
        }

        return $newParams;
    }

    public static function paramFromString($param): string
    {
        $sliced = <<<EOF
<?php

{$param}
EOF;

        $tokens = token_get_all($sliced);
        $count = count($tokens);
        $p = [];
        $hint = null;
        $in = [
            T_ARRAY,
            T_STRING,
            T_CONSTANT_ENCAPSED_STRING,
            T_LNUMBER,
        ];
        $i = 0;
        foreach ($tokens as $token) {
            if (isset($tokens[0]) && $tokens[0] !== T_OPEN_TAG) {
                $type = $token[0] ?? null;
                $value = $token[1] ?? $token;
                if ($value) {
                    if ($type === '[') {
                        $vv = '';
                        for ($ii = $i; $ii < $count; $ii++) {
                            $vv .= $tokens[$ii][1] ?? $tokens[$ii];
                        }

                        $p['value'] = trim($vv);
                    } elseif ($value === '&') {
                        $p['reference'] = true;
                    } elseif ($value === '?') {
                        $p['nullable'] = true;
                    } elseif (in_array($type, $in, true)) {
                        if ($type === T_ARRAY || (!isset($p['hint']) && !isset($p['value']) && !isset($p['name']))) {
                            $hint[] = $value;
                        } else {
                            if ($hint !== null) {
                                $p['hint'] = implode('\\', $hint);
                                $hint = null;
                            }
                            $p['value'] = trim($value);
                        }
                    } elseif ($type === T_VARIABLE) {
                        if ($hint !== null) {
                            $p['hint'] = implode('\\', $hint);
                            $hint = null;
                        }
                        $p['name'] = ltrim(trim($value), '$');
                    }
                }
            }
            $i++;
        }

        return $p;
    }

    public function setType($type): static
    {
        $this->type = $type;
        return $this;
    }

    public function setFinal(): static
    {
        $this->isFinal = true;
        return $this;
    }

    public function setAbstract(): static
    {
        $this->isAbstract = true;
        return $this;
    }


    public function addUse($class): static
    {
        if (is_array($class)) {
            $og = $class;
            $class = implode('\\', $class);
        } else {
            $og = explode('\\', $class);
        }
        if (count($og) >= 2) {
            $class = $this->addImport($class);
        }
        $hash = $this->hash($class);
        $class = ltrim($class, '\\');

        $this->classUses[$hash] = $class;
        return $this;
    }

    public function getClassUses(): array
    {
        return $this->classUses;
    }

    public function getExtends(): string
    {
        return $this->extends;
    }

    /**
     * @param $extends
     *
     * @return $this
     */
    public function setExtends($extends, $import = true): static
    {
        if (is_array($extends)) {
            $og = $extends;
            $extends = implode('\\', $extends);
        } else {
            $og = explode('\\', $extends);
        }

        if (count($og) >= 2) {
            $this->addImport($extends);
            $extends = array_pop($og);
        }

        $this->extends = $extends;
        return $this;
    }

    /**
     * @param $interface
     *
     * @return $this
     */
    public function addInterfaces(string $interface): static
    {
        if (empty($interface) === false) {
            $og = explode('\\', $interface);

            if (count($og) >= 2) {
                $this->addImport($interface);
                $interface = array_pop($og);
            }
            $hash = $this->hash($interface);
            $interface = ltrim($interface, '\\');
            $this->interfaces[$hash] = $interface;
        }
        return $this;
    }

    public function writeSourceType(): void
    {
        $type = null;

        if ($this->isAbstract() === true) {
            $type = 'abstract ';
        }

        if ($this->isFinal() === true) {
            $type = 'final ';
        }

        $this->output("\n{$type}class {$this->className}");

        if ($this->extends) {
            $this->output(" extends {$this->extends}");
        }

        if (empty($this->interfaces) !== true) {
            $this->output(" implements \n" . implode(",\n", $this->interfaces));
        }
        $this->output("\n{\n");
    }

    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    protected function writeBody(): void
    {
        $tab = $this->tab;
        //psr-12 updates
        if (empty($this->classUses) === false) {
            foreach ($this->classUses as $use) {
                {
                    $this->output("{$tab}use " . $use . ";\n");
                }
            }
        }
        $this->writeConst();
        $this->writeProperties();
        $this->writeMethods();
        $this->output("\n}");
    }

    protected function tab2space($line): string
    {
        return str_replace("\t", '    ', $line);
    }
}
