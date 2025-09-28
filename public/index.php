<?php
// 🛡️ PROTEÇÃO CRÍTICA: Bloquear acesso direto a uploads independente do servidor
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/uploads/') !== false || strpos($requestUri, 'uploads/') !== false) {
    http_response_code(403);
    header('Content-Type: text/plain');
    die('🚫 ACESSO NEGADO: Dados biométricos protegidos pela LGPD');
}

require_once __DIR__ . '/../config/config.php';

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
$publicRoutes = ['login', 'assets', 'reset-password', 'forgot-password', 'api', 'privacy'];

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
    // Handle specific user routes like config/users/5 BEFORE the main switch
    if (preg_match('/^config\/users\/(\d+)$/', $path, $matches)) {
        require_once '../src/controllers/ConfigController.php';
        $controller = new ConfigController();
        $_GET['id'] = $matches[1];
        
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $controller->updateUser();
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
        exit; // Important: exit here so the switch doesn't run
    }
    
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
            
        case 'reset-password':
            // Página/API de redefinição de senha
            require_once '../src/controllers/PasswordResetController.php';
            $controller = new PasswordResetController();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->showResetForm();
            } else {
                $controller->executeReset();
            }
            break;
            
        case 'forgot-password':
            // Página/API para solicitar reset de senha
            require_once '../src/controllers/PasswordResetController.php';
            $controller = new PasswordResetController();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->showForgotForm();
            } else {
                $controller->requestReset();
            }
            break;
            
        case 'privacy':
            // Página principal de privacidade
            require_once '../src/controllers/PrivacyController.php';
            $controller = new PrivacyController();
            $controller->index();
            break;
            
        case 'privacy/portal':
            // Portal do titular de dados LGPD
            require_once '../src/controllers/PrivacyController.php';
            $controller = new PrivacyController();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->portal();
            } else {
                $controller->processRequest();
            }
            break;
            
        case 'privacy/cookies':
            // Gestão de cookies
            require_once '../src/controllers/PrivacyController.php';
            $controller = new PrivacyController();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->cookies();
            } else {
                $controller->updateCookiePreferences();
            }
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
                case 'save_ajax':
                    $controller->saveAjax();
                    break;
                case 'update_ajax':
                    $controller->updateAjax();
                    break;
                default:
                    // Outras ações redirecionam para relatório
                    header('Location: /reports/profissionais-renner');
                    exit;
            }
            break;
            
        case 'visitantes':
            require_once '../src/controllers/VisitantesNovoController.php';
            $controller = new VisitantesNovoController();
            $action = $_GET['action'] ?? 'index';
            switch ($action) {
                case 'save_ajax':
                    $controller->saveAjax();
                    break;
                case 'update_ajax':
                    $controller->updateAjax();
                    break;
                default:
                    // Outras ações redirecionam para relatório
                    header('Location: /reports/visitantes');
                    exit;
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
            
        case 'reports/profissionais-renner':
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
            
        case 'reports/visitantes':
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
            
        case 'reports/prestadores-servico':
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
            
        case 'config':
        case 'config/rbac-matrix':
        case 'config/rbac-users':
        case 'config/role-permissions':
        case 'config/auth-policies':
        case 'config/audit':
        case 'config/users':
            require_once '../src/controllers/ConfigController.php';
            $controller = new ConfigController();
            
            // Suporte tanto para rotas diretas (/config/rbac-matrix) quanto query string (?action=rbac-matrix)
            if (strpos($path, 'config/') === 0) {
                $action = substr($path, strlen('config/'));
            } else {
                $action = $_GET['action'] ?? 'index';
            }
            switch ($action) {
                case 'organization':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getOrganization();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                        $controller->updateOrganization();
                    }
                    break;
                case 'organization/logo':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->uploadLogo();
                    }
                    break;
                case 'sites':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getSites();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createSite();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                        $controller->updateSite();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                        $controller->deleteSite();
                    }
                    break;
                case 'sectors':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getSectorsBySite();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createSector();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                        $controller->updateSector();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                        $controller->deleteSector();
                    }
                    break;
                case 'rbac-matrix':
                    $controller->getRbacMatrix();
                    break;
                case 'role-permissions':
                    $controller->updateRolePermissions();
                    break;
                case 'rbac-users':
                    $controller->getRbacUsers();
                    break;
                case 'auth-policies':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getAuthPolicies();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                        $controller->updateAuthPolicies();
                    }
                    break;
                case 'audit':
                    $controller->getAuditLogs();
                    break;
                case 'audit/export':
                    $controller->exportAuditLogs();
                    break;
                case 'users':
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $controller->getUsers();
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->createUser();
                    }
                    break;
                case 'validate-cnpj':
                    $controller->validateCnpj();
                    break;
                default:
                    // Verificar se é uma rota específica de usuários
                    if (preg_match('/^users\/(\d+)\/toggle-status$/', $action, $matches)) {
                        $_GET['id'] = $matches[1];
                        $controller->toggleUserStatus();
                    } elseif (preg_match('/^users\/(\d+)\/reset-password$/', $action, $matches)) {
                        $_GET['id'] = $matches[1];
                        $controller->resetUserPassword();
                    } elseif (preg_match('/^users\/(\d+)$/', $action, $matches)) {
                        $_GET['id'] = $matches[1];
                        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                            $controller->updateUser();
                        } else {
                            http_response_code(405);
                            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                        }
                    } else {
                        $controller->index();
                    }
                    break;
            }
            break;
            
        default:
            // 🎨 Páginas de demonstração
            if ($path === 'demo/components') {
                require_once '../src/services/LayoutService.php';
                require_once '../src/services/ComponentService.php';
                require_once '../views/demo/components.php';
            }
            // Handle new API endpoints
            else if (preg_match('/^entradas$/', $path)) {
                require_once '../src/controllers/RegistroAcessoController.php';
                $controller = new RegistroAcessoController();
                $controller->checkIn();
            } else if (preg_match('/^saidas\/(\d+)$/', $path, $matches)) {
                require_once '../src/controllers/RegistroAcessoController.php';
                $controller = new RegistroAcessoController();
                $controller->checkOut($matches[1]);
            } else if (preg_match('/^registros\/(\d+)$/', $path, $matches)) {
                require_once '../src/controllers/RegistroAcessoController.php';
                $controller = new RegistroAcessoController();
                $controller->edit($matches[1]);
            } else if (preg_match('/^registros$/', $path)) {
                require_once '../src/controllers/RegistroAcessoController.php';
                $controller = new RegistroAcessoController();
                $controller->list();
            } else if (preg_match('/^registros\/alertas$/', $path)) {
                require_once '../src/controllers/RegistroAcessoController.php';
                $controller = new RegistroAcessoController();
                $controller->alertas();
            } else if (strpos($path, 'secure/biometric/') === 0) {
                // 🔒 Endpoints seguros para dados biométricos
                require_once '../src/controllers/SecureBiometricController.php';
                $controller = new SecureBiometricController();
                
                $subPath = substr($path, strlen('secure/biometric/'));
                switch ($subPath) {
                    case 'photo':
                        $controller->servePhoto();
                        break;
                    case 'migrate':
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $controller->migrateExisting();
                        } else {
                            http_response_code(405);
                            echo json_encode(['error' => 'Método não permitido']);
                        }
                        break;
                    case 'delete':
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $controller->deletePhoto();
                        } else {
                            http_response_code(405);
                            echo json_encode(['error' => 'Método não permitido']);
                        }
                        break;
                    case 'stats':
                        $controller->getUsageStats();
                        break;
                    default:
                        http_response_code(404);
                        echo json_encode(['error' => 'Endpoint biométrico não encontrado']);
                }
            } else if (strpos($path, 'assets/') === 0 || strpos($path, 'uploads/') === 0) {
                // 🚫 BLOQUEAR uploads - não servir mais arquivos estáticos de uploads
                if (strpos($path, 'uploads/') === 0) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Acesso negado: dados biométricos protegidos']);
                } else {
                    // Apenas assets são permitidos
                    return false; // Let the web server handle static assets
                }
            } else {
                http_response_code(404);
                echo "<h1>404 - Page Not Found</h1>";
            }
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "<h1>Error</h1><p>An error occurred. Please try again.</p>";
}