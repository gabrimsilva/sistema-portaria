<?php

require_once __DIR__ . '/AuditService.php';

/**
 * Serviço RBAC baseado em banco de dados
 * Sistema paralelo ao AuthorizationService (mantém compatibilidade)
 */
class RbacService {
    private $db;
    private $auditService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
    }
    
    /**
     * Buscar todas as roles ativas
     */
    public function getRoles() {
        return $this->db->fetchAll(
            "SELECT id, name, description, system_role, active 
             FROM roles 
             WHERE active = TRUE 
             ORDER BY 
               CASE 
                 WHEN name = 'administrador' THEN 1
                 WHEN name = 'seguranca' THEN 2  
                 WHEN name = 'recepcao' THEN 3
                 WHEN name = 'rh' THEN 4
                 WHEN name = 'porteiro' THEN 5
                 ELSE 6
               END, name"
        );
    }
    
    /**
     * Buscar todas as permissions agrupadas por módulo
     */
    public function getPermissions() {
        return $this->db->fetchAll(
            "SELECT id, key, description, module 
             FROM permissions 
             ORDER BY 
               CASE module 
                 WHEN 'config' THEN 1
                 WHEN 'reports' THEN 2
                 WHEN 'access' THEN 3
                 WHEN 'audit' THEN 4
                 WHEN 'users' THEN 5
                 WHEN 'privacy' THEN 6
                 ELSE 7
               END, key"
        );
    }
    
    /**
     * Buscar matriz completa RBAC
     */
    public function getRbacMatrix() {
        $roles = $this->getRoles();
        $permissions = $this->getPermissions();
        
        // Buscar matriz atual
        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissions = $this->db->fetchAll(
                "SELECT p.key FROM role_permissions rp 
                 JOIN permissions p ON rp.permission_id = p.id 
                 WHERE rp.role_id = ?",
                [$role['id']]
            );
            
            $matrix[$role['id']] = array_column($rolePermissions, 'key');
        }
        
        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $matrix
        ];
    }
    
    /**
     * Atualizar permissões de uma role
     * @param bool $manageTransaction Se deve gerenciar transação (padrão: true)
     */
    public function updateRolePermissions($roleId, $permissionKeys, $manageTransaction = true) {
        // Buscar role
        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
        if (!$role) {
            throw new Exception("Role não encontrada");
        }
        
        // ⚡ VALIDAÇÃO CRÍTICA DE SEGURANÇA ⚡
        // Validar se todas as permissões solicitadas existem
        $validPermissions = $this->db->fetchAll("SELECT key FROM permissions");
        $validKeys = array_column($validPermissions, 'key');
        
        foreach ($permissionKeys as $permKey) {
            if (!in_array($permKey, $validKeys)) {
                throw new Exception("Permissão inválida: " . $permKey);
            }
        }
        
        // 🛡️ PROTEÇÃO CRÍTICA: Admin SEMPRE mantém TODAS as permissões config.*
        if ($role['name'] === 'administrador') {
            $configPermissions = $this->db->fetchAll(
                "SELECT key FROM permissions WHERE key LIKE 'config.%'"
            );
            $configKeys = array_column($configPermissions, 'key');
            
            // Garantir que todas as config.* estejam sempre presentes para Admin
            foreach ($configKeys as $configKey) {
                if (!in_array($configKey, $permissionKeys)) {
                    $permissionKeys[] = $configKey;
                }
            }
        }
        
        // Buscar permissões atuais para auditoria
        $currentPermissions = $this->db->fetchAll(
            "SELECT p.key FROM role_permissions rp 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE rp.role_id = ?",
            [$roleId]
        );
        $current = array_column($currentPermissions, 'key');
        
        // 💾 TRANSAÇÃO PARA GARANTIR INTEGRIDADE (apenas se solicitado)
        if ($manageTransaction) {
            $this->db->query("BEGIN");
        }
        
        try {
            // Deduplicar permissões para evitar conflitos
            $permissionKeys = array_unique($permissionKeys);
            
            // Remover todas as permissões atuais
            $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
            
            // Inserir novas permissões
            foreach ($permissionKeys as $permissionKey) {
                $permission = $this->db->fetch(
                    "SELECT id FROM permissions WHERE key = ?",
                    [$permissionKey]
                );
                
                if ($permission) {
                    $this->db->query(
                        "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                        [$roleId, $permission['id']]
                    );
                }
            }
            
            // Commit da transação (apenas se gerenciando)
            if ($manageTransaction) {
                $this->db->query("COMMIT");
            }
            
        } catch (Exception $e) {
            // Rollback em caso de erro (apenas se gerenciando)
            if ($manageTransaction) {
                $this->db->query("ROLLBACK");
            }
            throw new Exception("Erro ao atualizar permissões da role: " . $e->getMessage());
        }
        
        // Log de auditoria
        $this->auditService->log(
            'update',
            'role_permissions',
            $roleId,
            ['role' => $role['name'], 'permissions' => $current],
            ['role' => $role['name'], 'permissions' => $permissionKeys]
        );
        
        return true;
    }
    
    /**
     * Verificar se uma role tem uma permissão específica
     */
    public function roleHasPermission($roleId, $permissionKey) {
        $result = $this->db->fetch(
            "SELECT 1 FROM role_permissions rp 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE rp.role_id = ? AND p.key = ?",
            [$roleId, $permissionKey]
        );
        
        return $result !== false;
    }
    
    /**
     * Buscar permissões de uma role específica
     */
    public function getRolePermissions($roleId) {
        $permissions = $this->db->fetchAll(
            "SELECT p.key, p.description, p.module FROM role_permissions rp 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE rp.role_id = ? 
             ORDER BY p.module, p.key",
            [$roleId]
        );
        
        return array_column($permissions, 'key');
    }
    
    /**
     * Criar nova role personalizada
     */
    public function createRole($name, $description = null) {
        // Verificar se já existe
        $exists = $this->db->fetch("SELECT id FROM roles WHERE name = ?", [$name]);
        if ($exists) {
            throw new Exception("Role '{$name}' já existe");
        }
        
        $result = $this->db->fetch(
            "INSERT INTO roles (name, description, system_role, active) 
             VALUES (?, ?, FALSE, TRUE) RETURNING id",
            [$name, $description]
        );
        
        $roleId = $result['id'];
        
        // Log de auditoria
        $this->auditService->log(
            'create',
            'roles',
            $roleId,
            null,
            ['name' => $name, 'description' => $description]
        );
        
        return $roleId;
    }
    
    /**
     * Buscar usuários por role
     */
    public function getUsersByRole($roleId) {
        return $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login 
             FROM usuarios u 
             WHERE u.role_id = ? 
             ORDER BY u.nome",
            [$roleId]
        );
    }
    
    /**
     * Atualizar role de um usuário
     */
    public function updateUserRole($userId, $roleId) {
        // Buscar dados atuais
        $currentUser = $this->db->fetch("SELECT role_id, perfil FROM usuarios WHERE id = ?", [$userId]);
        
        // Atualizar
        $this->db->query(
            "UPDATE usuarios SET role_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$roleId, $userId]
        );
        
        // Log de auditoria
        $this->auditService->log(
            'update',
            'usuarios',
            $userId,
            ['role_id' => $currentUser['role_id']],
            ['role_id' => $roleId]
        );
        
        return true;
    }
    
    /**
     * Salvar matriz RBAC completa
     */
    public function saveRbacMatrix($matrix) {
        $this->db->query("BEGIN");
        
        try {
            foreach ($matrix as $roleId => $permissions) {
                // Validar se a role existe
                $role = $this->db->fetch("SELECT id FROM roles WHERE id = ?", [$roleId]);
                if (!$role) {
                    throw new Exception("Role {$roleId} não encontrada");
                }
                
                // Atualizar permissões desta role (sem gerenciar transação)
                $this->updateRolePermissions($roleId, $permissions, false);
            }
            
            $this->db->query("COMMIT");
            
            // Log de auditoria
            $this->auditService->log(
                'update',
                'rbac_matrix',
                null,
                null,
                ['matrix_updated' => count($matrix) . ' roles']
            );
            
            return true;
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw new Exception("Erro ao salvar matriz RBAC: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar todos os usuários agrupados por roles
     */
    public function getAllUsersByRoles() {
        $users = $this->db->fetchAll(
            "SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login, u.role_id,
                    r.name as role_name, r.description as role_description
             FROM usuarios u 
             LEFT JOIN roles r ON u.role_id = r.id 
             ORDER BY r.name, u.nome"
        );
        
        // Agrupar por role
        $grouped = [];
        foreach ($users as $user) {
            $roleId = $user['role_id'] ?? 'no_role';
            $roleName = $user['role_name'] ?? 'Sem Role';
            
            if (!isset($grouped[$roleId])) {
                $grouped[$roleId] = [
                    'role_id' => $roleId,
                    'role_name' => $roleName,
                    'role_description' => $user['role_description'] ?? '',
                    'users' => []
                ];
            }
            
            $grouped[$roleId]['users'][] = [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'ativo' => $user['ativo'],
                'ultimo_login' => $user['ultimo_login']
            ];
        }
        
        return array_values($grouped);
    }
}