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

use IPS\storm\Profiler\Debug;

use function defined;
use function file_get_contents;
use function header;
use function str_replace;
use function swapLineEndings;
use function strtoupper;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class Profiler extends GeneratorAbstract
{
    protected bool $includeConstructor = false;

    public function bodyGenerator()
    {
        $debugConstant = strtoupper($this->application->directory) . '_DEBUG_LOG';
        $content = swapLineEndings(
            file_get_contents(
                \IPS\Application::getRootPath('storm') .
                '/applications/storm/data/storm/sources/debug.txt'
            )
        );
        $content = str_replace(
            [
                '{constant}'
            ],
            [
                $debugConstant
            ],
            $content
        );
        $this->generator->addImport(Debug::class, 'ProfilerDebug');
        $this->generator->addClassBody($content);
    }
}
