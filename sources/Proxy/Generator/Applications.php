<?php

namespace IPS\storm\Proxy\Generator;

use function implode;

class Applications
{
    public static function run(): void
    {
        $body = Store::i()->read('storm_metadata_final');
        $apps  = [];

        foreach (Application::roots() as $app) {
            $apps[] = "'" . $app->directory . "'";
        }

        $apps = implode(',', $apps);
        $body[] = <<<EOF
    registerArgumentsSet('applications', {$apps});
EOF;

        $methods = [
            ['f' => '\\IPS\\Application::load()', 'i' => 0],
            ['f' => '\\IPS\\Application::appIsEnabled()', 'i' => 0],
            ['f' => '\\IPS\\Application::appsWithExtension()', 'i' => 0],
            ['f' => '\\IPS\\Application::extension()', 'i' => 0],
            ['f' => '\\IPS\\Application::allExtensions()', 'i' => 0],
            ['f' => '\\IPS\\Email::buildFromTemplate()', 'i' => 0],
            ['f' => '\\IPS\\Lang::saveCustom()', 'i' => 0],
            ['f' => '\\IPS\\Lang::copyCustom()', 'i' => 0],
            ['f' => '\\IPS\\Lang::copyCustom()', 'i' => 3],
            ['f' => '\\IPS\\Lang::deleteCustom()', 'i' => 0],
            ['f' => '\\IPS\\Theme::getTemplate()', 'i' => 1],
            ['f' => '\\IPS\\Output::js()', 'i' => 0],
            ['f' => '\\IPS\\Output::css()', 'i' => 0]
        ];

        foreach ( $methods as $m )
        {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('applications'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}