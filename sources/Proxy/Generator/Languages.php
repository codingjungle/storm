<?php

namespace IPS\storm\Proxy\Generator;

use IPS\Lang;

use function implode;

class Languages
{
    public static function run(): void
    {
        $toWrite = [];
        $lang = Lang::load(Lang::defaultLanguage());
        $body = Store::i()->read('storm_metadata_final');
        foreach ($lang->words as $key => $val) {
            $toWrite[] = "'" . $key . "'";
        }
        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('Languages', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Lang::addToStack()', 'i' => 0],
            ['f' => '\\IPS\\Lang::checkKeyExists()', 'i' => 0],
            ['f' => '\\IPS\\Lang::get()', 'i' => 0],
            ['f' => '\\IPS\\Lang::saveCustom()', 'i' => 1],
            ['f' => '\\IPS\\Lang::copyCustom()', 'i' => 1],
            ['f' => '\\IPS\\Lang::deleteCustom()', 'i' => 1],
            ['f' => '\\IPS\\Lang::copyCustom()', 'i' => 2],
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('Languages'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}