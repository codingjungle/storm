<?php

namespace IPS\storm\Proxy\Generator;

use function implode;

class ErrorCodes
{
    public static function run(): void
    {
        $body = Store::i()->read('storm_metadata_final');
        $errorCodes = Store::i()->read('storm_error_codes');
        $toWrite = [];

        foreach ($errorCodes as $key => $val) {
            $toWrite[] = "'" . $val . "'";
        }

        $toWrite = implode(',', $toWrite);
        $body[] = <<<EOF
    registerArgumentsSet('ErrorCodes', {$toWrite});
EOF;

        $methods = [
            ['f' => '\\IPS\\Output::error()', 'i' => 1]
        ];

        foreach ($methods as $m) {
            $body[] = <<<EOF
    expectedArguments({$m['f']}, {$m['i']}, argumentsSet('Languages'));
EOF;
        }

        Store::i()->write($body, 'storm_metadata_final');
    }
}