<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/replitmail.php';

class PasswordResetService {
    private $pdo;
    private $tokenExpiry = 3600; // 1 hora
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
    }
    
    /**
     * Inicia processo de recuperação de senha
     * Sempre retorna sucesso para prevenir enumeração de contas
     */
    public function initiateReset(string $email, string $ipAddress = null, string $userAgent = null): bool {
        try {
            // Verificar se usuário existe
            $stmt = $this->pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = true");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Sempre retornar sucesso para segurança (não revelar se email existe)
            if (!$user) {
                // Simular delay para não revelar que usuário não existe
                usleep(rand(100000, 300000)); // 0.1-0.3 segundos
                return true;
            }
            
            // Verificar rate limiting (máximo 3 tentativas por hora por usuário)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM password_resets 
                WHERE usuario_id = ? 
                AND created_at > NOW() - INTERVAL '1 hour'
            ");
            $stmt->execute([$user['id']]);
            $recentAttempts = $stmt->fetchColumn();
            
            if ($recentAttempts >= 3) {
                // Simular delay mas não enviar email
                usleep(rand(100000, 300000));
                return true;
            }
            
            // Gerar token seguro
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenExpiry);
            
            // Remover tokens antigos do usuário
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE usuario_id = ?");
            $stmt->execute([$user['id']]);
            
            // Inserir novo token
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (usuario_id, token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $hashedToken, $expiresAt, $ipAddress, $userAgent]);
            
            // Enviar email
            $this->sendResetEmail($user['email'], $user['nome'], $token, $ipAddress);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            // Sempre retornar sucesso mesmo com erro interno
            return true;
        }
    }
    
    /**
     * Valida token de reset
     */
    public function validateToken(string $token): ?array {
        try {
            $hashedToken = hash('sha256', $token);
            
            $stmt = $this->pdo->prepare("
                SELECT pr.usuario_id, u.email, u.nome 
                FROM password_resets pr
                JOIN usuarios u ON pr.usuario_id = u.id
                WHERE pr.token = ? 
                AND pr.expires_at > NOW() 
                AND pr.used_at IS NULL
                AND u.ativo = true
            ");
            $stmt->execute([$hashedToken]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Redefine a senha do usuário
     */
    public function resetPassword(string $token, string $newPassword): bool {
        try {
            $tokenData = $this->validateToken($token);
            if (!$tokenData) {
                return false;
            }
            
            $this->pdo->beginTransaction();
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            $stmt = $this->pdo->prepare("UPDATE usuarios SET senha = ?, data_atualizacao = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $tokenData['usuario_id']]);
            
            // Marcar token como usado
            $hashedToken = hash('sha256', $token);
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
            $stmt->execute([$hashedToken]);
            
            $this->pdo->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Password reset execution error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de recuperação de senha
     */
    private function sendResetEmail(string $email, string $nome, string $token, string $ipAddress = null): void {
        try {
            $resetLink = $this->getBaseUrl() . "/reset-password?token=" . urlencode($token);
            
            $htmlContent = $this->generateEmailTemplate($nome, $resetLink, $ipAddress);
            $textContent = $this->generateTextEmail($nome, $resetLink, $ipAddress);
            
            sendEmail([
                'to' => $email,
                'subject' => 'Recuperação de Senha - Sistema de Controle de Acesso',
                'html' => $htmlContent,
                'text' => $textContent
            ]);
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Gera template HTML do email
     */
    private function generateEmailTemplate(string $nome, string $resetLink, string $ipAddress = null): string {
        $currentTime = date('d/m/Y H:i:s');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .security-info { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Recuperação de Senha</h1>
                    <p>Sistema de Controle de Acesso</p>
                </div>
                
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' class='button'>Redefinir Minha Senha</a>
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <ul>
                            <li>Este link expira em <strong>1 hora</strong></li>
                            <li>Só pode ser usado <strong>uma vez</strong></li>
                            <li>Se você não solicitou, ignore este email</li>
                        </ul>
                    </div>
                    
                    <div class='security-info'>
                        <strong>Informações de Segurança:</strong><br>
                        📅 Data/Hora: {$currentTime}<br>
                        🌐 IP: " . ($ipAddress ?: 'Não disponível') . "<br>
                        🔗 Link alternativo: {$resetLink}
                    </div>
                    
                    <p style='margin-top: 30px; font-size: 12px; color: #666;'>
                        Se você tem problemas para clicar no botão, copie e cole o link acima no seu navegador.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Gera versão texto do email
     */
    private function generateTextEmail(string $nome, string $resetLink, string $ipAddress = null): string {
        $currentTime = date('d/m/Y H:i:s');
        
        return "
        RECUPERAÇÃO DE SENHA
        Sistema de Controle de Acesso
        
        Olá, {$nome}!
        
        Recebemos uma solicitação para redefinir a senha da sua conta.
        
        Para redefinir sua senha, acesse o link abaixo:
        {$resetLink}
        
        IMPORTANTE:
        - Este link expira em 1 hora
        - Só pode ser usado uma vez
        - Se você não solicitou, ignore este email
        
        Informações de Segurança:
        Data/Hora: {$currentTime}
        IP: " . ($ipAddress ?: 'Não disponível') . "
        
        Se você tem problemas, entre em contato com o suporte.
        ";
    }
    
    /**
     * Obtém URL base do sistema
     */
    private function getBaseUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:5000';
        return $protocol . '://' . $host;
    }
    
    /**
     * Limpa tokens expirados (para ser executado periodicamente)
     */
    public function cleanExpiredTokens(): int {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Token cleanup error: " . $e->getMessage());
            return 0;
        }
    }
}