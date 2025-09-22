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

// Handle authentication for non-public routes
if (!$isAuthenticated && !in_array(explode('/', $path)[0], $publicRoutes)) {
    // Check if this is an AJAX request
    $isAjaxRequest = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        !empty($_SERVER['HTTP_ACCEPT']) && 
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    ) || (
        isset($_GET['action']) && strpos($_GET['action'], 'ajax') !== false
    );
    
    if ($isAjaxRequest) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Sessão expirada. Faça login novamente.',
            'redirect' => '/login'
        ]);
        exit;
    }
    
    // Regular redirect for non-AJAX requests
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
            
        case 'profissionais-renner':
            require_once '../src/controllers/ProfissionaisRennerController.php';
            $controller = new ProfissionaisRennerController();
            $action = $_GET['action'] ?? 'index';
            switch ($action) {
                case 'new':
                    $controller->create();
                    break;
                case 'save':
                    $controller->save();
                    break;
                case 'save_ajax':
                    $controller->saveAjax();
                    break;
                case 'update_ajax':
                    $controller->updateAjax();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;
            
        case 'visitantes':
            require_once '../src/controllers/VisitantesNovoController.php';
            $controller = new VisitantesNovoController();
            $action = $_GET['action'] ?? 'index';
            switch ($action) {
                case 'new':
                    $controller->create();
                    break;
                case 'save':
                    $controller->save();
                    break;
                case 'save_ajax':
                    $controller->saveAjax();
                    break;
                case 'update_ajax':
                    $controller->updateAjax();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'entrada':
                    $controller->registrarEntrada();
                    break;
                case 'saida':
                    $controller->registrarSaida();
                    break;
                case 'registrar_saida':
                    $controller->registrarSaida();
                    break;
                case 'get_data':
                    $controller->getData();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                default:
                    $controller->index();
                    break;
            }
            break;
            
        case 'prestadores-servico':
            require_once '../src/controllers/PrestadoresServicoController.php';
            $controller = new PrestadoresServicoController();
            $action = $_GET['action'] ?? 'index';
            switch ($action) {
                case 'new':
                    $controller->create();
                    break;
                case 'save':
                    $controller->save();
                    break;
                case 'save_ajax':
                    $controller->saveAjax();
                    break;
                case 'update_ajax':
                    $controller->updateAjax();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'entrada':
                    $controller->registrarEntrada();
                    break;
                case 'saida':
                    $controller->registrarSaida();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                default:
                    $controller->index();
                    break;
            }
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