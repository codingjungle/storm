<?php


use const IPS\ROOT_PATH;

//require_once ROOT_PATH . '/init.php';
require_once ROOT_PATH . '/applications/storm/sources/Helpers/Helpers.php';
class dinit extends \IPS\IPS {
    public static bool $override = false;

    protected static array $hf = [
        'IPS\\Db' => ['ips' => 'system/Db/Db.php', 'hook' => 'Db.php'],
        'IPS\\Theme' => ['ips' => 'system/Theme/Theme.php', 'hook' => 'Theme.php'],
        'IPS\\Theme\\Dev\\Template' => ['ips' => 'system/Theme/Dev/Template.php', 'hook' => 'Template.php']
    ];

	public static function dinit()
	{ 
        $vendor = ROOT_PATH.'/applications/storm/sources/vendor/autoload.php';
        require $vendor;
		spl_autoload_register( array('\dinit', 'autoloader' ), true, true );
		set_exception_handler( array('\dinit', 'exceptionHandler' ) );
	}

	/**
	 * Autoloader
	 *
	 * @param	string	$classname	Class to load
	 * @return	void
	 */
	public static function autoloader( $classname )
	{

		/* Separate by namespace */
		$bits = explode( '\\', ltrim( $classname, '\\' ) );
								
		/* If this doesn't belong to us, try a PSR-0 loader or ignore it */
		$vendorName = array_shift( $bits );
		if( $vendorName !== 'IPS' )
		{			
			return;
		}
		
		/* Work out what namespace we're in */
		$class = array_pop( $bits );
		$namespace = empty( $bits ) ? 'IPS' : ( 'IPS\\' . implode( '\\', $bits ) );
		
			
        $lookUp = "{$namespace}\\{$class}";
        if(isset(static::$hf[$lookUp])){ 
            if( !class_exists( "{$namespace}\\{$class}", FALSE ) )
            {
                $path = ROOT_PATH . '/hooks/';
                $hookInfo = static::$hf[$lookUp];
                $ipsFile = ROOT_PATH.'/'.$hookInfo['ips'];
                $hookFile = ROOT_PATH.'/applications/storm/sources/Hooks/'.$hookInfo['hook'];

                $mtime = filemtime( $ipsFile );
                $name = \str_replace(["\\", '/'], '_', $namespace . $class . $ipsFile);
                $filename = $name.'_' . $mtime . '.php';

                if (!file_exists( $path.$filename ) && \file_exists($ipsFile))
                {
                    if (!\is_dir($path)) {
                        \mkdir($path, 0777, true);
                    } 
                    $fs = new \Symfony\Component\Filesystem\Filesystem();
                    $finder = new \Symfony\Component\Finder\Finder();
                    $finder->in( $path )->files()->name($name.'*.php');

                    foreach( $finder as $f ){
                        $fs->remove($f->getRealPath());
                    }

                    $content = file_get_contents($ipsFile);
                    $content = preg_replace('#\b(?<![\'|"])class '.$class.'\b#', 'class _'. $class, $content);
                    if (!\file_exists($path . $filename)) {
                        \file_put_contents($path . $filename,  $content);
                    }
                }

                require_once $path . $filename;        
                require_once $hookFile;
                
               	if ( interface_exists( $lookUp, FALSE ) )
                {
                    return;
                }
                
                /* Is it a trait? */
                if ( trait_exists( $lookUp, FALSE ) )
                {
                    return;
                }
                
                /* Is it an enumeration? */
                if ( function_exists( 'enum_exists' ) && enum_exists( $lookUp, FALSE ) )
                {
                    return;
                }

                /* Does it exist? */
                if ( class_exists( $lookUp, FALSE ) )
                {
                    return;
                }
                                
                /* Doesn't exist? */
                if( !class_exists( "{$namespace}\\{$class}", FALSE ) )
                {
                    trigger_error( "Class {$classname} could not be loaded. Ensure it is in the correct namespace. storm", E_USER_ERROR );
                } 
            }
        
        } 
	}

   public static function exceptionHandler( $exception )
	{
	    if(\IPS\IN_DEV === true){
	        throw $exception;
	    }
	    else{
	        parent::exceptionHandler($exception);
	    }
	}
}

dinit::dinit();