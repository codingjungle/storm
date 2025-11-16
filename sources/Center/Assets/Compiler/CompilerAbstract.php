<?php

/**
 * @brief       CompilerAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Assets\Compiler;

use IPS\Application;
use IPS\storm\Proxy;
use IPS\storm\Shared\Magic;
use IPS\storm\Writers\FileGenerator;

use function explode;
use function file_exists;
use function str_replace;
use function swapLineEndings;

/**
 * @brief      CompilerAbstract Class
 * @property string $group_manual
 * @property string $group_manual_location
 * @property string $group_manual_folder
 * @property string $group
 * @property string $location
 * @property string $_group
 * @property string $app
 * @property string $filename
 * @property string $extension
 * @property string $widgetname
 * @property array $templateName
 * @property array $arguments
 * @property string $mixin
 */
abstract class CompilerAbstract
{
    use Magic;

    //    use Read;
    //    use Replace;
    //    use Write;

    /**
     * @var Application
     */
    protected ?Application $application = null;

    protected ?string $type = null;


    /**
     * cause it looks pretty?
     * CompilerAbstract constructor.
     *
     * @param array $values
     * @param Application $application
     * @param string $type
     */
    public function __construct(array $values, Application $application, string $type)
    {
        $this->type = $type;
        $this->application = $application;
        foreach ($values as $key => $val) {
            $key = $this->replace('storm_devcenter_assets_', '', $key);
            if (!empty($val)) {
                $this->{$key} = $val;
            } else {
                $this->{$key} = null;
            }
        }
    }

    protected function replace(string|array $search, string|array $replace, string $content): string
    {
        return str_replace($search, $replace, $content);
    }

    /**
     * process the values for file creation
     */
    final public function process(): string
    {
        $this->app = $this->application->directory;

        if (!$this->group_manual) {
            $this->location = $this->group_manual_location;
            $this->group = $this->group_manual_folder;
        } else {
            $locationGroup = $this->_group;
            [$this->location, $this->group] = explode(':', $locationGroup);
        }

        $content = $this->content();
        $file = $this->filename;

        $dir = Application::getRootPath() . '/applications/' . $this->app . '/dev/';
        if ($this->type === 'template') {
            $dir .= 'html/';
        } else {
            $dir .= 'js/';
        }

        $dir .= $this->location . '/' . $this->group;

        FileGenerator::i()
            ->setPath($dir)
            ->setFileName($file)
            ->setExtension($this->extension)
            ->addBody($content)
            ->save();
        $lockFile = Proxy::i()->path . '/lock.txt';
        if (file_exists($lockFile) && $this->extension === 'phtml') {
            Proxy::i()->run('phtml');
        }
        return $file;
    }

    /**
     * sets and gathers the class body blank
     *
     * @return string
     */
    abstract protected function content(): string;

    protected function getFile(string $file): ?string
    {
        $path = \IPS\storm\Application::getRootPath() . '/applications/storm/data/storm/assets/' . $file . '.txt';
        return file_exists($path) ? swapLineEndings(file_get_contents($path)) : null;
    }
}
