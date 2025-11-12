<?php

/**
 * @brief       Profiler Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

use function defined;
use function header;
use function mb_strtolower;

class Profiler extends GeneratorAbstract
{
    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $profiler = mb_ucfirst(mb_strtolower($this->type));
        $profilerClass = '\\IPS\\storm\\Profiler\\' . $profiler;
        $this->generator->addImport($profilerClass, 'ProfilerDebug');
        $debugConstant = strtoupper($this->application->directory) . '_DEBUG_LOG';
        $profilerClass .= '::class';
        $body = <<<EOF
        if ( 
            defined('{$debugConstant}') &&
            {$debugConstant} === true &&
            class_exists( {$profilerClass}) 
        )
        {
            ProfilerDebug::log(\$message, \$category, \$level);
        }   
EOF;

        $this->generator->addImport(\Exception::class);
        $params = [
            [
                'name' => 'message',
                'hint' => 'Exception|string|array'
            ],
            [
                'name' => 'category',
                'hint' => '?string',
                'value' => null,
            ],
            [
                'name' => 'level',
                'hint' => '?int',
                'value' => null
            ]
        ];
        $extra = [
            'visibility' => T_PUBLIC,
            'static' => true
        ];

        $this->generator->addmethod('log', $body, $params, $extra);
    }
}
