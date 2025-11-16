<?php

/**
 * @brief       Imports Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */

namespace IPS\storm\Writers\Traits;

use Exception;
use InvalidArgumentException;

use function array_pop;
use function class_exists;
use function count;
use function explode;
use function interface_exists;
use function mb_strtolower;
use function mb_substr;
use function str_replace;
use function trait_exists;

trait Imports
{
    /**
     * list of class import FQN's
     *
     * @var array
     */
    public array $imports = [];

    /**
     * list of function import FQN's
     *
     * @var array
     */
    protected array $importsFunctions = [];

    protected array $importsConst = [];

    public function addImportConstant(string $import): static
    {
        if ($this->checkForImportConstant($import)) {
            throw new InvalidArgumentException('This constant exist as a import!');
        }
        $this->importsConst[$import] = ['class' => $import];
        return $this;
    }

    public function checkForImportConstant($import): bool
    {
        return isset($this->importsConst[$import]);
    }

    public function addImportFunction(string $import, $alias = null, $throw = false): static
    {
        if ($alias !== null) {
            $hash = $alias;
        } else {
            $parts = explode('\\', $import);
            $class = array_pop($parts);
            $hash = $class;
        }
        if (($this->checkForImportFunction($class) || $this->checkForImportFunction($alias)) && $throw === true) {
            throw new InvalidArgumentException('This function exist as a import! ' . $class);
        }
        $this->importsFunctions[$hash] = ['class' => $import, 'alias' => $alias];
        return $this;
    }

    public function checkForImportFunction($import)
    {
        return isset($this->importsFunctions[$import]);
    }

    public function addImport(string $import, string $alias = null): mixed
    {
        $skipOn = [
            'array' => 1,
            'self' => 1,
            'callable' => 1,
            'bool' => 1,
            'float' => 1,
            'int' => 1,
            'string' => 1,
            'iterable' => 1,
            'object' => 1,
        ];

        if (isset($skipOn[mb_strtolower($import)])) {
            return $import;
        }

        $parts = explode('\\', $import);
        $class = array_pop($parts);
        $hash = $class;

        if ($alias !== null) {
            $hash = $alias;
        }

        $continue = true;

        if ($this->getNameSpace() . '\\' . $class === $import) {
            $continue = false;
        }

        $return = $this->canMakeImport($import);

        if ($continue) {
            $import = ltrim($import, '\\');
        }

        $exists = $this->existsCheck($import);

        if (
            $continue === true &&
            (
                $import === 'Throwable' ||
                $import === '\\Throwable' ||
                $exists === true
            ) &&
            $this->checkForImport($class) === false &&
            $this->checkForImport($alias) === false
        ) {
            $this->imports[$hash] = ['class' => $import, 'alias' => $alias];
        }

        if (
            $continue === true &&
            $return !== $import &&
            $this->checkForImport($class) === false &&
            $this->checkForImport($alias) === false
        ) {
            $this->imports[$hash] = ['class' => $import, 'alias' => $alias];
        }

        if (
            $return === $import &&
            count(explode('\\', $import)) >= 2
        ) {
            $check = mb_substr($return, 0, 1);
            if ($check !== '\\') {
                $return = '\\' . $return;
            }
        }

        return $return;
    }

    public function canMakeImport($class): mixed
    {
        $nsClass = explode('\\', $class);
        $newClass = array_pop($nsClass);
        $testClass = '\\' . $this->getNameSpace() . '\\' . $newClass;

        try {
            if (class_exists($testClass)) {
                return $class;
            }
        } catch (Exception $e) {
        }

        foreach ($this->imports as $import) {
            $nsImport = explode('\\', $import['class']);
            $importClass = array_pop($nsImport);
            if ($import['class'] !== $class && $newClass === $importClass) {
                if ($import['alias'] === null) {
                    $newClass = $importClass;
                } else {
                    $newClass = $import['alias'];
                }
            }
        }

        return $newClass;
    }

    protected function existsCheck($class): bool
    {
        if (
            class_exists($class) ||
            class_exists('\\' . $class)
        ) {
            return true;
        }

        if (
            interface_exists($class) ||
            interface_exists('\\' . $class)
        ) {
            return true;
        }

        if (
            trait_exists($class) ||
            trait_exists('\\' . $class)
        ) {
            return true;
        }

        return false;
    }

    public function checkForImport($import): bool
    {
        return isset($this->imports[$import]);
    }

    public function getImportFunctions(): array
    {
        return $this->importsFunctions;
    }

    public function getImportConstants(): array
    {
        return $this->importsConst;
    }

    public function getImports(): array
    {
        return $this->imports;
    }

    public function wrapUp(): void
    {
        $replacement = '';
        if (empty($this->imports) !== true) {
            foreach ($this->imports as $import) {
                $replacement .= $this->buildImport($import);
            }
        }

        if (empty($this->importsFunctions) !== true) {
            foreach ($this->importsFunctions as $import) {
                $replacement .= $this->buildImport($import, 'function');
            }
        }

        if (empty($this->importsConst) !== true) {
            foreach ($this->importsConst as $import) {
                $replacement .= $this->buildImport($import, 'const');
            }
        }

        $this->toWrite = str_replace('#generator_token_imports#', $replacement, $this->toWrite);
    }

    /**
     * @param      $import
     * @param null $type
     */
    protected function buildImport($import, $type = null): string
    {
        $output = '';
        $output .= "\nuse ";

        if ($type !== null) {
            $output .= $type . ' ';
        }
        $output .= $import['class'];

        if (isset($import['alias']) && $import['alias']) {
            $output .= ' as ' . $import['alias'];
        }

        $output .= ';';

        return $output;
    }
}
