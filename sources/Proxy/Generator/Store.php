<?php

namespace IPS\storm\Proxy\Generator;

use IPS\Patterns\Singleton;
use IPS\storm\Application;
use IPS\storm\Proxy;
use IPS\storm\Writers\FileGenerator;
use OutOfRangeException;

use function file_exists;
use function file_get_contents;
use function json_decode;
use function json_encode;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;

class Store extends Singleton
{
    /**
     * @brief Singleton Instances
     * @note  This needs to be declared in any child class.
     * @var static
     */
    protected static ?Singleton $instance = null;

    protected string $path = '';

    public function __construct()
    {
        Application::initAutoloader();
        $this->path = Proxy::i()->path . 'store' . DIRECTORY_SEPARATOR;
    }

    public function read(string $file)
    {
        $file = $this->path . $file . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?? [];
        }

        return [];
    }

    public function write(array $data, string $file)
    {
        try {
            $writer = new FileGenerator();
            $writer->setFileName($file)
                ->setExtension('json')
                ->setPath($this->path)
                ->addBody(json_encode($data, JSON_PRETTY_PRINT))
                ->save();
        } catch (OutOfRangeException $e) {
        }
    }
}
