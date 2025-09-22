<?php

// Environment configuration
$_ENV = array_merge($_ENV, $_SERVER);

// Application configuration
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('BASE_URL', '/');

// Session configuration
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400);
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload function for classes
spl_autoload_register(function ($className) {
    $directories = [
        BASE_PATH . '/src/controllers/',
        BASE_PATH . '/src/models/',
        BASE_PATH . '/src/services/',
        BASE_PATH . '/config/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database and CSRF configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/csrf.php';