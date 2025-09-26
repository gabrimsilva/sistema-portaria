<?php

// Environment configuration
$_ENV = array_merge($_ENV, $_SERVER);

// Application configuration - evitar redefinições
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// Session configuration with security
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax'); // Permite AJAX requests no mesmo domínio
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();

// Error reporting - secure for production
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// Only display errors in development
if (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'development') {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

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

// 🔐 Security configuration for biometric data
$encryptionKey = getenv('BIOMETRIC_ENCRYPTION_KEY');
if (!$encryptionKey) {
    // 🚨 PRODUÇÃO: Apenas em ambiente verdadeiramente de produção
    $isProduction = (getenv('ENVIRONMENT') === 'production') && 
                   (!isset($_SERVER['REPLIT_DB_URL'])); // Replit nunca é produção
    
    if ($isProduction) {
        throw new Exception('BIOMETRIC_ENCRYPTION_KEY obrigatória em produção');
    }
    // 🧪 Ambiente de desenvolvimento/teste/Replit: Chave segura temporária
    $encryptionKey = 'dev-test-key-' . hash('sha256', 'biometric-development-' . (__DIR__));
}
define('BIOMETRIC_ENCRYPTION_KEY', $encryptionKey);

// Include database and CSRF configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/csrf.php';