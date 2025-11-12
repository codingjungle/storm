<?php

/**
 * @brief       Orm Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use InvalidArgumentException;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\storm\Application;
use UnderflowException;

class Orm extends GeneratorAbstract
{
    protected bool $includeConstructor = false;

    public function bodyGenerator()
    {
        $this->brief = 'Trait';
        $this->generator->addImport(InvalidArgumentException::class);
        $this->generator->addImport(Db::class);
        $this->generator->addImport(ActiveRecordIterator::class);
        $this->generator->addImport(UnderflowException::class);

        $this->generator->addImportFunction('array_key_exists');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('implode');
        $this->generator->addImportFunction('json_decode');
        $this->generator->addImportFunction('json_encode');
        $this->generator->addImportFunction('mb_substr');
        $this->generator->addImportFunction('property_exists');
        $this->generator->addImportFunction('strlen');
        $this->generator->addImportFunction('explode');

        $tb = Application::getRootPath('storm');
        $content = \file_get_contents($tb . '/applications/storm/data/storm/sources/orm.txt');

        $this->generator->addClassBody($content);
    }
}
