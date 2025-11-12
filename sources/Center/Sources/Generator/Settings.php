<?php

/**
 * @brief       Settings Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.3.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use IPS\Settings as IPSSettings;
use IPS\storm\Application;
use Throwable;
use UnderflowException;


class Settings extends GeneratorAbstract
{
    protected bool $includeConstructor = false;

    public function bodyGenerator()
    {
        $this->brief = 'Class';
        $this->generator->addImport(UnderflowException::class);
        $this->generator->addImport(Throwable::class);
        $this->generator->addImportFunction('array_combine');
        $this->generator->addImportFunction('array_values');
        $this->generator->addImportFunction('is_array');
        $this->generator->addImportFunction('defined');
        $this->generator->addImportFunction('header');
        $this->generator->addImportFunction('json_decode');

        $this->generator->addClassBody(\file_get_contents(Application::getRootPath('storm').'/applications/storm/data/defaults/settings.txt') );
        $this->generator->addExtends(IPSSettings::class,false);
    }
}
