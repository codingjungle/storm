<?php

namespace IPS\storm;

use IPS\Patterns\Singleton;
use IPS\Theme;

class Tpl
{

    public static function get(string $template): mixed
    {
        $pieces = explode('.', $template);

        return Theme::i()->getTemplate(...$pieces);
    }
}