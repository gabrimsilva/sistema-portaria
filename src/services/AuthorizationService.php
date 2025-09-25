<?php

require_once __DIR__ . '/RbacService.php';
require_once __DIR__ . '/../config/Database.php';

class AuthorizationService {
    private $rbacService;
    
    public function __construct() {
        $this->rbacService = new RbacService();
    }
    
    // Definição de permissões por perfil (COMPATIBILIDADE - sistema legado)
    private const PERMISSIONS = [
        'administrador' => [
            'registro_acesso.create',
            'registro_acesso.read',
            'registro_acesso.update',
            'registro_acesso.delete',
            'registro_acesso.edit_all_fields',
            'audit_log.read',
            'usuarios.manage'
        ],
        'seguranca' => [
            'registro_acesso.create',
            'registro_acesso.read',
            'registro_acesso.update',
            'registro_acesso.edit_basic_fields'
        ],
        'recepcao' => [
            'registro_acesso.create',
            'registro_acesso.read',
            'registro_acesso.update',
            'registro_acesso.edit_basic_fields'
        ],
        'porteiro' => [
            'registro_acesso.create',
            'registro_acesso.read',
            'registro_acesso.update',
            'registro_acesso.checkin',
            'registro_acesso.checkout'
        ]
    ];
    
    // Campos que cada perfil pode editar
    private const EDITABLE_FIELDS = [
        'administrador' => ['*'], // Pode editar todos os campos
        'seguranca' => [
            'nome', 'empresa', 'setor', 'placa_veiculo', 
            'funcionario_responsavel', 'observacao', 'entrada_at', 'saida_at'
        ],
        'recepcao' => [
            'nome', 'empresa', 'setor', 'placa_veiculo', 
            'funcionario_responsavel', 'observacao'
        ],
        'porteiro' => ['observacao'] // Apenas observações
    ];
    
    /**
     * Verifica se o usuário tem uma permissão específica
     * Híbrido: sistema legado + novo RBAC
     */
    public function hasPermission($permission) {
        $userProfile = $_SESSION['user_profile'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userProfile || !$userId) {
            return false;
        }
        
        // 1️⃣ PRIMEIRO: Verificar sistema legado (compatibilidade)
        $userPermissions = self::PERMISSIONS[$userProfile] ?? [];
        if (in_array($permission, $userPermissions)) {
            return true;
        }
        
        // 2️⃣ SEGUNDO: Verificar sistema RBAC moderno (banco de dados)
        try {
            // Buscar role_id do usuário
            $db = new Database();
            $user = $db->fetch("SELECT role_id FROM usuarios WHERE id = ?", [$userId]);
            
            if ($user && $user['role_id']) {
                return $this->rbacService->roleHasPermission($user['role_id'], $permission);
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão RBAC: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Verifica se o usuário pode editar um campo específico
     */
    public function canEditField($field) {
        $userProfile = $_SESSION['user_profile'] ?? null;
        
        if (!$userProfile) {
            return false;
        }
        
        $editableFields = self::EDITABLE_FIELDS[$userProfile] ?? [];
        
        // Administrador pode editar todos os campos
        if (in_array('*', $editableFields)) {
            return true;
        }
        
        return in_array($field, $editableFields);
    }
    
    /**
     * Filtra dados de entrada removendo campos não editáveis
     */
    public function filterEditableData($data) {
        $userProfile = $_SESSION['user_profile'] ?? null;
        
        if (!$userProfile) {
            return [];
        }
        
        $editableFields = self::EDITABLE_FIELDS[$userProfile] ?? [];
        
        // Administrador pode editar todos os campos
        if (in_array('*', $editableFields)) {
            return $data;
        }
        
        $filteredData = [];
        foreach ($data as $field => $value) {
            if (in_array($field, $editableFields)) {
                $filteredData[$field] = $value;
            }
        }
        
        return $filteredData;
    }
    
    /**
     * Middleware para verificar permissão
     */
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Permissão insuficiente.',
                'required_permission' => $permission,
                'user_profile' => $_SESSION['user_profile'] ?? 'não definido'
            ]);
            exit;
        }
    }
    
    /**
     * Retorna lista de permissões do usuário atual
     */
    public function getUserPermissions() {
        $userProfile = $_SESSION['user_profile'] ?? null;
        return self::PERMISSIONS[$userProfile] ?? [];
    }
    
    /**
     * Retorna campos editáveis para o usuário atual
     */
    public function getUserEditableFields() {
        $userProfile = $_SESSION['user_profile'] ?? null;
        return self::EDITABLE_FIELDS[$userProfile] ?? [];
    }
}