<?php

/**
 * Controller para acesso seguro a dados biométricos
 * 
 * Este controller garante que apenas usuários autorizados possam
 * acessar dados biométricos, implementando controles de segurança
 * adequados conforme LGPD.
 */

require_once __DIR__ . '/../services/SecureBiometricStorageService.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../utils/CSRFProtection.php';

class SecureBiometricController {
    private $biometricService;
    private $authService;
    
    public function __construct() {
        $this->biometricService = new SecureBiometricStorageService();
        $this->authService = new AuthorizationService();
    }
    
    /**
     * Servir foto biométrica de forma segura
     * 
     * URL: /secure/biometric/{biometric_id}
     * Método: GET
     * Requer: Autenticação + autorização específica
     */
    public function servePhoto() {
        try {
            // Verificar autenticação
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Não autenticado']);
                return;
            }
            
            // Verificar permissão geral para biométricos
            if (!$this->authService->hasPermission('biometric.read')) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                return;
            }
            
            // Obter ID da foto do parâmetro
            $biometricId = (int)($_GET['id'] ?? 0);
            
            if ($biometricId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID biométrico inválido']);
                return;
            }
            
            // Parâmetros opcionais para validação adicional
            $entityType = $_GET['entity_type'] ?? null;
            $entityId = $_GET['entity_id'] ?? null;
            
            // Recuperar foto de forma segura
            $photoData = $this->biometricService->retrieveBiometricPhoto($biometricId, $entityType, $entityId);
            
            // Verificar se token de acesso é válido (proteção adicional)
            $expectedToken = $this->generateAccessToken($biometricId, $_SESSION['user_id']);
            $providedToken = $_GET['token'] ?? '';
            
            if (!hash_equals($expectedToken, $providedToken)) {
                http_response_code(403);
                echo json_encode(['error' => 'Token de acesso inválido']);
                return;
            }
            
            // Definir headers de segurança
            header('Content-Type: ' . $photoData['mime_type']);
            header('Content-Disposition: inline; filename="' . ($photoData['original_filename'] ?? 'photo.jpg') . '"');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Cache-Control: private, no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Servir dados da imagem
            echo $photoData['data'];
            
        } catch (Exception $e) {
            error_log("Erro ao servir foto biométrica: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }
    
    /**
     * Migrar fotos existentes para armazenamento seguro
     * 
     * URL: /secure/biometric/migrate
     * Método: POST
     * Requer: Permissão de administrador
     */
    public function migrateExisting() {
        try {
            // Verificar permissão de administrador
            if (!$this->authService->hasPermission('admin.system.manage')) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                return;
            }
            
            // Verificar CSRF
            CSRFProtection::verifyRequest();
            
            // Executar migração
            $result = $this->biometricService->migrateExistingPhotos();
            
            echo json_encode([
                'success' => true,
                'migrated' => $result['migrated'],
                'errors' => $result['errors']
            ]);
            
        } catch (Exception $e) {
            error_log("Erro na migração de fotos: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro na migração']);
        }
    }
    
    /**
     * Gerar URL segura para acesso à foto
     * 
     * Esta função deve ser usada pelos outros controllers para gerar URLs
     * que incluem token de acesso válido e limitação de tempo
     */
    public function generateSecurePhotoUrl($biometricId, $entityType = null, $entityId = null) {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $token = $this->generateAccessToken($biometricId, $_SESSION['user_id']);
        $expires = time() + (15 * 60); // Token válido por 15 minutos
        
        $params = [
            'id' => $biometricId,
            'token' => $token,
            'expires' => $expires
        ];
        
        if ($entityType) {
            $params['entity_type'] = $entityType;
        }
        
        if ($entityId) {
            $params['entity_id'] = $entityId;
        }
        
        return '/secure/biometric/photo?' . http_build_query($params);
    }
    
    /**
     * Deletar foto biométrica
     * 
     * URL: /secure/biometric/delete
     * Método: POST
     * Requer: Permissão específica de exclusão
     */
    public function deletePhoto() {
        try {
            // Verificar permissão
            if (!$this->authService->hasPermission('biometric.delete')) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                return;
            }
            
            // Verificar CSRF
            CSRFProtection::verifyRequest();
            
            $biometricId = (int)($_POST['biometric_id'] ?? 0);
            $reason = $_POST['reason'] ?? 'Solicitação de exclusão';
            
            if ($biometricId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID biométrico inválido']);
                return;
            }
            
            // Executar exclusão segura
            $this->biometricService->deleteBiometricPhoto($biometricId, $reason);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log("Erro ao deletar foto biométrica: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Gerar token de acesso temporário
     */
    private function generateAccessToken($biometricId, $userId) {
        $secretKey = $_ENV['BIOMETRIC_ACCESS_SECRET'] ?? 'default-secret-change-in-production';
        $data = $biometricId . '|' . $userId . '|' . date('Y-m-d-H');
        
        return hash_hmac('sha256', $data, $secretKey);
    }
    
    /**
     * Validar token de acesso
     */
    private function validateAccessToken($token, $biometricId, $userId) {
        $expectedToken = $this->generateAccessToken($biometricId, $userId);
        return hash_equals($expectedToken, $token);
    }
    
    /**
     * Obter estatísticas de uso (para auditoria)
     */
    public function getUsageStats() {
        if (!$this->authService->hasPermission('audit_log.read')) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            return;
        }
        
        try {
            $db = new Database();
            
            // Estatísticas básicas
            $totalFiles = $db->fetch("SELECT COUNT(*) as count FROM biometric_files WHERE deleted_at IS NULL")['count'];
            $deletedFiles = $db->fetch("SELECT COUNT(*) as count FROM biometric_files WHERE deleted_at IS NOT NULL")['count'];
            
            // Por tipo de entidade
            $byEntityType = $db->fetchAll("
                SELECT entity_type, COUNT(*) as count 
                FROM biometric_files 
                WHERE deleted_at IS NULL 
                GROUP BY entity_type
            ");
            
            // Acessos recentes (últimos 30 dias)
            $recentAccess = $db->fetch("
                SELECT COUNT(*) as count 
                FROM audit_logs 
                WHERE action = 'biometric_access' 
                AND created_at >= CURRENT_DATE - INTERVAL '30 days'
            ")['count'] ?? 0;
            
            echo json_encode([
                'total_active_files' => $totalFiles,
                'total_deleted_files' => $deletedFiles,
                'by_entity_type' => $byEntityType,
                'recent_access_count' => $recentAccess
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno']);
        }
    }
}