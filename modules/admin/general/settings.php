<?php

namespace IPS\storm\modules\admin\general;

use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Member;
use IPS\Output;
use IPS\storm\Form;
use IPS\storm\Settings as StormSettings;
use IPS\Http\Url;

use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * settings
 */
class settings extends Controller
{
    /**
     * Execute
     *
     * @return  void
     */
    public function execute(): void
    {
        Dispatcher::i()->checkAcpPermission('settings_manage');
        parent::execute();
    }

    /**
     * ...
     *
     * @return  void
     */
    protected function manage(): void
    {
        $form = Form::create()->setObject(StormSettings::i());
        $form->addTab('storm_profiler');
        $form->addHeader('storm_profiler_bar');
        $form->addElement('storm_profiler_enabled', 'yn')
            ->toggles(
                [
                    'storm_profiler_execution_times_enabled',
                    'storm_profiler_memory_tab_enabled',
                    'storm_profiler_files_enabled',
                    'storm_profiler_database_enabled',
                    'storm_profiler_environment_enabled',
                    'storm_profiler_templates_enabled',
                    'storm_profiler_js_enabled',
                    'storm_profiler_js_vars_enabled',
                    'storm_profiler_css_enabled',
                    'storm_profiler_debug_enabled',
                    'storm_profiler_debug_ajax_enable',
                    'storm_profiler_admin_enabled'
                ]
            );
        $form->addElement('storm_profiler_admin_enabled', 'yn');
        $form->addHeader('storm_profiler_tabs');
        $form->addElement('storm_profiler_execution_times_enabled', 'yn');
        $form->addElement('storm_profiler_memory_tab_enabled', 'yn');
        $form->addElement('storm_profiler_files_enabled', 'yn');
        $form->addElement('storm_profiler_database_enabled', 'yn');
        $form->addElement('storm_profiler_environment_enabled', 'yn');
        $form->addElement('storm_profiler_templates_enabled', 'yn');
        $form->addElement('storm_profiler_js_enabled', 'yn');
        $form->addElement('storm_profiler_js_vars_enabled', 'yn');
        $form->addElement('storm_profiler_css_enabled', 'yn');
        $form->addElement('storm_profiler_debug_enabled', 'yn')->toggles(['storm_profiler_debug_ajax_enable']);
        $form->addElement('storm_profiler_debug_ajax_enable', 'yn');

        $form->addTab('storm_devcenter');
        $form->addElement('storm_devcenter_keep_case', 'yn');

        $form->addTab('storm_proxy');
        $form->addElement('storm_proxy_do_non_owned', 'yn');
        $form->addElement('storm_proxy_write_mixin', 'yn');
        $form->addElement('storm_proxy_alt_templates', 'yn');

        if ($values = $form->values()) {
            $form->saveAsSettings($values);
            Output::i()->redirect(Url::internal('app=storm&module=general&controller=settings'), 'saved');
        }

        Output::i()->title = lang('menu__storm_general_settings');
        Output::i()->output = $form;
    }

    // Create new methods with the same name as the 'do' parameter which should execute it
}
