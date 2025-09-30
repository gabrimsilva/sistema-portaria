<?php

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/RbacService.php';

/**
 * Serviço de Configurações do Sistema
 */
class ConfigService {
    private $db;
    private $auditService;
    private $rbacService;
    
    public function __construct() {
        $this->db = new Database();
        $this->auditService = new AuditService();
        $this->rbacService = new RbacService();
    }
    
    // ========== ORGANIZAÇÃO ==========
    
    /**
     * Buscar configurações da organização
     */
    public function getOrganizationSettings() {
        $result = $this->db->fetch(
            "SELECT * FROM organization_settings ORDER BY id LIMIT 1"
        );
        
        if (!$result) {
            // Retornar configuração padrão se não existir
            return [
                'company_name' => '',
                'cnpj' => '',
                'logo_url' => null,
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt-BR'
            ];
        }
        
        // Formatar CNPJ se existir
        if (!empty($result['cnpj'])) {
            $result['cnpj_formatted'] = CnpjValidator::format($result['cnpj']);
        }
        
        return $result;
    }
    
    /**
     * Atualizar configurações da organização
     */
    public function updateOrganizationSettings($data) {
        $current = $this->getOrganizationSettings();
        
        // Validar CNPJ se fornecido
        if (!empty($data['cnpj'])) {
            $cnpjClean = CnpjValidator::clean($data['cnpj']);
            if (!CnpjValidator::isValid($cnpjClean)) {
                throw new Exception('CNPJ inválido');
            }
            $data['cnpj'] = $cnpjClean;
        }
        
        // Se não existe configuração, criar
        if (empty($current['company_name'])) {
            $result = $this->db->fetch(
                "INSERT INTO organization_settings (company_name, cnpj, logo_url, timezone, locale) 
                 VALUES (?, ?, ?, ?, ?) RETURNING id",
                [
                    $data['company_name'],
                    $data['cnpj'] ?? null,
                    $data['logo_url'] ?? null,
                    $data['timezone'] ?? 'America/Sao_Paulo',
                    $data['locale'] ?? 'pt-BR'
                ]
            );
            
            $this->auditService->log(
                'create',
                'organization_settings',
                $result['id'],
                null,
                $data
            );
        } else {
            // Atualizar existente
            $this->db->query(
                "UPDATE organization_settings SET 
                 company_name = ?, cnpj = ?, logo_url = ?, timezone = ?, locale = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = (SELECT id FROM organization_settings ORDER BY id LIMIT 1)",
                [
                    $data['company_name'],
                    $data['cnpj'] ?? $current['cnpj'],
                    $data['logo_url'] ?? $current['logo_url'],
                    $data['timezone'] ?? $current['timezone'],
                    $data['locale'] ?? $current['locale']
                ]
            );
            
            $this->auditService->log(
                'update',
                'organization_settings',
                $current['id'] ?? 1,
                $current,
                array_merge($current, $data)
            );
        }
        
        return $this->getOrganizationSettings();
    }
    
    // ========== SITES/LOCAIS ==========
    
    /**
     * Buscar todos os sites
     */
    public function getSites() {
        return $this->db->fetchAll(
            "SELECT s.*, 
                    COUNT(sec.id) as sectors_count,
                    COALESCE(SUM(sec.capacity), 0) as total_capacity
             FROM sites s 
             LEFT JOIN sectors sec ON s.id = sec.site_id AND sec.active = TRUE
             WHERE s.active = TRUE
             GROUP BY s.id
             ORDER BY s.name"
        );
    }
    
    /**
     * Criar novo site
     */
    public function createSite($data) {
        // Validações
        if (empty($data['name'])) {
            throw new Exception('Nome do local é obrigatório');
        }
        
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            throw new Exception('Capacidade deve ser maior ou igual a zero');
        }
        
        $result = $this->db->fetch(
            "INSERT INTO sites (name, capacity, address, timezone, active) 
             VALUES (?, ?, ?, ?, ?) RETURNING id",
            [
                $data['name'],
                $data['capacity'] ?? 0,
                $data['address'] ?? null,
                $data['timezone'] ?? 'America/Sao_Paulo',
                $data['active'] ?? true
            ]
        );
        
        $siteId = $result['id'];
        
        $this->auditService->log(
            'create',
            'sites',
            $siteId,
            null,
            $data
        );
        
        return $siteId;
    }
    
    /**
     * Atualizar site
     */
    public function updateSite($siteId, $data) {
        $current = $this->db->fetch("SELECT * FROM sites WHERE id = ?", [$siteId]);
        if (!$current) {
            throw new Exception('Site não encontrado');
        }
        
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            throw new Exception('Capacidade deve ser maior ou igual a zero');
        }
        
        $this->db->query(
            "UPDATE sites SET name = ?, capacity = ?, address = ?, timezone = ?, active = ?, updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [
                $data['name'] ?? $current['name'],
                $data['capacity'] ?? $current['capacity'],
                $data['address'] ?? $current['address'],
                $data['timezone'] ?? $current['timezone'],
                $data['active'] ?? $current['active'],
                $siteId
            ]
        );
        
        $this->auditService->log(
            'update',
            'sites',
            $siteId,
            $current,
            array_merge($current, $data)
        );
        
        return true;
    }
    
    /**
     * Deletar site (soft delete)
     */
    public function deleteSite($siteId) {
        $current = $this->db->fetch("SELECT * FROM sites WHERE id = ?", [$siteId]);
        if (!$current) {
            throw new Exception('Site não encontrado');
        }
        
        // Soft delete - marcar como inativo
        $this->db->query(
            "UPDATE sites SET active = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$siteId]
        );
        
        // Também desativar setores do site
        $this->db->query(
            "UPDATE sectors SET active = FALSE, updated_at = CURRENT_TIMESTAMP WHERE site_id = ?",
            [$siteId]
        );
        
        $this->auditService->log(
            'delete',
            'sites',
            $siteId,
            $current,
            ['active' => false]
        );
        
        return true;
    }
    
    // ========== SETORES ==========
    
    /**
     * Buscar setores de um site
     */
    public function getSectorsBySite($siteId) {
        return $this->db->fetchAll(
            "SELECT * FROM sectors WHERE site_id = ? AND active = TRUE ORDER BY name",
            [$siteId]
        );
    }
    
    /**
     * Criar novo setor
     */
    public function createSector($data) {
        if (empty($data['name']) || empty($data['site_id'])) {
            throw new Exception('Nome e site são obrigatórios');
        }
        
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            throw new Exception('Capacidade deve ser maior ou igual a zero');
        }
        
        // Verificar se site existe
        $site = $this->db->fetch("SELECT id FROM sites WHERE id = ? AND active = TRUE", [$data['site_id']]);
        if (!$site) {
            throw new Exception('Site não encontrado');
        }
        
        $result = $this->db->fetch(
            "INSERT INTO sectors (site_id, name, capacity, active) 
             VALUES (?, ?, ?, ?) RETURNING id",
            [
                $data['site_id'],
                $data['name'],
                $data['capacity'] ?? 0,
                $data['active'] ?? true
            ]
        );
        
        $sectorId = $result['id'];
        
        $this->auditService->log(
            'create',
            'sectors',
            $sectorId,
            null,
            $data
        );
        
        return $sectorId;
    }
    
    /**
     * Atualizar setor
     */
    public function updateSector($sectorId, $data) {
        $current = $this->db->fetch("SELECT * FROM sectors WHERE id = ?", [$sectorId]);
        if (!$current) {
            throw new Exception('Setor não encontrado');
        }
        
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            throw new Exception('Capacidade deve ser maior ou igual a zero');
        }
        
        $this->db->query(
            "UPDATE sectors SET name = ?, capacity = ?, active = ?, updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [
                $data['name'] ?? $current['name'],
                $data['capacity'] ?? $current['capacity'],
                $data['active'] ?? $current['active'],
                $sectorId
            ]
        );
        
        $this->auditService->log(
            'update',
            'sectors',
            $sectorId,
            $current,
            array_merge($current, $data)
        );
        
        return true;
    }
    
    /**
     * Deletar setor (soft delete)
     */
    public function deleteSector($sectorId) {
        $current = $this->db->fetch("SELECT * FROM sectors WHERE id = ?", [$sectorId]);
        if (!$current) {
            throw new Exception('Setor não encontrado');
        }
        
        // Soft delete - marcar como inativo
        $this->db->query(
            "UPDATE sectors SET active = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$sectorId]
        );
        
        $this->auditService->log(
            'delete',
            'sectors',
            $sectorId,
            $current,
            ['active' => false]
        );
        
        return true;
    }
    
    // ========== RBAC ==========
    
    /**
     * Buscar matriz RBAC (delegando para RbacService)
     */
    public function getRbacMatrix() {
        return $this->rbacService->getRbacMatrix();
    }
    
    /**
     * Atualizar permissões de role (delegando para RbacService)
     */
    public function updateRolePermissions($roleId, $permissions) {
        return $this->rbacService->updateRolePermissions($roleId, $permissions);
    }
    
    /**
     * Buscar usuários organizados por role (delegando para RbacService)
     */
    public function getRbacUsers() {
        $roles = $this->rbacService->getRoles();
        $usersByRole = [];
        
        foreach ($roles as $role) {
            $usersByRole[$role['id']] = $this->rbacService->getUsersByRole($role['id']);
        }
        
        return [
            'roles' => $roles,
            'usersByRole' => $usersByRole
        ];
    }
    
    // ========== POLÍTICAS DE AUTENTICAÇÃO ==========
    
    /**
     * Buscar políticas de autenticação
     */
    public function getAuthPolicies() {
        $result = $this->db->fetch("SELECT * FROM auth_policies ORDER BY id LIMIT 1");
        
        if (!$result) {
            // Retornar configuração padrão
            return [
                'password_min_length' => 8,
                'password_expiry_days' => 90,
                'session_timeout_minutes' => 1440,
                'require_2fa' => false,
                'enable_sso' => false,
                'sso_provider' => null,
                'sso_client_id' => null,
                'sso_issuer' => null
            ];
        }
        
        return $result;
    }
    
    /**
     * Atualizar políticas de autenticação
     */
    public function updateAuthPolicies($data) {
        $current = $this->getAuthPolicies();
        
        // Validações
        if (isset($data['password_min_length']) && $data['password_min_length'] < 4) {
            throw new Exception('Senha deve ter pelo menos 4 caracteres');
        }
        
        if (isset($data['password_expiry_days']) && $data['password_expiry_days'] <= 0) {
            throw new Exception('Expiração de senha deve ser maior que zero');
        }
        
        if (isset($data['session_timeout_minutes']) && $data['session_timeout_minutes'] <= 0) {
            throw new Exception('Timeout de sessão deve ser maior que zero');
        }
        
        // Se não existe configuração, criar
        if (!isset($current['id'])) {
            $result = $this->db->fetch(
                "INSERT INTO auth_policies (password_min_length, password_expiry_days, session_timeout_minutes, require_2fa, enable_sso, sso_provider, sso_client_id, sso_issuer) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id",
                [
                    $data['password_min_length'] ?? 8,
                    $data['password_expiry_days'] ?? 90,
                    $data['session_timeout_minutes'] ?? 1440,
                    $data['require_2fa'] ?? false,
                    $data['enable_sso'] ?? false,
                    $data['sso_provider'] ?? null,
                    $data['sso_client_id'] ?? null,
                    $data['sso_issuer'] ?? null
                ]
            );
            
            $this->auditService->log(
                'create',
                'auth_policies',
                $result['id'],
                null,
                $data
            );
        } else {
            // Atualizar existente
            $this->db->query(
                "UPDATE auth_policies SET 
                 password_min_length = ?, password_expiry_days = ?, session_timeout_minutes = ?, 
                 require_2fa = ?, enable_sso = ?, sso_provider = ?, sso_client_id = ?, sso_issuer = ?, 
                 updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ?",
                [
                    $data['password_min_length'] ?? $current['password_min_length'],
                    $data['password_expiry_days'] ?? $current['password_expiry_days'],
                    $data['session_timeout_minutes'] ?? $current['session_timeout_minutes'],
                    $data['require_2fa'] ?? $current['require_2fa'],
                    $data['enable_sso'] ?? $current['enable_sso'],
                    $data['sso_provider'] ?? $current['sso_provider'],
                    $data['sso_client_id'] ?? $current['sso_client_id'],
                    $data['sso_issuer'] ?? $current['sso_issuer'],
                    $current['id']
                ]
            );
            
            $this->auditService->log(
                'update',
                'auth_policies',
                $current['id'],
                $current,
                array_merge($current, $data)
            );
        }
        
        return $this->getAuthPolicies();
    }
    
    // ========== AUDITORIA ==========
    
    /**
     * Buscar logs de auditoria com filtros e paginação
     */
    public function getAuditLogs($filters) {
        $sql = "SELECT al.*, u.nome as usuario_nome, al.severidade, al.modulo, al.resultado
                FROM audit_log al 
                LEFT JOIN usuarios u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['entity'])) {
            $sql .= " AND al.entidade = ?";
            $params[] = $filters['entity'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.acao = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['severidade'])) {
            $sql .= " AND al.severidade = ?";
            $params[] = $filters['severidade'];
        }
        
        if (!empty($filters['modulo'])) {
            $sql .= " AND al.modulo = ?";
            $params[] = $filters['modulo'];
        }
        
        if (!empty($filters['date_start'])) {
            $sql .= " AND DATE(al.timestamp) >= ?";
            $params[] = $filters['date_start'];
        }
        
        if (!empty($filters['date_end'])) {
            $sql .= " AND DATE(al.timestamp) <= ?";
            $params[] = $filters['date_end'];
        }
        
        // Contar total
        $countSql = str_replace("SELECT al.*, u.nome as usuario_nome", "SELECT COUNT(*)", $sql);
        $total = $this->db->fetch($countSql, $params)['count'];
        
        // Paginação
        $offset = ($filters['page'] - 1) * $filters['pageSize'];
        $sql .= " ORDER BY al.timestamp DESC LIMIT ? OFFSET ?";
        $params[] = $filters['pageSize'];
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // Processar dados JSON
        foreach ($logs as &$log) {
            if (!empty($log['dados_antes'])) {
                $log['dados_antes_parsed'] = json_decode($log['dados_antes'], true);
            }
            if (!empty($log['dados_depois'])) {
                $log['dados_depois_parsed'] = json_decode($log['dados_depois'], true);
            }
            $log['timestamp_formatted'] = date('d/m/Y H:i:s', strtotime($log['timestamp']));
        }
        
        return [
            'logs' => $logs,
            'pagination' => [
                'current' => $filters['page'],
                'total' => ceil($total / $filters['pageSize']),
                'totalItems' => $total,
                'pageSize' => $filters['pageSize']
            ]
        ];
    }
    
    /**
     * Exportar logs de auditoria para CSV
     */
    public function exportAuditLogsCSV($filters) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        
        // Cabeçalhos
        fputcsv($output, [
            'ID', 'Data/Hora', 'Usuário', 'Ação', 'Entidade', 'ID Entidade',
            'Dados Antes', 'Dados Depois', 'IP', 'User Agent'
        ]);
        
        // Buscar logs sem paginação para exportação completa
        $sql = "SELECT al.*, u.nome as usuario_nome 
                FROM audit_log al 
                LEFT JOIN usuarios u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['entity'])) {
            $sql .= " AND al.entidade = ?";
            $params[] = $filters['entity'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.acao = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['date_start'])) {
            $sql .= " AND DATE(al.timestamp) >= ?";
            $params[] = $filters['date_start'];
        }
        
        if (!empty($filters['date_end'])) {
            $sql .= " AND DATE(al.timestamp) <= ?";
            $params[] = $filters['date_end'];
        }
        
        $sql .= " ORDER BY al.timestamp DESC";
        
        $logs = $this->db->fetchAll($sql, $params);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                date('d/m/Y H:i:s', strtotime($log['timestamp'])),
                $log['usuario_nome'] ?? 'Sistema',
                $log['acao'],
                $log['entidade'],
                $log['entidade_id'],
                $log['dados_antes'] ? json_encode(json_decode($log['dados_antes']), JSON_UNESCAPED_UNICODE) : '',
                $log['dados_depois'] ? json_encode(json_decode($log['dados_depois']), JSON_UNESCAPED_UNICODE) : '',
                $log['ip_address'],
                substr($log['user_agent'], 0, 100) // Truncar user agent para CSV
            ]);
        }
        
        fclose($output);
        exit;
    }
}