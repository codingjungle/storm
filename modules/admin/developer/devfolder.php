<?php

namespace IPS\storm\modules\admin\developer;

use InvalidArgumentException;
use IPS\Developer\Controller as DeveloperController;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\storm\Application;
use IPS\storm\DevFolder\Applications;
use IPS\storm\Form;

use IPS\storm\Tpl;

use function array_pop;
use function defined;
use function file_exists;
use function in_array;
use function lang;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
 * devfolder
 */
class devfolder extends DeveloperController
{
    /**
     * @brief    Has been CSRF-protected
     */
    public static bool $csrfProtected = true;
    /**
     * Execute
     *
     * @return  void
     */
    public function execute(string $command = 'do'): void
    {
        if (\IPS\NO_WRITES === true) {
            Output::i()
                ->error(
                    'Dev Folder generator can not be used for as NO_WRITES are enabled in constants.php.',
                    '100foo'
                );
        }
        parent::execute($command);
        array_pop(Output::i()->breadcrumb);
        Output::i()->breadcrumb[] = [null,lang('storm_devfolder_title')];
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage()
    {
        $app = Request::i()->appKey;

        if (in_array($app, IPS::$ipsApps, true)) {
            Output::i()->error("{$app} is an IPS app, DevFolder Generator will not generate IPS apps Dev Folder, get the SDK from IPS instead.", '100foo');
        }

        /**
         * @param $data
         */
        $form = Form::create()->setPrefix('storm_devfolder_')->removePrefix();

        $validate = static function ($overwrite) use ($app) {
            if ($overwrite === false) {
                $folders = \IPS\Application::getRootPath() . "/applications/{$app}/dev";

                if (
                    file_exists($folders)
                ) {
                    $lang = lang('storm_devfolder_folder_exist', false, ['sprintf' => $folders]);
                    throw new InvalidArgumentException($lang);
                }
            }
        };
        $form->addHidden('appKey', $app);
        $form->addElement('overwrite', 'yn')
            ->validation($validate);
        $url = Url::internal('app=storm&module=developer&controller=devfolder&do=queue&appKey=' . $app);
        if ($values = $form->values()) {
            Output::i()->redirect($url->setQueryString(['do' => 'queue', 'appKey' => $values['appKey']]));
        }

        Tpl::op(
            $form,
            [
                'storm_devcenter_devfolder_landing',
                false,
                ['sprintf' => [$this->application->get__formattedTitle()]]
            ]
        );
//        Output::i()->title = lang('storm_devfolder_title');
    }

    protected function queue()
    {
        Output::i()->title = lang('storm_devcenter_queue_title');

        $app = Request::i()->appKey;

        Output::i()->output = new MultipleRedirect(
            Url::internal('app=storm&module=developer&controller=devfolder&do=queue&appKey=' . $app),
            static function ($data) use ($app) {
                $next = null;
                $end = false;
                $do = $data['next'] ?? 'language';
                $done = 0;

                switch ($do) {
                    case 'language':
                        (new Applications($app))->language();
                        $done = 25;
                        $next = 'javascript';
                        break;
                    case 'javascript':
                        (new Applications($app))->javascript();
                        $done = 50;
                        $next = 'templates';
                        break;
                    case 'templates':
                        (new Applications($app))->templates();
                        $done = 75;
                        $next = 'email';
                        break;
                    case 'email':
                        (new Applications($app))->email();
                        $done = 100;
                        $next = 'default';
                        break;
                    default:
                        $end = true;
                        break;
                }

                if ($end) {
                    if ($app === 'core') {
                        (new Applications($app))->core();
                    }

                    return null;
                }

                $language = lang('storm_devcenter_total_done', false, [
                    'sprintf' => [
                        $done,
                        100,
                    ],
                ]);

                return [['next' => $next], $language, $done];
            },
            static function () {
                $app = Request::i()->appKey;
                $app = lang("__app_{$app}");
                $msg = lang('storm_devcenter_completed', false, ['sprintf' => [$app]]);
                $url = Url::internal('app=storm&module=developer&controller=devfolder&appKey='.$app);
                /* And redirect back to the overview screen */
                Output::i()->redirect($url, $msg);
            }
        );
    }
}
