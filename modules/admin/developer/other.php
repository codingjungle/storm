<?php

namespace IPS\storm\modules\admin\developer;

use Exception;
use IPS\Application;
use IPS\Developer\Controller as DeveloperController;
use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\storm\Center\Traits\Assets as TraitsAssets;
use IPS\storm\Head;
use IPS\storm\Tpl;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function array_pop;
use function defined;
use function file_exists;
use function header;
use function in_array;
use function lang;
use function method_exists;

/* To prevent PHP errors (extending class does not exist) revealing path */

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * sources
 */
class other extends DeveloperController
{
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

        parent::execute($command);
        $og = Output::i()->sidebar['actions']['apps'];
        $newMenus = [];
        foreach ($og['menu'] as $key => $menu) {
            if (isset($menu['link'])) {
                $link = (string) $menu['link'];
                if (str_contains($link, 'app=core&module=developer&controller=other')) {
                    $menu['link'] = Url::internal('app=storm&module=developer&controller=other&appKey=' . $key);
                }
            }
            $newMenus[$key] = $menu;
        }
        $og['menu'] = $newMenus;
        Output::i()->sidebar['actions']['apps'] = $og;
        array_pop(Output::i()->breadcrumb);
        Output::i()->breadcrumb[] = [null,'Other'];
    }

    protected function manage(): void
    {
        Head::i()->js(['global_alert']);
        $buttons = [
            [
                'url' => Url::internal('app=storm&module=developer&controller=other&do=addIndexHtml&appKey=' . $this->application->directory),
                'label' => lang('storm_devcenter_other_index_html'),
                'attributes' => 'data-ipsstormalert data-ipsstormalert-msg="' . lang('storm_devcenter_other_index_html_alert') . '" data-ipsstormalert-type="confirm"'
            ]
        ];
        $template = Tpl::get('devcenter.storm.global')->other($buttons);
        Tpl::op($template, ['storm_other_landing', false, ['sprintf' => [$this->application->get__formattedTitle()]]]);
    }

    protected function addIndexHtml(): void
    {
        \IPS\storm\Application::initAutoloader();
        $app = $this->application;

        $exclude = [
            '.git',
            '.idea',
            'vendor',
            'Vendor',
            '3rdParty',
            '3rd_party',
        ];

        try {
            $finder = new Finder();
            $dir = Application::getRootPath() . '/applications/' . $app->directory;

            if (!file_exists($dir .'/index.html')){
                file_put_contents($dir .'/index.html', '');
            }

            $finder->in($dir);

            foreach ($exclude as $dirs) {
                $finder->exclude($dirs);
            }

            $finder->directories();

            foreach ($finder as $iter) {
                if ($iter->isDir()) {
                    $path = $iter->getPathname();
                    if (!file_exists($path . '/index.html')) {
                        file_put_contents($path . '/index.html', '');
                    }
                }
            }
            Output::i()->json(['message' => lang('storm_devcenter_other_index_html_success')]);
        } catch (Exception $e) {
            Output::i()->json(['message' => $e->getMessage()]);
        }
    }
}
