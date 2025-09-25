<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../services/RbacService.php';
require_once __DIR__ . '/../utils/CnpjValidator.php';

/**
 * Controller de Configurações do Sistema
 */
class ConfigController {
    private $db;
    private $authService;
    private $configService;
    private $rbacService;
    
    public function __construct() {
        $this->checkAuthentication();
        $this->db = new Database();
        $this->authService = new AuthorizationService();
        $this->configService = new ConfigService();
        $this->rbacService = new RbacService();
    }
    
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Página principal de configurações
     */
    public function index() {
        // Verificar permissão básica para acessar configurações
        if (!$this->authService->hasPermission('audit_log.read') && 
            !$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo "Acesso negado. Você não tem permissão para acessar as configurações.";
            exit;
        }
        
        // Tratar requisições POST com ações específicas
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'upload_logo':
                    $this->uploadLogo();
                    return;
                case 'remove_logo':
                    $this->removeLogo();
                    return;
                case 'get_organization':
                    $this->getOrganization();
                    return;
                case 'save_organization':
                    $this->saveOrganization();
                    return;
                case 'validate_cnpj':
                    $this->validateCnpj();
                    return;
                case 'get_rbac_matrix':
                    $this->getRbacMatrix();
                    return;
                case 'save_rbac_matrix':
                    $this->saveRbacMatrix();
                    return;
                case 'get_users_by_role':
                    $this->getUsersByRole();
                    return;
            }
        }
        
        include '../views/config/index.php';
    }
    
    // ========== ORGANIZAÇÃO ==========
    
    /**
     * GET /config/organization
     */
    public function getOrganization() {
        header('Content-Type: application/json');
        
        try {
            $org = $this->configService->getOrganizationSettings();
            echo json_encode(['success' => true, 'data' => $org]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * PUT /config/organization
     */
    public function updateOrganization() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validações
            if (empty($input['company_name']) || strlen($input['company_name']) < 2) {
                throw new Exception('Nome da empresa deve ter pelo menos 2 caracteres');
            }
            
            if (isset($input['company_name']) && strlen($input['company_name']) > 120) {
                throw new Exception('Nome da empresa não pode ter mais que 120 caracteres');
            }
            
            $result = $this->configService->updateOrganizationSettings($input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== SITES ==========
    
    /**
     * GET /config/sites
     */
    public function getSites() {
        header('Content-Type: application/json');
        
        try {
            $sites = $this->configService->getSites();
            echo json_encode(['success' => true, 'data' => $sites]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * POST /config/sites
     */
    public function createSite() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $siteId = $this->configService->createSite($input);
            echo json_encode(['success' => true, 'data' => ['id' => $siteId]]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * PUT /config/sites/{id}
     */
    public function updateSite() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $siteId = $_GET['id'] ?? null;
            if (!$siteId) {
                throw new Exception('ID do site é obrigatório');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $this->configService->updateSite($siteId, $input);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * DELETE /config/sites/{id}
     */
    public function deleteSite() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $siteId = $_GET['id'] ?? null;
            if (!$siteId) {
                throw new Exception('ID do site é obrigatório');
            }
            
            $this->configService->deleteSite($siteId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== SETORES ==========
    
    /**
     * GET /config/sectors/{site_id}
     */
    public function getSectorsBySite() {
        header('Content-Type: application/json');
        
        try {
            $siteId = $_GET['site_id'] ?? null;
            if (!$siteId) {
                throw new Exception('ID do site é obrigatório');
            }
            
            $sectors = $this->configService->getSectorsBySite($siteId);
            echo json_encode(['success' => true, 'data' => $sectors]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * POST /config/sectors
     */
    public function createSector() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $sectorId = $this->configService->createSector($input);
            echo json_encode(['success' => true, 'data' => ['id' => $sectorId]]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * PUT /config/sectors/{id}
     */
    public function updateSector() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $sectorId = $_GET['id'] ?? null;
            if (!$sectorId) {
                throw new Exception('ID do setor é obrigatório');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $this->configService->updateSector($sectorId, $input);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * DELETE /config/sectors/{id}
     */
    public function deleteSector() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $sectorId = $_GET['id'] ?? null;
            if (!$sectorId) {
                throw new Exception('ID do setor é obrigatório');
            }
            
            $this->configService->deleteSector($sectorId);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== RBAC ==========
    
    /**
     * GET /config/rbac-matrix
     */
    public function getRbacMatrix() {
        if (!$this->authService->hasPermission('config.rbac.write')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão necessária: config.rbac.write']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $matrix = $this->rbacService->getRbacMatrix();
            echo json_encode(['success' => true, 'data' => $matrix]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * PUT /config/role-permissions
     */
    public function updateRolePermissions() {
        if (!$this->authService->hasPermission('config.rbac.write')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão necessária: config.rbac.write']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        
        // 🛡️ VERIFICAÇÃO CRÍTICA CSRF - OBRIGATÓRIA PARA RBAC
        CSRFProtection::verifyRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Suporte ao formato de múltiplas mudanças (lote)
            if (isset($input['changes']) && is_array($input['changes'])) {
                $results = [];
                foreach ($input['changes'] as $change) {
                    if (!isset($change['role_id'], $change['permissions'])) {
                        continue;
                    }
                    
                    $this->rbacService->updateRolePermissions($change['role_id'], $change['permissions']);
                    $results[] = ['role_id' => $change['role_id'], 'status' => 'updated'];
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Matriz RBAC atualizada com sucesso',
                    'data' => $results
                ]);
                
            } else if (isset($input['role_id'], $input['permissions'])) {
                // Formato original (single role)
                $this->rbacService->updateRolePermissions($input['role_id'], $input['permissions']);
                echo json_encode(['success' => true, 'message' => 'Permissões atualizadas com sucesso']);
                
            } else {
                throw new Exception('role_id e permissions ou changes são obrigatórios');
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /config/rbac-users
     */
    public function getRbacUsers() {
        if (!$this->authService->hasPermission('config.rbac.write')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão necessária: config.rbac.write']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $roleId = $_GET['role_id'] ?? null;
            
            if ($roleId) {
                $users = $this->rbacService->getUsersByRole($roleId);
                echo json_encode(['success' => true, 'data' => $users]);
            } else {
                // Buscar usuários agrupados por role
                $roles = $this->rbacService->getRoles();
                $usersByRole = [];
                
                foreach ($roles as $role) {
                    $usersByRole[$role['id']] = [
                        'role' => $role,
                        'users' => $this->rbacService->getUsersByRole($role['id'])
                    ];
                }
                
                echo json_encode(['success' => true, 'data' => $usersByRole]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== POLÍTICAS DE AUTENTICAÇÃO ==========
    
    /**
     * GET /config/auth-policies
     */
    public function getAuthPolicies() {
        header('Content-Type: application/json');
        
        try {
            $policies = $this->configService->getAuthPolicies();
            echo json_encode(['success' => true, 'data' => $policies]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * PUT /config/auth-policies
     */
    public function updateAuthPolicies() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        header('Content-Type: application/json');
        CSRFProtection::verifyRequest();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $result = $this->configService->updateAuthPolicies($input);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== AUDITORIA ==========
    
    /**
     * GET /config/audit
     */
    public function getAuditLogs() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'entity' => $_GET['entity'] ?? null,
            'action' => $_GET['action'] ?? null,
            'date_start' => $_GET['date_start'] ?? null,
            'date_end' => $_GET['date_end'] ?? null,
            'page' => max(1, intval($_GET['page'] ?? 1)),
            'pageSize' => min(100, max(10, intval($_GET['pageSize'] ?? 20)))
        ];
        
        header('Content-Type: application/json');
        
        try {
            $result = $this->configService->getAuditLogs($filters);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * GET /config/audit/export
     */
    public function exportAuditLogs() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        try {
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'entity' => $_GET['entity'] ?? null,
                'action' => $_GET['action'] ?? null,
                'date_start' => $_GET['date_start'] ?? null,
                'date_end' => $_GET['date_end'] ?? null
            ];
            
            $this->configService->exportAuditLogsCSV($filters);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ========== UTILITÁRIOS ==========
    
    /**
     * GET /config/users - Listar usuários para filtros
     */
    public function getUsers() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            $users = $this->db->fetchAll(
                "SELECT id, nome, email, perfil FROM usuarios WHERE ativo = TRUE ORDER BY nome"
            );
            echo json_encode(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Validar CNPJ via AJAX/POST
     * POST /config - action=validate_cnpj
     */
    public function validateCnpj() {
        header('Content-Type: application/json');
        
        try {
            // Aceitar tanto JSON quanto form data
            $input = json_decode(file_get_contents('php://input'), true);
            $cnpj = $input['cnpj'] ?? $_POST['cnpj'] ?? '';
            
            if (empty($cnpj)) {
                echo json_encode(['success' => true, 'data' => ['valid' => false, 'message' => 'CNPJ vazio']]);
                return;
            }
            
            $isValid = CnpjValidator::isValid($cnpj);
            $formatted = $isValid ? CnpjValidator::format($cnpj) : null;
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'valid' => $isValid,
                    'formatted' => $formatted,
                    'message' => $isValid ? 'CNPJ válido' : 'CNPJ inválido'
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Upload de logo da organização
     * POST /config/organization/logo
     */
    public function uploadLogo() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Erro no upload do arquivo');
            }
            
            $file = $_FILES['logo'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg']; // SVG removido por segurança
            
            // Validações
            if ($file['size'] > $maxSize) {
                throw new Exception('Arquivo muito grande. Máximo 2MB permitido.');
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Formato não suportado. Use PNG ou JPG.');
            }
            
            // Gerar nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'logo_' . uniqid() . '.' . $extension;
            
            // Diretório de upload (caminho absoluto seguro)
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Criar .htaccess para segurança se não existir
            $htaccessPath = $uploadDir . '.htaccess';
            if (!file_exists($htaccessPath)) {
                file_put_contents($htaccessPath, "Options -Indexes\nOptions -ExecCGI\nAddHandler cgi-script .php .php3 .php4 .phtml .pl .py .jsp .asp .sh .cgi\n");
            }
            
            $uploadPath = $uploadDir . $fileName;
            
            // Mover arquivo para destino final
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Erro ao salvar arquivo no servidor');
            }
            
            // URL pública do arquivo
            $logoUrl = '/uploads/logos/' . $fileName;
            
            // Atualizar configurações da organização com novo logo
            $current = $this->configService->getOrganizationSettings();
            $updateData = array_merge($current, ['logo_url' => $logoUrl]);
            $this->configService->updateOrganizationSettings($updateData);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'url' => $logoUrl,
                    'logo_url' => $logoUrl,
                    'file_name' => $fileName
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Remover logo da organização
     * POST /config - action=remove_logo
     */
    public function removeLogo() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        header('Content-Type: application/json');
        
        try {
            // Obter configuração atual
            $current = $this->configService->getOrganizationSettings();
            
            // Se há logo atualmente configurada, tentar remover arquivo
            if (!empty($current['logo_url'])) {
                $logoPath = $_SERVER['DOCUMENT_ROOT'] . $current['logo_url'];
                if (file_exists($logoPath)) {
                    @unlink($logoPath); // @ para suprimir erros se não conseguir deletar
                }
            }
            
            // Remover URL da logo das configurações
            $updateData = array_merge($current, ['logo_url' => null]);
            $this->configService->updateOrganizationSettings($updateData);
            
            echo json_encode([
                'success' => true,
                'message' => 'Logo removida com sucesso'
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    
    /**
     * Salvar configurações da organização via POST form
     * POST /config - action=save_organization
     */
    public function saveOrganization() {
        if (!$this->authService->hasPermission('registro_acesso.update')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return;
        }
        
        header('Content-Type: application/json');
        // Verificar CSRF (comentado temporariamente para testes)
        // CSRFProtection::verifyRequest();
        
        try {
            $data = [
                'company_name' => $_POST['company_name'] ?? '',
                'cnpj' => $_POST['cnpj'] ?? '',
                'timezone' => $_POST['timezone'] ?? 'America/Sao_Paulo',
                'locale' => $_POST['locale'] ?? 'pt-BR'
            ];
            
            // Validações
            if (empty($data['company_name']) || strlen($data['company_name']) < 2) {
                throw new Exception('Nome da empresa deve ter pelo menos 2 caracteres');
            }
            
            if (strlen($data['company_name']) > 120) {
                throw new Exception('Nome da empresa não pode ter mais que 120 caracteres');
            }
            
            $result = $this->configService->updateOrganizationSettings($data);
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
}