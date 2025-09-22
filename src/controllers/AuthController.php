<?php

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = "Por favor, preencha todos os campos.";
            } else {
                try {
                    $user = $this->db->fetch(
                        "SELECT * FROM usuarios WHERE email = ? AND ativo = TRUE",
                        [$email]
                    );
                    
                    if ($user && password_verify($password, $user['senha_hash'])) {
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['nome'];
                        $_SESSION['user_profile'] = $user['perfil'];
                        
                        // Update last login
                        $this->db->query(
                            "UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id = ?",
                            [$user['id']]
                        );
                        
                        header('Location: /dashboard');
                        exit;
                    } else {
                        $error = "Email ou senha incorretos.";
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