<?php

/**
 * @brief       Constants Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */


namespace IPS\storm\Writers\Traits;

use function trim;

trait Constants
{

    /**
     * an array of class constants
     *
     * @var array
     */
    protected array $const = [];

    public function addConst(string $name, $value, array $extra = []): static
    {
        $this->const[$this->hash($name)] = [
            'name'       => $name,
            'value'      => $value,
            'document'   => $extra['document'] ?? null,
            'visibility' => $extra['visibility'] ?? null,
        ];
        return $this;
    }

    public function getConstants(): array
    {
        return $this->const;
    }

    public function getConstant($name): ?string
    {
        return $this->const[$this->hash($name)] ?? null;
    }

    protected function writeConst(): void
    {
        if (empty($this->const) !== true) {
            foreach ($this->const as $const) {
                $this->output("\n{$this->tab}");

                if ($const['document']) {
                    $this->output("/**\n");
                    foreach ($const['document'] as $item) {
                        $this->output("{$this->tab}* {$item}\n");
                    }
                    $this->output($this->tab . "*/\n{$this->tab}");
                }

                if ($const['visibility'] !== null) {
                    $this->output($const['visibility'] . ' ');
                }

                $this->output('CONST ');

                $this->output($const['name']);
                if ($const['value']) {
                    $this->output(' = ' . trim($const['value']));
                }
                $this->output(";\n");
            }
        }
    }
}
