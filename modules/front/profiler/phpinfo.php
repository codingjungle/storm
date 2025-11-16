<?php

namespace IPS\storm\modules\front\profiler;

use Exception;
use IPS\Dispatcher\Controller;
use IPS\Log;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\storm\Application;
use IPS\storm\Proxy\Generator\Store;
use IPS\Theme;
use Throwable;
use IPS\storm\Profiler\Debug;

use function defined;
use function randomString;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * phpinfo
 */
class phpinfo extends Controller
{
    /**
     * Execute
     *
     * @return  void
     */
    public function execute(): void
    {
        if (\IPS\CIC === true || \IPS\CIC2 === true) {
            Output::i()->error('Storm: Dev Toolbox is not available in CIC.', '100STORM');
        }
        parent::execute();
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage(): void
    {
        ob_start();
        phpinfo();
        $content = ob_get_clean();
        try {
            ob_end_clean();
        } catch (Exception $e) {
        }
        $content = preg_replace('#<head>(?:.|\n|\r)+?</head>#miu', '', $content);
        Output::i()->title = 'phpinfo()';
        Output::i()->output = Theme::i()->getTemplate('profiler', 'storm', 'global')->phpinfo($content);
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
}
