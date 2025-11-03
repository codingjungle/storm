<?php
/**
 * @brief		Dev Storm Application Class
 * @author		<a href='https://codingjungle.com'>Michael S. Edwards</a>
 * @copyright	(c) 2025 Michael S. Edwards
 * @package		Invision Community
 * @subpackage	Dev Storm
 * @since		07 Oct 2025
 * @version		
 */
 
namespace IPS\storm;

use IPS\Application as SystemApplication;
use IPS\IPS;

require \IPS\ROOT_PATH . '/applications/storm/sources/Bootstrap/Bootstrap.php';
/**
 * Dev Storm Application Class
 */
class Application extends SystemApplication
{

    public function __construct()
    {
        parent::__construct();
    }

    protected static $loaded = false;
    
    public static function initAutoloader(): void
    {
        if (static::$loaded === false) {
            static::$loaded = true;
            require \IPS\Application::getRootPath('storm') . '/applications/storm/sources/vendor/autoload.php';
            IPS::$PSR0Namespaces['Generator'] = \IPS\Application::getRootPath() . '/applications/storm/sources/Generator/';
        }
    }

    public function get__icon(): string
    {
        return 'wrench';
    }
}