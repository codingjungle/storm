<?php

/**
 * @brief       Properties Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */

namespace IPS\storm\Writers\Traits;

use IPS\storm\Writers\ClassGenerator;

use function trim;

use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;

trait Properties
{
    /**
     * an array of class properties
     *
     * @var array
     */
    protected array $properties = [];

    /**
     * @param string $name
     * @param        $value
     * @param array $extra
     *
     * @return $this
     */
    public function addProperty(string $name, mixed $value = null, array $extra = []): static
    {
        $this->properties[$name] = [
            'name' => $name,
            'value' => $value,
            'document' => $extra['document'] ?? ['@inheritdoc'],
            'static' => $extra['static'] ?? false,
            'visibility' => $extra['visibility'] ?? T_PUBLIC,
            'type' => $extra['type'] ?? 'string',
            'hint' => $extra['hint'] ?? 'string',
        ];
        return $this;
    }

    public function removeProperty(string $name): static
    {
        unset($this->properties[$name]);
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getPropertyValue($property): ?string
    {
        $property = $this->getProperty($property);
        if ($property !== null && isset($property['value']) && $property['value'] !== null) {
            $value = trim($property['value'], '"');
            $value = trim($value, "'");

            return $value;
        }

        return null;
    }

    public function getProperty($name): ?string
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * @param                 $name
     * @param array $extra
     */
    public function addPropertyTag($name, array $extra = []): static
    {
        $doc = '';
        $type = $extra['type'] ?? null;
        if ($type === 'write') {
            $doc .= '@property-write';
        } else {
            if ($type === 'read') {
                $doc .= '@property-read';
            } else {
                $doc .= '@property';
            }
        }

        if (isset($extra['hint']) && $extra['hint']) {
            $doc .= ' ' . $extra['hint'];
        }

        $doc .= ' $' . $name;

        if (isset($extra['comment']) && $extra['comment']) {
            $doc .= ' ' . $extra['comment'];
        }

        $this->classComment[$name] = $doc;

        return $this;
    }

    public function getPropertyTag($name): ?string
    {
        return $this->classComment[$name] ?? null;
    }

    protected function writeProperties(): void
    {
        if (empty($this->properties) !== true) {
            foreach ($this->properties as $property) {
                $this->output("\n{$this->tab}");

                if ($property['document']) {
                    $this->output("/**\n");
                    foreach ($property['document'] as $item) {
                        $this->output("{$this->tab}* {$item}\n");
                    }
                    $this->output($this->tab . "*/\n{$this->tab}");
                }
                $visibility = $property['visibility'];

                if ($visibility === T_PUBLIC) {
                    $visibility = 'public ';
                } elseif ($visibility === T_PROTECTED) {
                    $visibility = 'protected ';
                } elseif ($visibility === T_PRIVATE) {
                    $visibility = 'private ';
                } elseif ($visibility === null) {
                    $visibility = '';
                }

                $this->output($visibility);

                if (isset($property['static']) && $property['static']) {
                    $this->output('static ');
                }

                if (isset($property['hint'])) {
                    $this->output($property['hint'] . ' ');
                }

                $this->output('$' . $property['name']);
                $value = $property['value'] ?? null;
                $value = trim(ClassGenerator::convertValue($value));
                $this->output(' = ' . $value);
                $this->output(";\n");
            }
        }
    }
}
