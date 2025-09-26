<?php

require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../services/LoginThrottleService.php';

class AuthController {
    private $db;
    private $throttleService;
    
    public function __construct() {
        $this->db = new Database();
        $this->throttleService = new LoginThrottleService();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar CSRF token primeiro
            CSRFProtection::verifyRequest();
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // Verificar rate limiting
            $blockStatus = $this->throttleService->isBlocked($ipAddress, $email);
            if ($blockStatus['blocked']) {
                $minutes = ceil($blockStatus['remaining_seconds'] / 60);
                $error = "Muitas tentativas de login. Tente novamente em $minutes minutos.";
                include '../views/auth/login.php';
                return;
            }
            
            if (empty($email) || empty($password)) {
                $error = "Por favor, preencha todos os campos.";
            } else {
                try {
                    $user = $this->db->fetch(
                        "SELECT u.*, r.name as role_name FROM usuarios u 
                         LEFT JOIN roles r ON u.role_id = r.id 
                         WHERE u.email = ? AND u.ativo = TRUE",
                        [$email]
                    );
                    
                    if ($user && password_verify($password, $user['senha_hash'])) {
                        // Login bem-sucedido - limpar tentativas
                        $this->throttleService->clearAttempts($ipAddress, $email);
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nome'];
                        // Usar role RBAC se disponível, senão fallback para perfil legacy
                        $_SESSION['user_profile'] = $user['role_name'] ?? $user['perfil'];
                        
                        // Update last login
                        $this->db->query(
                            "UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id = ?",
                            [$user['id']]
                        );
                        
                        header('Location: /dashboard');
                        exit;
                    } else {
                        // Login falhado - registrar tentativa
                        $attemptInfo = $this->throttleService->recordFailedAttempt($ipAddress, $email);
                        
                        if ($attemptInfo['blocked']) {
                            $error = "Muitas tentativas incorretas. Acesso bloqueado por 15 minutos.";
                        } else {
                            $remaining = 5 - $attemptInfo['attempts'];
                            $error = "Email ou senha incorretos. ($remaining tentativas restantes)";
                        }
                    }
                } catch (Exception $e) {
                    error_log("Database error during login: " . $e->getMessage());
                    $error = "Erro de conexão com o banco de dados. Tente novamente.";
                }
            }
        }
        
        include '../views/auth/login.php';
    }
    
    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}