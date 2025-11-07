<?php

/**
* @brief      Numbers Singleton
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.0.1
* @version    -storm_version-
*/

namespace IPS\storm\DevToys;

use InvalidArgumentException;
use IPS\Patterns\Singleton;

use function bindec;
use function decbin;
use function dechex;
use function decoct;
use function hexdec;
use function ctype_xdigit;
use function defined;
use function header;
use function octdec;
use function preg_match;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden');
    exit;
}

/**
* Numbers Class
* @mixin Numbers
*/
class Numbers extends Singleton
{
    /**
    * @brief Singleton Instance
    * @note This needs to be declared in any child class
    * @var static
    */
    public static ?Singleton $instance = null;


    public function decimal(int|float $number): array
    {
        if (!(int)$number) {
            throw new InvalidArgumentException('Not valid decimal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => decoct($number),
            'binary' => decbin($number)
        ];
    }

    public function hexa(mixed $hex): array
    {
        $number = hexdec($hex);
        if (!ctype_xdigit($hex)) {
            throw new InvalidArgumentException('Not valid hexadecimal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => $hex,
            'octal' => decoct($number),
            'binary' => decbin($number)
        ];
    }

    public function octal(int $octal): array
    {
        $number = octdec($octal);
        if (!$number) {
            throw new InvalidArgumentException('Not valid octal number!');
        }
        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => $octal,
            'binary' => decbin($number)
        ];
    }

    public function binary(int $bin): array
    {
        preg_match('#^\b[01]+\b$#', $bin, $matches);
        if (empty($matches)) {
            throw new InvalidArgumentException('Not valid binary number!');
        }
        $number = bindec($bin);

        return [
            'decimal' => $number,
            'hexa' => dechex($number),
            'octal' => decoct($number),
            'binary' => $bin
        ];
    }
}
