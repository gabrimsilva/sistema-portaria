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
     */
    public function updateRolePermissions($roleId, $permissionKeys) {
        // Buscar role
        $role = $this->db->fetch("SELECT * FROM roles WHERE id = ?", [$roleId]);
        if (!$role) {
            throw new Exception("Role não encontrada");
        }
        
        // Buscar permissões atuais para auditoria
        $currentPermissions = $this->db->fetchAll(
            "SELECT p.key FROM role_permissions rp 
             JOIN permissions p ON rp.permission_id = p.id 
             WHERE rp.role_id = ?",
            [$roleId]
        );
        $current = array_column($currentPermissions, 'key');
        
        // Proteção: Admin sempre deve ter config.write
        if ($role['name'] === 'administrador' && !in_array('config.write', $permissionKeys)) {
            $permissionKeys[] = 'config.write';
        }
        
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
}