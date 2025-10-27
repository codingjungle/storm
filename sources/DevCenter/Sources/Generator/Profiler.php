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

namespace IPS\storm\DevCenter\Sources\Generator;

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
        $profilerClass = '\\IPS\\storm\\Profiler\\' . $profiler . '::class';

        if ($profiler === 'Debug') {
            $body = <<<EOF
        if( defined('DTPROFILER') && DTPROFILER && class_exists( $profilerClass) ){
            \$class =  $profilerClass;
            if( method_exists(\$class, \$method ) ){
                \$class::{\$method}(...\$args);
            }
        }
        else if( \$method === 'add' ){
            list( \$message, \$key, ) = \$args;
            \IPS\Log::debug(\$message, \$key);
        }   
EOF;
        } else {
            $body = <<<EOF
        if( defined('DTPROFILER') && DTPROFILER && class_exists( $profilerClass) ){
            \$class =  $profilerClass;
            if( method_exists(\$class, \$method ) ){
                \$class::{\$method}(...\$args);
            }
        }
EOF;
        }

        $params = [
            ['name' => 'method'],
            ['name' => 'args'],
        ];

        $extra = [
            'static' => true,
        ];

        $this->generator->addmethod('__callStatic', $body, $params, $extra);
    }
}
