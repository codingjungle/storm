<?php

namespace IPS\storm;

use IPS\Output;
use IPS\Patterns\Singleton;
use IPS\Theme;

class Tpl
{

    public static function get(string $template): mixed
    {
        $pieces = explode('.', $template);

        return Theme::i()->getTemplate(...$pieces);
    }

    public static function op(mixed $content, ?array $title = null): void
    {
        Output::i()->output = (string) $content;
        if ($title !== null) {
            Output::i()->title = lang(...$title);
        }
    }
}