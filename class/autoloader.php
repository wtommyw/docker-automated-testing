<?php

spl_autoload_register(function() {
	foreach	( scandir(__DIR__) as $file ) {
		if ( preg_match('/\.php$/', $file) && $file !== 'autoloader.php' ) {
			require_once $file;
		}
	}
});

