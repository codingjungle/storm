<?php

namespace IPS\storm;

use IPS\Patterns\Singleton;
use IPS\Theme;

class Template
{

    public static function get(string $template): \IPS\Theme\Template
    {
        $pieces = explode('.', $template);

        return Theme::i()->getTemplate(...$pieces);
    }
}