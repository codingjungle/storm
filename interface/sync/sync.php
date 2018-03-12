<?php
require_once str_replace( 'applications/storm/interface/sync/sync.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
\IPS\Session\Front::i();
\IPS\storm\Sync::recieve();