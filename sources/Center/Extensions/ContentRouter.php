<?php

/**
 * @brief       ContentRouter Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev Toolbox: Dev Center Plus
 * @since       1.0.1
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Extensions;

use function count;
use function defined;
use function header;
use function implode;
use function is_array;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class ContentRouter
 *
 * @package IPS\toolbox\Center\Extensions
 */
class ContentRouter extends ExtensionsAbstract
{

    /**
     * @inheritdoc
     */
    protected function _content()
    {
        if (is_array($this->classRouter) && count($this->classRouter)) {
            $new = [];
            foreach ($this->classRouter as $class) {
                $new[] = '\\IPS\\' . $this->application->directory . '\\' . $class . '::class';
            }
            $this->classRouter = implode(",", $new);
        } else {
            $this->classRouter = null;
        }

        return $this->_getFile($this->extension);
    }

    /**
     * @inheritdoc
     */
    public function elements()
    {
        $this->form->addElement('use_default')->toggles(['module', 'classRouter'], true);
        $this->form->addElement('module')->required();
        $this->form->addElement('classRouter', 'stack')->prefix('\\IPS\\' . $this->application->directory . '\\')->required();
    }
}
