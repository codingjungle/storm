<?php

/**
 * @brief       Singleton Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use Exception;
use IPS\Patterns\Singleton as IPSSingleton;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Singleton extends GeneratorAbstract
{
    protected bool $includeConstructor = false;

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function bodyGenerator()
    {
        $this->brief = 'Singleton';
        $this->extends = 'Singleton';
        $this->generator->addImport(IPSSingleton::class);
        $extra = [
            'document' => ['@inheritdocs'],
            'static'   => true,
            'hint' => '?Singleton'
        ];

        $this->generator->addProperty('instance', 'null', $extra);
    }
}
