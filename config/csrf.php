<?php
// CSRF Protection
class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function getHiddenInput() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function verifyRequest() {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // Verificar CSRF em requisições que alteram dados
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            // Tentar pegar token do header primeiro (para APIs JSON)
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            // Se não há header, tentar form data (para formulários tradicionais)
            if (empty($token)) {
                $token = $_POST['csrf_token'] ?? '';
            }
            
            if (!self::validateToken($token)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
                die();
            }
        }
    }
}