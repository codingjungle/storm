<?php

namespace IPS\storm\modules\admin\developer;

use IPS\Http\Url;
use IPS\Output;
use IPS\Application;
use IPS\Developer\Controller as DeveloperController;
use IPS\Dispatcher;
use IPS\Request;
use IPS\storm\Center\Traits\Assets as TraitsAssets;
use IPS\storm\Head;

use function array_pop;
use function defined;
use function header;
use function lang;

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * sources
 */
class assets extends DeveloperController
{
    use TraitsAssets;

    /**
     * @brief    Has been CSRF-protected
     */
    public static bool $csrfProtected = true;

    /**
     * @var null|Application
     */
    protected ?Application $application = null;

    /**
     * @var null|IPS\storm\Center\Assets
     */
    protected ?\IPS\storm\Center\Assets $elements = null;

    public function execute(string $command = 'do'): void
    {
        if (\IPS\NO_WRITES === true) {
            Output::i()
                ->error(
                    'Assets generator can not be used for as NO_WRITES are enabled in constants.php.',
                    '102foo'
                );
        }
        Head::i()->css(['global_storm']);
        //Sources::menu();
        $app = (string)Request::i()->appKey;
        if (!$app) {
            $app = 'core';
        }
        $this->application = Application::load($app);

        $this->elements = new \IPS\storm\Center\Assets($this->application);
        parent::execute($command);
        $og = Output::i()->sidebar['actions']['apps'];
        $newMenus = [];
        foreach ($og['menu'] as $key => $menu) {
            if (isset($menu['link'])) {
                $link = (string) $menu['link'];
                if (str_contains($link, 'app=core&module=developer&controller=assets')) {
                    $menu['link'] = Url::internal('app=storm&module=developer&controller=assets&appKey=' . $key);
                }
            }
            $newMenus[$key] = $menu;
        }
        $og['menu'] = $newMenus;
        Output::i()->sidebar['actions']['apps'] = $og;
        array_pop(Output::i()->breadcrumb);
        Output::i()->breadcrumb[] = [null,'Assets'];
    }
}
