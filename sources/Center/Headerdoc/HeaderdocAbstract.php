<?php

/**
 * @brief       HeaderdocAbstract Class
 * @author      -storm_author-
 * @copyright   -storm_copyright-
 * @package     IPS Social Suite
 * @subpackage  Dev storm: Dev Center Plus
 * @since       1.0.0
 * @version     -storm_version-
 */

namespace IPS\storm\Center\Headerdoc;

use IPS\Application;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

class HeaderdocAbstract
{
    protected ?Application $application = null;

    final public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function since(): ?string
    {
        //you can override this in the extension
        $version = $this->application->version;
        return empty($version) === true ? 'Pre 1.0.0' : $version;
    }
}
