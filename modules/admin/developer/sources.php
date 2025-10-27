<?php

namespace IPS\storm\modules\admin\developer;

use IPS\Application;
use IPS\Developer\Controller as DeveloperController;
use IPS\Http\Url;
use IPS\Output;

use IPS\Request;

use IPS\storm\Form\Element;
use IPS\storm\Head;
use IPS\storm\Proxy\Generator\Cache;

use function _p;
use function array_shift;
use function defined;
use function explode;
use function implode;
use function json_decode;
use function ksort;
use function ltrim;
use function preg_grep;
use function preg_quote;
use function str_replace;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * sources
 */
class sources extends DeveloperController
{
    use \IPS\storm\DevCenter\Traits\Sources;
    /**
     * @var bool
     */
    public static bool $csrfProtected = true;
    /**
     * @var Application
     */
    protected ?Application $application = null;

    /**
     * @var \IPS\storm\DevCenter\Sources
     */
    protected $elements;
    protected $front = false;
    public function execute(string $command = 'do'): void
    {
        Head::i()->css(['global_storm']);
        //Sources::menu();
        $app = (string)Request::i()->appKey;
        if (!$app) {
            $app = 'core';
        }
        $this->application = Application::load($app);
        $this->elements = new \IPS\storm\DevCenter\Sources($this->application);
        parent::execute($command);
        $og = Output::i()->sidebar['actions']['apps'];
        $newMenus = [];
        foreach($og['menu'] as $key => $menu){
            if(isset($menu['link'])){
                $link = (string) $menu['link'];
                if(str_contains($link, 'app=core&module=developer&controller=sources')){
                    $menu['link'] = Url::internal('app=storm&module=developer&controller=sources&appKey='.$key);
                }
            }
            $newMenus[$key] = $menu;
        }
        $og['menu'] = $newMenus;
       // _p($og,$newMenus);
//        $sideBarApps = str_replace('app=core&module=developer&controller=sources', 'app=storm&module=developer&controller=sources', $sideBarApps);
//        $sideBarApps = json_decode($sideBarApps, true);
////        _p($sideBarApps, $og);
        Output::i()->sidebar['actions']['apps'] = $og;
        array_pop(Output::i()->breadcrumb);
        Output::i()->breadcrumb[] = [null,'Sources'];

    }

}
