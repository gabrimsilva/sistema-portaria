<?php

require_once __DIR__ . '/../services/PasswordResetService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../../config/database.php';

class PasswordResetController {
    private $resetService;
    private $auditService;
    
    public function __construct() {
        $this->resetService = new PasswordResetService();
        $this->auditService = new AuditService();
    }
    
    /**
     * Exibe formulário de solicitação de reset
     */
    public function showForgotForm() {
        require_once '../views/auth/forgot-password.php';
    }
    
    /**
     * Processa solicitação de reset de senha
     */
    public function requestReset() {
        try {
            // Definir cabeçalho JSON primeiro
            header('Content-Type: application/json; charset=utf-8');
            
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                return;
            }
            
            // Obter dados
            $input = json_decode(file_get_contents('php://input'), true);
            $email = trim($input['email'] ?? '');
            
            // Validações
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email é obrigatório']);
                return;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email inválido']);
                return;
            }
            
            // Obter informações de segurança
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Rate limiting por IP (máximo 10 tentativas por hora)
            if (!$this->checkIpRateLimit($ipAddress)) {
                echo json_encode(['success' => false, 'message' => 'Muitas tentativas. Tente novamente em 1 hora.']);
                return;
            }
            
            // Processar reset
            $success = $this->resetService->initiateReset($email, $ipAddress, $userAgent);
            
            // Log da tentativa
            $this->auditService->log(
                null, // usuário anônimo
                'password_reset_request',
                'usuarios',
                null,
                ['email' => $email, 'ip' => $ipAddress],
                $ipAddress,
                $userAgent
            );
            
            // Sempre retornar sucesso por segurança
            echo json_encode([
                'success' => true, 
                'message' => 'Se uma conta com este email existir, você receberá as instruções de recuperação.'
            ]);
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }
    
    /**
     * Exibe página de redefinição de senha
     */
    public function showResetForm() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            header('Location: /login?error=token_missing');
            exit;
        }
        
        // Validar token
        $tokenData = $this->resetService->validateToken($token);
        if (!$tokenData) {
            header('Location: /login?error=token_invalid');
            exit;
        }
        
        // Exibir formulário
        include __DIR__ . '/../../views/auth/reset-password.php';
    }
    
    /**
     * Processa redefinição de senha
     */
    public function executeReset() {
        try {
            // Definir cabeçalho JSON primeiro
            header('Content-Type: application/json; charset=utf-8');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['token'] ?? '';
            $password = $input['password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            // Validações
            if (empty($token) || empty($password) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
                return;
            }
            
            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
                return;
            }
            
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
                return;
            }
            
            // Validar token antes do reset
            $tokenData = $this->resetService->validateToken($token);
            if (!$tokenData) {
                echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
                return;
            }
            
            // Executar reset
            $success = $this->resetService->resetPassword($token, $password);
            
            if ($success) {
                // Log da ação
                $this->auditService->log(
                    $tokenData['usuario_id'],
                    'password_reset_completed',
                    'usuarios',
                    $tokenData['usuario_id'],
                    ['email' => $tokenData['email']],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                );
                
                echo json_encode(['success' => true, 'message' => 'Senha redefinida com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao redefinir senha']);
            }
            
        } catch (Exception $e) {
            error_log("Password reset execution error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }
    
    /**
     * Verifica rate limiting por IP
     */
    private function checkIpRateLimit(string $ipAddress, int $maxAttempts = 10): bool {
        if (empty($ipAddress)) {
            return true;
        }
        
        try {
            $database = new Database();
            $pdo = $database->connect();
            
            // Criar tabela de tentativas se não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS reset_attempts (
                    id SERIAL PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT NOW()
                )
            ");
            
            // Registrar tentativa atual
            $stmt = $pdo->prepare("INSERT INTO reset_attempts (ip_address) VALUES (?)");
            $stmt->execute([$ipAddress]);
            
            // Verificar tentativas na última hora
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reset_attempts 
                WHERE ip_address = ? 
                AND attempted_at > NOW() - INTERVAL '1 hour'
            ");
            $stmt->execute([$ipAddress]);
            $attempts = $stmt->fetchColumn();
            
            return $attempts <= $maxAttempts;
            
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Em caso de erro, permitir tentativa
        }
    }
}