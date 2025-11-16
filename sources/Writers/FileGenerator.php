<?php

namespace IPS\storm\Writers;

/**
 * @brief       FileGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       4.0.0
 * @version     -storm_version-
 */

use IPS\Patterns\Singleton;


/**
 * Class FileGenerator
 *
 * @package IPS\storm\Generator
 */
class FileGenerator extends GeneratorAbstract
{
    protected const HASCLASS = false;
    protected static ?Singleton $instance = null;
    protected $body = [];

    public function addBody($body): static
    {
        $this->body[] = $body;
        return $this;
    }

    protected function writeBody(): void
    {
        $body = implode("\n", $this->body);
        $this->output($body);
    }

    protected function writeSourceType()
    {
    }

}
