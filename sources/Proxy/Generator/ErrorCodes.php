<?php

namespace IPS\storm\Proxy\Generator;

use IPS\Patterns\Singleton;
use IPS\storm\Proxy;

use function array_filter;

class ErrorCodes extends GeneratorAbstract
{
    protected static ?Singleton $instance = null;

    public function create() : void
    {
        $jsonMeta = \IPS\storm\Proxy\Generator\Store::i()->read('storm_json');
        $jsonMeta['registrar'][] = [
            'signature' => [
                "IPS\\Output::error:1"
            ],
            'provider'  => 'error',
            'language'  => 'php',
        ];

        $jsonMeta['providers'][] = [
            'name'   => 'error',
            'source' => [
                'contributor' => 'return_array',
                'parameter'   => 'stormProxy\\ErrorCodesProvider::get',
            ],
        ];

        \IPS\storm\Proxy\Generator\Store::i()->write($jsonMeta,'storm_json');
        $errorCodes = \IPS\storm\Proxy\Generator\Store::i()->read('storm_error_codes');
        $this->writeClass('Error', 'ErrorCodesProvider', array_filter($errorCodes));
    }
}