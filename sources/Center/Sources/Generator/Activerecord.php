<?php

/**
 * @brief       ActiveRecord Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Sources\Generator;

use IPS\Patterns\ActiveRecord as IPSActiveRecord;

class Activerecord extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    protected function bodyGenerator(): void
    {
        $this->brief = 'Active Record';
        $this->extends = IPSActiveRecord::class;
    }
}
