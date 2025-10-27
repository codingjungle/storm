<?php

/**
 * @brief       FileStorage Standard
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  dtdevplus
 * @since       -storm_since_version-
 * @version     -storm_version-
 */

namespace IPS\storm\DevCenter\Extensions;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Class _CreateMenu
 *
 * @package IPS\toolbox\DevCenter\Extensions
 */
class CreateMenu extends ExtensionsAbstract
{

    /**
     * @inheritdoc
     */
    protected function _content()
    {
        $this->link = 'app=' . $this->application->directory . '&' . $this->link;
        $this->seo = $this->seo ? "'" . $this->seo . "'" : null;
        $this->seoTitle = $this->seoTitle ? "'" . $this->seoTitle . "'" : null;

        return $this->_getFile($this->extension);
    }

    /**
     * @inheritdoc
     */
    public function elements()
    {
        $this->form->element('use_default')->toggles(['key', 'link', 'seo', 'seoTitle'], true);
        $this->form->addElement('key')->required();
        $this->form->addElement('link')->required()->prefix('app=' . $this->application->directory . '&');
        $this->form->addElement('seo');
        $this->form->addElement('seoTitle');
    }
}
