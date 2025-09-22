<?php
require_once '../config/config.php';

// Simple router
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base URL from path
if (strpos($path, BASE_URL) === 0) {
    $path = substr($path, strlen(BASE_URL));
}

// Clean path
$path = trim($path, '/');
if (empty($path)) {
    $path = 'dashboard';
}

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);
$publicRoutes = ['login', 'assets'];

// Redirect to login if not authenticated and not accessing public routes
if (!$isAuthenticated && !in_array(explode('/', $path)[0], $publicRoutes)) {
    header('Location: /login');
    exit;
}

try {
    switch ($path) {
        case 'login':
            if ($isAuthenticated) {
                header('Location: /dashboard');
                exit;
            }
            require_once '../src/controllers/AuthController.php';
            $controller = new AuthController();
            $controller->login();
            break;
            
        case 'logout':
            require_once '../src/controllers/AuthController.php';
            $controller = new AuthController();
            $controller->logout();
            break;
            
        case 'dashboard':
            require_once '../src/controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->index();
            break;
            
        case 'visitors':
            require_once '../src/controllers/VisitorController.php';
            $controller = new VisitorController();
            $controller->index();
            break;
            
        case 'employees':
            require_once '../src/controllers/EmployeeController.php';
            $controller = new EmployeeController();
            $controller->index();
            break;
            
        case 'access':
            require_once '../src/controllers/AccessController.php';
            $controller = new AccessController();
            $controller->index();
            break;
            
        case 'reports':
            require_once '../src/controllers/ReportController.php';
            $controller = new ReportController();
            $controller->index();
            break;
            
        default:
            // Handle assets and uploads
            if (strpos($path, 'assets/') === 0 || strpos($path, 'uploads/') === 0) {
                return false; // Let the web server handle static files
            }
            
            http_response_code(404);
            echo "<h1>404 - Page Not Found</h1>";
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "<h1>Error</h1><p>An error occurred. Please try again.</p>";
}