<?php

namespace IPS\storm\ThemeHooks;

use IPS\storm\Settings;

class CoreAdminGlobalGlobalTemplate
{
    public function content()
    {
        if (Settings::i()->storm_profiler_admin_enabled === true) {
            return '<!--ipsQueryLog-->';
        }
    }
}
