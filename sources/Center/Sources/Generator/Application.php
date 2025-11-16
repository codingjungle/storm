<?php

/**
 * @brief       Application Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use IPS\Output;
use IPS\storm\Proxy\Proxyclass;
use IPS\Theme;

use function class_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function swapLineEndings;

class Application extends GeneratorAbstract
{
    protected bool $overrideDir = true;
    protected bool $includeConstructor = false;

    /**
     * @inheritdoc
     */
    protected function bodyGenerator(): void
    {
        $this->dir = $this->application->getApplicationPath() . '/';
        $og = '\\IPS\\' . $this->application->directory . '\\ApplicationOG';
        if (!class_exists($og)) {
            $path = $this->application->getApplicationPath();
            $file = $path . '/Application.php';
            $content = swapLineEndings(file_get_contents($file));
            $content = str_replace('_Application', '_ApplicationOG', $content);
            $newPath = $path . '/sources/ApplicationOG/';
            if (!is_dir($newPath)) {
                mkdir($newPath, 0777, true);
            }
            file_put_contents($newPath . '/ApplicationOG.php', $content);
            Proxyclass::i()->build($newPath . '/ApplicationOG.php');
        }
        $this->brief = 'Application Class';
        $this->extends = $og;
        $body = '';
        $this->generator->addImport(Output::class);
        $this->generator->addImport(Theme::class);
        $this->generator->addImportFunction('array_merge');
        $this->generator->addImportFunction('array_combine');
        $this->generator->addImportFunction('strrev');
        $this->generator->addImportFunction('dechex');
        $this->generator->addImportFunction('crc32');
        $this->generator->addImportFunction('mb_substr');
        $this->generator->addImportFunction('mb_strlen');
        $this->generator->addImportFunction('str_pad');
        $this->generator->addImportFunction('random_int');
        $this->generator->addImportFunction('floor');
        $this->generator->addImportConstant('STR_PAD_LEFT');


        $this->generator->addClassBody($body);
    }
}
