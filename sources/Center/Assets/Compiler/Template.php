<?php

/**
 * @brief       Template Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Assets\Compiler;

use function count;
use function implode;
use function str_starts_with;

/**
 * Class Template
 *
 * @package IPS\storm\Center\Dev\Compiler
 */
class Template extends CompilerAbstract
{
    /**
     * @inheritdoc
     */
    public function content(): string
    {
        $this->extension = 'phtml';
        $params = [];
        $arguments = $this->arguments ?: [];
        /* @var array $arguments */
        if (!empty($arguments)) {
            foreach ($arguments as $argument) {
                $hint = null;
                if (isset($argument['key']) && empty($argument['key']) === false) {
                    $hint = $argument['key'] . ' ';
                }
                $arg = $argument['value'];
                if (empty($arg) === false) {
                    if (!str_starts_with($arg, '$')) {
                        $arg = '$' . $arg;
                    }
                    $params[] = $hint . $arg;
                }
            }
        }

        $params = empty($params) === false ? implode(',', $params) : '';

        return $this->replace('{params}', $params, $this->getFile('template'));
    }
}
