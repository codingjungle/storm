<?php

/**
 * @brief       TraitGenerator Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Storm
 * @since       1.0.0
 * @version     1.0.0
 */

namespace IPS\storm\Writers;



use IPS\Patterns\Singleton;
use IPS\storm\Writers\Traits\ClassMethods;
use IPS\storm\Writers\Traits\Constants;
use IPS\storm\Writers\Traits\Imports;
use IPS\storm\Writers\Traits\Properties;

/**
 * Class TraitGenerator
 *
 * @package IPS\storm\Writers
 */
class TraitGenerator extends GeneratorAbstract
{
    use ClassMethods;
    use Constants;
    use Imports;
    use Properties;


    protected static ?Singleton $instance = null;
    /**
     * class type, final/abstract
     *
     * @var string
     */
    protected string $type = '';

    protected bool $doImports = true;

    protected function writeBody(): void
    {
        $this->writeConst();
        $this->writeProperties();
        $this->writeMethods();
        $this->output("\n}");
    }

    public function writeSourceType(): void
    {
        $this->output("\ntrait {$this->className}");
        $this->output("\n{\n");
    }
}
