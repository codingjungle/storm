<?php
/**
 * @brief      HelperTemplate Class
 * @copyright  -storm_copyright-
 * @package    IPS Social Suite
 * @subpackage dtdevplus
 * @since      -storm_since_version-
 * @version    -storm_version-
 */


namespace IPS\storm\Center\Helpers;

use IPS\storm\Proxy\Helpers\HelpersAbstract;

use function defined;
use function header;

if (!defined('\IPS\SUITE_UNIQUE_KEY')) {
    header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden');
    exit;
}

/**
 * HelperTemplate Class
 *
 * @mixin  IPS\storm\Proxy\Helpers\HelpersAbstract;
 */
class HelperCompilerAbstract implements HelpersAbstract
{
    public function process($class, &$classDoc, &$classExtends, &$body)
    {
//        $el = Dev::i()->elements();
//        foreach ( $el as $val ) {
//            if ( isset( $val[ 'name' ] ) ) {
//                $type = 'string';
//                if ( isset( $val[ 'class' ] ) && 'stack' === mb_strtolower( $val[ 'class' ] ) ) {
//                    $type = 'array';
//                }
//
//                $classDoc[] = [ 'pt' => 'p', 'prop' => $val[ 'name' ], 'type' => $type ];
//            }
//        }

        $classDoc[] = ['pt' => 'p', 'prop' => 'location', 'type' => 'string'];
        $classDoc[] = ['pt' => 'p', 'prop' => 'group', 'type' => 'string'];
    }
}

