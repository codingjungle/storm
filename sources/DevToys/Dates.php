<?php

/**
 * @brief      Dates Singleton
 * @author     -storm_author-
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage toolbox
 * @since      5.0.1
 * @version    -storm_version-
 */

namespace IPS\storm\DevToys;

use IPS\DateTime;
use IPS\Patterns\Singleton;

use function defined;
use function header;
use function strtotime;

use const null;
use const true;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * Dates Class
 * @mixin Dates
 */
class Dates extends Singleton
{
    /**
     * @brief Singleton Instance
     * @note This needs to be declared in any child class
     * @var static
     */
    public static ?Singleton $instance = null;

    public function __call(string $name, array $arguments): array
    {
        return $this->dates(...$arguments);
    }

    public function dates(mixed $date = null): array
    {
        $time = strtotime($date);
        $dateTime = DateTime::ts($time, true);
        return $this->calculate($dateTime);
    }

    protected function calculate(DateTime $date): array
    {
        return [
            'dates' => $date->format('Y-m-d\TH:i'),
            'atom' => $date->format('Y-d-m\TH:i:sP'),
            'cookie' => $date->format('l, d-M-Y H:i:s e'),
            'iso' => $date->format('c'),
            'rfc' => $date->format('r'),
            'rfc3339' => $date->format('Y-m-d\TH:i:sP'),
            'rfc3339e' => $date->format('Y-m-d\TH:i:s.vP'),
            'rfc7231' => $date->format('D, d M Y H:i:s e'),
            'rss' => $date->format('D, d M Y H:i:s O'),
            'sql' => $date->format('Y-m-d H:i:s'),
            'unix' => $date->getTimestamp(),
            'w3c' => $date->format('Y-m-d\TH:i:sP')
        ];
    }

    public function unix(int $int): array
    {
        $dateTime = DateTime::ts($int, true);

        return $this->calculate($dateTime);
    }
}
