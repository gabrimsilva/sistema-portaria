# PLANO DE IMPLEMENTAÇÃO - MÓDULO CONFIGURAÇÕES
## Sistema de Controle de Acesso (PHP + PostgreSQL)

---

## 1. MODELAGEM & MIGRAÇÕES

### 1.1 Organização & Configurações Gerais

```sql
-- Tabela de configurações organizacionais
CREATE TABLE IF NOT EXISTS organization_settings (
    id SERIAL PRIMARY KEY,
    company_name VARCHAR(120) NOT NULL CHECK (char_length(company_name) >= 2),
    cnpj VARCHAR(18) UNIQUE NOT NULL, -- Formato: XX.XXX.XXX/XXXX-XX
    logo_url VARCHAR(500),
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    locale VARCHAR(10) DEFAULT 'pt-BR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_organization_settings_updated_at 
    BEFORE UPDATE ON organization_settings 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Inserir configuração inicial
INSERT INTO organization_settings (company_name, cnpj, timezone, locale) 
VALUES ('Empresa Exemplo', '00.000.000/0000-00', 'America/Sao_Paulo', 'pt-BR')
ON CONFLICT DO NOTHING;
```

### 1.2 Locais e Setores

```sql
-- Tabela de sites/locais
CREATE TABLE IF NOT EXISTS sites (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    capacity INTEGER DEFAULT 0 CHECK (capacity >= 0),
    address TEXT,
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Atualizar tabela de setores para incluir relacionamento com sites
CREATE TABLE IF NOT EXISTS sectors (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    capacity INTEGER DEFAULT 0 CHECK (capacity >= 0),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, name)
);

-- Trigger para updated_at
CREATE TRIGGER update_sites_updated_at 
    BEFORE UPDATE ON sites 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_sectors_updated_at 
    BEFORE UPDATE ON sectors 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Índices
CREATE INDEX IF NOT EXISTS idx_sectors_site_id ON sectors(site_id);
CREATE INDEX IF NOT EXISTS idx_sites_active ON sites(active);
CREATE INDEX IF NOT EXISTS idx_sectors_active ON sectors(active);
```

### 1.3 Horários de Funcionamento

```sql
-- Horários normais de funcionamento
CREATE TABLE IF NOT EXISTS business_hours (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    weekday INTEGER NOT NULL CHECK (weekday >= 0 AND weekday <= 6), -- 0=domingo, 6=sábado
    open_at TIME,
    close_at TIME,
    closed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, weekday)
);

-- Exceções de horário (para datas específicas)
CREATE TABLE IF NOT EXISTS business_hour_exceptions (
    id SERIAL PRIMARY KEY,
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    open_at TIME,
    close_at TIME,
    closed BOOLEAN DEFAULT FALSE,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(site_id, date)
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_business_hours_site_weekday ON business_hours(site_id, weekday);
CREATE INDEX IF NOT EXISTS idx_business_hour_exceptions_site_date ON business_hour_exceptions(site_id, date);
```

### 1.4 Feriados

```sql
-- Feriados
CREATE TABLE IF NOT EXISTS holidays (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    name VARCHAR(100) NOT NULL,
    scope VARCHAR(10) DEFAULT 'global' CHECK (scope IN ('global', 'site')),
    site_id INTEGER REFERENCES sites(id) ON DELETE CASCADE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK ((scope = 'global' AND site_id IS NULL) OR (scope = 'site' AND site_id IS NOT NULL))
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_holidays_date ON holidays(date);
CREATE INDEX IF NOT EXISTS idx_holidays_site_date ON holidays(site_id, date);
```

### 1.5 RBAC (Roles, Permissions, User Roles)

```sql
-- Tabela de perfis/roles
CREATE TABLE IF NOT EXISTS roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    system_role BOOLEAN DEFAULT FALSE, -- Roles do sistema (não podem ser removidos)
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de permissões
CREATE TABLE IF NOT EXISTS permissions (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    module VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relacionamento roles-permissions
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
    permission_id INTEGER REFERENCES permissions(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id)
);

-- Atualizar tabela usuarios para usar role_id
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS role_id INTEGER REFERENCES roles(id);

-- Trigger para updated_at
CREATE TRIGGER update_roles_updated_at 
    BEFORE UPDATE ON roles 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Índices
CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions(permission_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_role ON usuarios(role_id);
```

### 1.6 Políticas de Autenticação

```sql
-- Políticas de autenticação
CREATE TABLE IF NOT EXISTS auth_policies (
    id SERIAL PRIMARY KEY,
    password_min_length INTEGER DEFAULT 8 CHECK (password_min_length >= 4),
    password_expiry_days INTEGER DEFAULT 90 CHECK (password_expiry_days > 0),
    session_timeout_minutes INTEGER DEFAULT 1440 CHECK (session_timeout_minutes > 0), -- 24h
    require_2fa BOOLEAN DEFAULT FALSE,
    enable_sso BOOLEAN DEFAULT FALSE,
    sso_provider VARCHAR(50),
    sso_client_id VARCHAR(255),
    sso_issuer VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para updated_at
CREATE TRIGGER update_auth_policies_updated_at 
    BEFORE UPDATE ON auth_policies 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Inserir política padrão
INSERT INTO auth_policies (password_min_length, password_expiry_days, session_timeout_minutes) 
VALUES (8, 90, 1440)
ON CONFLICT DO NOTHING;
```

### 1.7 Dados Iniciais (Seeds)

```sql
-- Inserir roles padrão
INSERT INTO roles (name, description, system_role) VALUES
('administrador', 'Administrador do sistema', TRUE),
('seguranca', 'Equipe de segurança', TRUE),
('recepcao', 'Equipe de recepção', TRUE),
('rh', 'Recursos Humanos', TRUE),
('porteiro', 'Porteiro/Guarita', TRUE)
ON CONFLICT (name) DO NOTHING;

-- Inserir permissões
INSERT INTO permissions (key, description, module) VALUES
-- Configurações
('config.read', 'Visualizar configurações', 'config'),
('config.write', 'Editar configurações', 'config'),
-- Relatórios
('report.read', 'Visualizar relatórios', 'reports'),
('report.export', 'Exportar relatórios', 'reports'),
-- LGPD
('person.cpf.view_unmasked', 'Ver CPF não mascarado', 'privacy'),
-- Registro de acesso
('registro_acesso.create', 'Criar registro de acesso', 'access'),
('registro_acesso.read', 'Visualizar registros de acesso', 'access'),
('registro_acesso.update', 'Editar registros de acesso', 'access'),
('registro_acesso.delete', 'Excluir registros de acesso', 'access'),
-- Auditoria
('audit.read', 'Visualizar logs de auditoria', 'audit'),
('audit.export', 'Exportar logs de auditoria', 'audit'),
-- Usuários
('users.manage', 'Gerenciar usuários', 'users')
ON CONFLICT (key) DO NOTHING;

-- Atribuir permissões aos roles
WITH role_permission_mappings AS (
    SELECT r.id as role_id, p.id as permission_id
    FROM roles r, permissions p
    WHERE 
        (r.name = 'administrador') OR
        (r.name = 'seguranca' AND p.key IN ('registro_acesso.create', 'registro_acesso.read', 'registro_acesso.update', 'report.read', 'report.export')) OR
        (r.name = 'recepcao' AND p.key IN ('registro_acesso.create', 'registro_acesso.read', 'registro_acesso.update', 'report.read')) OR
        (r.name = 'rh' AND p.key IN ('report.read', 'report.export', 'person.cpf.view_unmasked')) OR
        (r.name = 'porteiro' AND p.key IN ('registro_acesso.create', 'registro_acesso.read', 'registro_acesso.update'))
)
INSERT INTO role_permissions (role_id, permission_id)
SELECT role_id, permission_id FROM role_permission_mappings
ON CONFLICT DO NOTHING;
```

---

## 2. BACK-END (PHP Controllers & Services)

### 2.1 ConfigController.php

```diff
+<?php
+
+require_once __DIR__ . '/../services/ConfigService.php';
+require_once __DIR__ . '/../services/AuthorizationService.php';
+require_once __DIR__ . '/../utils/CnpjValidator.php';
+
+class ConfigController {
+    private $db;
+    private $authService;
+    private $configService;
+    
+    public function __construct() {
+        $this->checkAuthentication();
+        $this->db = new Database();
+        $this->authService = new AuthorizationService();
+        $this->configService = new ConfigService();
+    }
+    
+    private function checkAuthentication() {
+        if (!isset($_SESSION['user_id'])) {
+            header('Location: /login');
+            exit;
+        }
+    }
+    
+    public function index() {
+        $this->authService->requirePermission('config.read');
+        include '../views/config/index.php';
+    }
+    
+    // === ORGANIZAÇÃO ===
+    public function getOrganization() {
+        $this->authService->requirePermission('config.read');
+        header('Content-Type: application/json');
+        
+        try {
+            $org = $this->configService->getOrganizationSettings();
+            echo json_encode(['success' => true, 'data' => $org]);
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    public function updateOrganization() {
+        $this->authService->requirePermission('config.write');
+        
+        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
+            http_response_code(405);
+            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
+            return;
+        }
+        
+        header('Content-Type: application/json');
+        CSRFProtection::verifyRequest();
+        
+        try {
+            $input = json_decode(file_get_contents('php://input'), true);
+            
+            // Validações
+            if (empty($input['company_name']) || strlen($input['company_name']) < 2) {
+                throw new Exception('Nome da empresa deve ter pelo menos 2 caracteres');
+            }
+            
+            if (!empty($input['cnpj']) && !CnpjValidator::isValid($input['cnpj'])) {
+                throw new Exception('CNPJ inválido');
+            }
+            
+            $result = $this->configService->updateOrganizationSettings($input);
+            echo json_encode(['success' => true, 'data' => $result]);
+            
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    // === SITES ===
+    public function getSites() {
+        $this->authService->requirePermission('config.read');
+        header('Content-Type: application/json');
+        
+        try {
+            $sites = $this->configService->getSites();
+            echo json_encode(['success' => true, 'data' => $sites]);
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    public function createSite() {
+        $this->authService->requirePermission('config.write');
+        
+        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
+            http_response_code(405);
+            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
+            return;
+        }
+        
+        header('Content-Type: application/json');
+        CSRFProtection::verifyRequest();
+        
+        try {
+            $input = json_decode(file_get_contents('php://input'), true);
+            
+            if (empty($input['name'])) {
+                throw new Exception('Nome do local é obrigatório');
+            }
+            
+            if (isset($input['capacity']) && $input['capacity'] < 0) {
+                throw new Exception('Capacidade deve ser maior ou igual a zero');
+            }
+            
+            $siteId = $this->configService->createSite($input);
+            echo json_encode(['success' => true, 'data' => ['id' => $siteId]]);
+            
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    // === RBAC ===
+    public function getRbacMatrix() {
+        $this->authService->requirePermission('config.read');
+        header('Content-Type: application/json');
+        
+        try {
+            $matrix = $this->configService->getRbacMatrix();
+            echo json_encode(['success' => true, 'data' => $matrix]);
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    public function updateRolePermissions() {
+        $this->authService->requirePermission('config.write');
+        
+        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
+            http_response_code(405);
+            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
+            return;
+        }
+        
+        header('Content-Type: application/json');
+        CSRFProtection::verifyRequest();
+        
+        try {
+            $input = json_decode(file_get_contents('php://input'), true);
+            
+            // Nunca remover config.write do admin
+            if ($input['role_name'] === 'administrador') {
+                if (!in_array('config.write', $input['permissions'])) {
+                    $input['permissions'][] = 'config.write';
+                }
+            }
+            
+            $result = $this->configService->updateRolePermissions($input['role_id'], $input['permissions']);
+            echo json_encode(['success' => true, 'data' => $result]);
+            
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    // === AUDITORIA ===
+    public function getAuditLogs() {
+        $this->authService->requirePermission('audit.read');
+        
+        $filters = [
+            'user_id' => $_GET['user_id'] ?? null,
+            'entity' => $_GET['entity'] ?? null,
+            'action' => $_GET['action'] ?? null,
+            'date_start' => $_GET['date_start'] ?? null,
+            'date_end' => $_GET['date_end'] ?? null,
+            'page' => max(1, intval($_GET['page'] ?? 1)),
+            'pageSize' => min(100, max(10, intval($_GET['pageSize'] ?? 20)))
+        ];
+        
+        header('Content-Type: application/json');
+        
+        try {
+            $result = $this->configService->getAuditLogs($filters);
+            echo json_encode(['success' => true, 'data' => $result]);
+        } catch (Exception $e) {
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+    
+    public function exportAuditLogs() {
+        $this->authService->requirePermission('audit.export');
+        
+        try {
+            $filters = [
+                'user_id' => $_GET['user_id'] ?? null,
+                'entity' => $_GET['entity'] ?? null,
+                'action' => $_GET['action'] ?? null,
+                'date_start' => $_GET['date_start'] ?? null,
+                'date_end' => $_GET['date_end'] ?? null
+            ];
+            
+            $this->configService->exportAuditLogsCSV($filters);
+            
+        } catch (Exception $e) {
+            http_response_code(500);
+            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
+        }
+    }
+}
```

### 2.2 ConfigService.php

```diff
+<?php
+
+require_once __DIR__ . '/AuditService.php';
+
+class ConfigService {
+    private $db;
+    private $auditService;
+    
+    public function __construct() {
+        $this->db = new Database();
+        $this->auditService = new AuditService();
+    }
+    
+    public function getOrganizationSettings() {
+        $result = $this->db->fetch(
+            "SELECT * FROM organization_settings ORDER BY id LIMIT 1"
+        );
+        
+        if (!$result) {
+            // Retornar configuração padrão se não existir
+            return [
+                'company_name' => '',
+                'cnpj' => '',
+                'logo_url' => null,
+                'timezone' => 'America/Sao_Paulo',
+                'locale' => 'pt-BR'
+            ];
+        }
+        
+        return $result;
+    }
+    
+    public function updateOrganizationSettings($data) {
+        $current = $this->getOrganizationSettings();
+        
+        // Se não existe, criar
+        if (empty($current['company_name'])) {
+            $id = $this->db->query(
+                "INSERT INTO organization_settings (company_name, cnpj, logo_url, timezone, locale) 
+                 VALUES (?, ?, ?, ?, ?) RETURNING id",
+                [
+                    $data['company_name'],
+                    $data['cnpj'] ?? null,
+                    $data['logo_url'] ?? null,
+                    $data['timezone'] ?? 'America/Sao_Paulo',
+                    $data['locale'] ?? 'pt-BR'
+                ]
+            );
+            
+            $this->auditService->log(
+                'create',
+                'organization_settings',
+                $id,
+                null,
+                $data
+            );
+        } else {
+            // Atualizar existente
+            $this->db->query(
+                "UPDATE organization_settings SET 
+                 company_name = ?, cnpj = ?, logo_url = ?, timezone = ?, locale = ?, updated_at = CURRENT_TIMESTAMP
+                 WHERE id = (SELECT id FROM organization_settings ORDER BY id LIMIT 1)",
+                [
+                    $data['company_name'],
+                    $data['cnpj'] ?? $current['cnpj'],
+                    $data['logo_url'] ?? $current['logo_url'],
+                    $data['timezone'] ?? $current['timezone'],
+                    $data['locale'] ?? $current['locale']
+                ]
+            );
+            
+            $this->auditService->log(
+                'update',
+                'organization_settings',
+                1,
+                $current,
+                array_merge($current, $data)
+            );
+        }
+        
+        return $this->getOrganizationSettings();
+    }
+    
+    public function getSites() {
+        return $this->db->fetchAll(
+            "SELECT s.*, 
+                    COUNT(sec.id) as sectors_count,
+                    COALESCE(SUM(sec.capacity), 0) as total_capacity
+             FROM sites s 
+             LEFT JOIN sectors sec ON s.id = sec.site_id AND sec.active = TRUE
+             WHERE s.active = TRUE
+             GROUP BY s.id
+             ORDER BY s.name"
+        );
+    }
+    
+    public function createSite($data) {
+        $result = $this->db->fetch(
+            "INSERT INTO sites (name, capacity, address, timezone, active) 
+             VALUES (?, ?, ?, ?, ?) RETURNING id",
+            [
+                $data['name'],
+                $data['capacity'] ?? 0,
+                $data['address'] ?? null,
+                $data['timezone'] ?? 'America/Sao_Paulo',
+                $data['active'] ?? true
+            ]
+        );
+        
+        $siteId = $result['id'];
+        
+        $this->auditService->log(
+            'create',
+            'sites',
+            $siteId,
+            null,
+            $data
+        );
+        
+        return $siteId;
+    }
+    
+    public function getRbacMatrix() {
+        // Buscar todos os roles
+        $roles = $this->db->fetchAll(
+            "SELECT id, name, description, system_role FROM roles WHERE active = TRUE ORDER BY name"
+        );
+        
+        // Buscar todas as permissões
+        $permissions = $this->db->fetchAll(
+            "SELECT id, key, description, module FROM permissions ORDER BY module, key"
+        );
+        
+        // Buscar matriz atual
+        $matrix = [];
+        foreach ($roles as $role) {
+            $rolePermissions = $this->db->fetchAll(
+                "SELECT p.key FROM role_permissions rp 
+                 JOIN permissions p ON rp.permission_id = p.id 
+                 WHERE rp.role_id = ?",
+                [$role['id']]
+            );
+            
+            $matrix[$role['id']] = array_column($rolePermissions, 'key');
+        }
+        
+        return [
+            'roles' => $roles,
+            'permissions' => $permissions,
+            'matrix' => $matrix
+        ];
+    }
+    
+    public function updateRolePermissions($roleId, $permissions) {
+        // Buscar permissões atuais
+        $currentPermissions = $this->db->fetchAll(
+            "SELECT p.key FROM role_permissions rp 
+             JOIN permissions p ON rp.permission_id = p.id 
+             WHERE rp.role_id = ?",
+            [$roleId]
+        );
+        $current = array_column($currentPermissions, 'key');
+        
+        // Remover todas as permissões atuais
+        $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
+        
+        // Inserir novas permissões
+        foreach ($permissions as $permissionKey) {
+            $permission = $this->db->fetch(
+                "SELECT id FROM permissions WHERE key = ?",
+                [$permissionKey]
+            );
+            
+            if ($permission) {
+                $this->db->query(
+                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
+                    [$roleId, $permission['id']]
+                );
+            }
+        }
+        
+        // Log de auditoria
+        $this->auditService->log(
+            'update',
+            'role_permissions',
+            $roleId,
+            ['permissions' => $current],
+            ['permissions' => $permissions]
+        );
+        
+        return true;
+    }
+    
+    public function getAuditLogs($filters) {
+        $sql = "SELECT al.*, u.nome as usuario_nome 
+                FROM audit_log al 
+                LEFT JOIN usuarios u ON al.user_id = u.id 
+                WHERE 1=1";
+        $params = [];
+        
+        if (!empty($filters['user_id'])) {
+            $sql .= " AND al.user_id = ?";
+            $params[] = $filters['user_id'];
+        }
+        
+        if (!empty($filters['entity'])) {
+            $sql .= " AND al.entidade = ?";
+            $params[] = $filters['entity'];
+        }
+        
+        if (!empty($filters['action'])) {
+            $sql .= " AND al.acao = ?";
+            $params[] = $filters['action'];
+        }
+        
+        if (!empty($filters['date_start'])) {
+            $sql .= " AND DATE(al.timestamp) >= ?";
+            $params[] = $filters['date_start'];
+        }
+        
+        if (!empty($filters['date_end'])) {
+            $sql .= " AND DATE(al.timestamp) <= ?";
+            $params[] = $filters['date_end'];
+        }
+        
+        // Contar total
+        $countSql = str_replace("SELECT al.*, u.nome as usuario_nome", "SELECT COUNT(*)", $sql);
+        $total = $this->db->fetch($countSql, $params)['count'];
+        
+        // Paginação
+        $offset = ($filters['page'] - 1) * $filters['pageSize'];
+        $sql .= " ORDER BY al.timestamp DESC LIMIT ? OFFSET ?";
+        $params[] = $filters['pageSize'];
+        $params[] = $offset;
+        
+        $logs = $this->db->fetchAll($sql, $params);
+        
+        return [
+            'logs' => $logs,
+            'pagination' => [
+                'current' => $filters['page'],
+                'total' => ceil($total / $filters['pageSize']),
+                'totalItems' => $total,
+                'pageSize' => $filters['pageSize']
+            ]
+        ];
+    }
+    
+    public function exportAuditLogsCSV($filters) {
+        header('Content-Type: text/csv; charset=UTF-8');
+        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');
+        header('Pragma: no-cache');
+        header('Expires: 0');
+        
+        $output = fopen('php://output', 'w');
+        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
+        
+        // Cabeçalhos
+        fputcsv($output, [
+            'ID', 'Data/Hora', 'Usuário', 'Ação', 'Entidade', 'ID Entidade',
+            'Dados Antes', 'Dados Depois', 'IP', 'User Agent'
+        ]);
+        
+        // Buscar logs sem paginação
+        $sql = "SELECT al.*, u.nome as usuario_nome 
+                FROM audit_log al 
+                LEFT JOIN usuarios u ON al.user_id = u.id 
+                WHERE 1=1";
+        $params = [];
+        
+        // Aplicar mesmos filtros
+        if (!empty($filters['user_id'])) {
+            $sql .= " AND al.user_id = ?";
+            $params[] = $filters['user_id'];
+        }
+        
+        if (!empty($filters['entity'])) {
+            $sql .= " AND al.entidade = ?";
+            $params[] = $filters['entity'];
+        }
+        
+        if (!empty($filters['action'])) {
+            $sql .= " AND al.acao = ?";
+            $params[] = $filters['action'];
+        }
+        
+        if (!empty($filters['date_start'])) {
+            $sql .= " AND DATE(al.timestamp) >= ?";
+            $params[] = $filters['date_start'];
+        }
+        
+        if (!empty($filters['date_end'])) {
+            $sql .= " AND DATE(al.timestamp) <= ?";
+            $params[] = $filters['date_end'];
+        }
+        
+        $sql .= " ORDER BY al.timestamp DESC";
+        
+        $logs = $this->db->fetchAll($sql, $params);
+        
+        foreach ($logs as $log) {
+            fputcsv($output, [
+                $log['id'],
+                date('d/m/Y H:i:s', strtotime($log['timestamp'])),
+                $log['usuario_nome'] ?? 'Sistema',
+                $log['acao'],
+                $log['entidade'],
+                $log['entidade_id'],
+                $log['dados_antes'] ? json_encode(json_decode($log['dados_antes']), JSON_UNESCAPED_UNICODE) : '',
+                $log['dados_depois'] ? json_encode(json_decode($log['dados_depois']), JSON_UNESCAPED_UNICODE) : '',
+                $log['ip_address'],
+                $log['user_agent']
+            ]);
+        }
+        
+        fclose($output);
+        exit;
+    }
+}
```

### 2.3 CnpjValidator.php

```diff
+<?php
+
+class CnpjValidator {
+    public static function isValid($cnpj) {
+        // Remove formatação
+        $cnpj = preg_replace('/\D/', '', $cnpj);
+        
+        // Verifica se tem 14 dígitos
+        if (strlen($cnpj) !== 14) {
+            return false;
+        }
+        
+        // Verifica se não são todos iguais
+        if (preg_match('/(\d)\1{13}/', $cnpj)) {
+            return false;
+        }
+        
+        // Calcula dígitos verificadores
+        $soma = 0;
+        $multiplicador = [5,4,3,2,9,8,7,6,5,4,3,2];
+        
+        for ($i = 0; $i < 12; $i++) {
+            $soma += $cnpj[$i] * $multiplicador[$i];
+        }
+        
+        $resto = $soma % 11;
+        $dv1 = $resto < 2 ? 0 : 11 - $resto;
+        
+        if ($cnpj[12] != $dv1) {
+            return false;
+        }
+        
+        $soma = 0;
+        $multiplicador = [6,5,4,3,2,9,8,7,6,5,4,3,2];
+        
+        for ($i = 0; $i < 13; $i++) {
+            $soma += $cnpj[$i] * $multiplicador[$i];
+        }
+        
+        $resto = $soma % 11;
+        $dv2 = $resto < 2 ? 0 : 11 - $resto;
+        
+        return $cnpj[13] == $dv2;
+    }
+    
+    public static function format($cnpj) {
+        $cnpj = preg_replace('/\D/', '', $cnpj);
+        if (strlen($cnpj) !== 14) {
+            return $cnpj;
+        }
+        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
+    }
+}
```

### 2.4 Atualização ReportController.php (Exportação com RBAC)

```diff
 public function exportFuncionarios() {
+    $this->authService->requirePermission('report.export');
+    
     $setor = $_GET['setor'] ?? '';
     $status = $_GET['status'] ?? '';
     $site_id = $_GET['site_id'] ?? '';
     $search = $_GET['search'] ?? '';
+    
+    // Verificar se pode ver CPF não mascarado
+    $canViewFullCpf = $this->authService->hasPermission('person.cpf.view_unmasked');
     
     header('Content-Type: text/csv; charset=UTF-8');
     header('Content-Disposition: attachment; filename="funcionarios_' . date('Y-m-d') . '.csv"');
     header('Pragma: no-cache');
     header('Expires: 0');
     
     $output = fopen('php://output', 'w');
     fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
     
     // Cabeçalhos
     fputcsv($output, [
         'ID', 'Nome', 'CPF', 'Cargo', 'Setor', 'Site', 'Email', 
         'Telefone', 'Status', 'Data Admissão', 'Criado em'
     ]);
     
     // Query com filtros
-    $sql = "SELECT f.*, s.name as site_name FROM funcionarios f LEFT JOIN sites s ON f.site_id = s.id WHERE 1=1";
+    $sql = "SELECT f.*, 'Principal' as site_name FROM funcionarios f WHERE 1=1"; // Ajustar quando sites estiver implementado
     $params = [];
     
     if (!empty($search)) {
         $sql .= " AND (f.nome ILIKE ? OR f.cpf ILIKE ?)";
         $params[] = "%$search%";
         $params[] = "%$search%";
     }
     
     if (!empty($status)) {
         $sql .= " AND f.ativo = ?";
         $params[] = $status === 'ativo';
     }
     
     $sql .= " ORDER BY f.nome ASC";
     
     $funcionarios = $this->db->fetchAll($sql, $params);
     
     foreach ($funcionarios as $funcionario) {
+        // Mascarar CPF se necessário
+        $cpf = $funcionario['cpf'];
+        if (!$canViewFullCpf && !empty($cpf)) {
+            $cpf = $this->maskCpf($cpf);
+        }
+        
         fputcsv($output, [
             $funcionario['id'],
             $funcionario['nome'],
-            $funcionario['cpf'],
+            $cpf,
             $funcionario['cargo'],
             '', // setor - implementar quando houver
             $funcionario['site_name'] ?? 'Principal',
             $funcionario['email'],
             $funcionario['telefone'],
             $funcionario['ativo'] ? 'Ativo' : 'Inativo',
             $funcionario['data_admissao'] ? date('d/m/Y', strtotime($funcionario['data_admissao'])) : '',
             date('d/m/Y H:i', strtotime($funcionario['data_criacao']))
         ]);
     }
     
     fclose($output);
     exit;
 }
+ 
+ private function maskCpf($cpf) {
+     if (empty($cpf)) return '';
+     $cpf = preg_replace('/\D/', '', $cpf);
+     if (strlen($cpf) !== 11) return $cpf;
+     return '***.***.***-' . substr($cpf, -2);
+ }
```

---

## 3. FRONT-END (Views PHP)

### 3.1 views/config/index.php

```diff
+<!DOCTYPE html>
+<html lang="pt-BR">
+<head>
+    <meta charset="UTF-8">
+    <meta name="viewport" content="width=device-width, initial-scale=1.0">
+    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
+    <title>Configurações - Sistema de Controle de Acesso</title>
+    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
+    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
+    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
+    <style>
+        .config-nav {
+            border-right: 1px solid #dee2e6;
+            min-height: calc(100vh - 200px);
+        }
+        .config-nav .nav-link {
+            color: #495057;
+            border-radius: 0;
+            border-bottom: 1px solid #dee2e6;
+        }
+        .config-nav .nav-link.active {
+            background-color: #007bff;
+            color: white;
+        }
+        .danger-zone {
+            border: 2px solid #dc3545;
+            border-radius: 8px;
+            background-color: #f8f9fa;
+        }
+        .permission-matrix {
+            max-height: 400px;
+            overflow-y: auto;
+        }
+        .auto-save-indicator {
+            position: fixed;
+            top: 20px;
+            right: 20px;
+            z-index: 1050;
+        }
+    </style>
+</head>
+<body class="hold-transition sidebar-mini layout-fixed">
+    <div class="wrapper">
+        <!-- Navbar -->
+        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
+            <ul class="navbar-nav">
+                <li class="nav-item">
+                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
+                        <i class="fas fa-bars"></i>
+                    </a>
+                </li>
+            </ul>
+            <ul class="navbar-nav ml-auto">
+                <li class="nav-item">
+                    <span class="navbar-text">
+                        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?>
+                    </span>
+                </li>
+                <li class="nav-item">
+                    <a class="nav-link" href="/logout">
+                        <i class="fas fa-sign-out-alt"></i> Sair
+                    </a>
+                </li>
+            </ul>
+        </nav>
+
+        <!-- Sidebar -->
+        <aside class="main-sidebar sidebar-dark-primary elevation-4">
+            <a href="/dashboard" class="brand-link">
+                <img src="/logo.jpg" alt="Logo" class="brand-image img-circle elevation-3" style="width: 40px; height: 40px; object-fit: contain;">
+                <span class="brand-text font-weight-light">Controle Acesso</span>
+            </a>
+            
+            <div class="sidebar">
+                <nav class="mt-2">
+                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
+                        <li class="nav-item">
+                            <a href="/dashboard" class="nav-link">
+                                <i class="nav-icon fas fa-tachometer-alt"></i>
+                                <p>Dashboard</p>
+                            </a>
+                        </li>
+                        <li class="nav-item">
+                            <a href="/config" class="nav-link active">
+                                <i class="nav-icon fas fa-cogs"></i>
+                                <p>Configurações</p>
+                            </a>
+                        </li>
+                    </ul>
+                </nav>
+            </div>
+        </aside>

+        <!-- Content Wrapper -->
+        <div class="content-wrapper">
+            <div class="content-header">
+                <div class="container-fluid">
+                    <div class="row mb-2">
+                        <div class="col-sm-6">
+                            <h1 class="m-0">Configurações do Sistema</h1>
+                        </div>
+                        <div class="col-sm-6">
+                            <ol class="breadcrumb float-sm-right">
+                                <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
+                                <li class="breadcrumb-item active">Configurações</li>
+                            </ol>
+                        </div>
+                    </div>
+                </div>
+            </div>

+            <!-- Main content -->
+            <section class="content">
+                <div class="container-fluid">
+                    <div class="row">
+                        <!-- Navigation Menu -->
+                        <div class="col-md-3">
+                            <div class="card">
+                                <div class="card-header">
+                                    <h3 class="card-title">Menu de Configurações</h3>
+                                </div>
+                                <div class="card-body p-0">
+                                    <nav class="nav nav-pills flex-column config-nav">
+                                        <a class="nav-link active" data-section="organization" href="#">
+                                            <i class="fas fa-building mr-2"></i> Organização & Locais
+                                        </a>
+                                        <a class="nav-link" data-section="rbac" href="#">
+                                            <i class="fas fa-users-cog mr-2"></i> Pessoas & Perfis
+                                        </a>
+                                        <a class="nav-link" data-section="auth-policies" href="#">
+                                            <i class="fas fa-shield-alt mr-2"></i> Políticas de Login
+                                        </a>
+                                        <a class="nav-link" data-section="audit" href="#">
+                                            <i class="fas fa-history mr-2"></i> Auditoria
+                                        </a>
+                                        <a class="nav-link" data-section="export" href="#">
+                                            <i class="fas fa-download mr-2"></i> Exportações
+                                        </a>
+                                    </nav>
+                                </div>
+                            </div>
+                        </div>

+                        <!-- Content Area -->
+                        <div class="col-md-9">
+                            <!-- Organização & Locais -->
+                            <div id="section-organization" class="config-section">
+                                <div class="card">
+                                    <div class="card-header">
+                                        <ul class="nav nav-tabs card-header-tabs">
+                                            <li class="nav-item">
+                                                <a class="nav-link active" data-tab="organization-info" href="#">Organização</a>
+                                            </li>
+                                            <li class="nav-item">
+                                                <a class="nav-link" data-tab="sites" href="#">Locais/Sites</a>
+                                            </li>
+                                            <li class="nav-item">
+                                                <a class="nav-link" data-tab="sectors" href="#">Zonas/Setores</a>
+                                            </li>
+                                            <li class="nav-item">
+                                                <a class="nav-link" data-tab="business-hours" href="#">Horários</a>
+                                            </li>
+                                            <li class="nav-item">
+                                                <a class="nav-link" data-tab="holidays" href="#">Feriados</a>
+                                            </li>
+                                        </ul>
+                                    </div>
+                                    <div class="card-body">
+                                        <!-- Tab: Organização -->
+                                        <div id="tab-organization-info" class="tab-content active">
+                                            <form id="form-organization">
+                                                <div class="row">
+                                                    <div class="col-md-6">
+                                                        <div class="form-group">
+                                                            <label for="company_name">Nome da Empresa *</label>
+                                                            <input type="text" class="form-control" id="company_name" name="company_name" required>
+                                                        </div>
+                                                    </div>
+                                                    <div class="col-md-6">
+                                                        <div class="form-group">
+                                                            <label for="cnpj">CNPJ</label>
+                                                            <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00">
+                                                        </div>
+                                                    </div>
+                                                </div>
+                                                <div class="row">
+                                                    <div class="col-md-4">
+                                                        <div class="form-group">
+                                                            <label for="timezone">Fuso Horário</label>
+                                                            <select class="form-control" id="timezone" name="timezone">
+                                                                <option value="America/Sao_Paulo">São Paulo (UTC-3)</option>
+                                                                <option value="America/Manaus">Manaus (UTC-4)</option>
+                                                                <option value="America/Rio_Branco">Rio Branco (UTC-5)</option>
+                                                            </select>
+                                                        </div>
+                                                    </div>
+                                                    <div class="col-md-4">
+                                                        <div class="form-group">
+                                                            <label for="locale">Idioma</label>
+                                                            <select class="form-control" id="locale" name="locale">
+                                                                <option value="pt-BR">Português (Brasil)</option>
+                                                                <option value="en-US">English (US)</option>
+                                                            </select>
+                                                        </div>
+                                                    </div>
+                                                    <div class="col-md-4">
+                                                        <div class="form-group">
+                                                            <label for="logo_url">URL do Logo</label>
+                                                            <input type="url" class="form-control" id="logo_url" name="logo_url" placeholder="https://...">
+                                                        </div>
+                                                    </div>
+                                                </div>
+                                                <button type="submit" class="btn btn-primary">
+                                                    <i class="fas fa-save"></i> Salvar Configurações
+                                                </button>
+                                            </form>
+                                        </div>

+                                        <!-- Tab: Sites -->
+                                        <div id="tab-sites" class="tab-content">
+                                            <div class="d-flex justify-content-between mb-3">
+                                                <h5>Locais/Sites</h5>
+                                                <button class="btn btn-success btn-sm" id="btn-add-site">
+                                                    <i class="fas fa-plus"></i> Adicionar Local
+                                                </button>
+                                            </div>
+                                            <div class="table-responsive">
+                                                <table class="table table-bordered table-hover" id="table-sites">
+                                                    <thead class="table-dark">
+                                                        <tr>
+                                                            <th>Nome</th>
+                                                            <th>Capacidade</th>
+                                                            <th>Setores</th>
+                                                            <th>Status</th>
+                                                            <th>Ações</th>
+                                                        </tr>
+                                                    </thead>
+                                                    <tbody>
+                                                        <!-- Populated by JavaScript -->
+                                                    </tbody>
+                                                </table>
+                                            </div>
+                                        </div>

+                                        <!-- Outros tabs aqui... -->
+                                    </div>
+                                </div>
+                            </div>

+                            <!-- RBAC Section -->
+                            <div id="section-rbac" class="config-section" style="display: none;">
+                                <div class="card">
+                                    <div class="card-header">
+                                        <h3 class="card-title">
+                                            <i class="fas fa-users-cog"></i> Pessoas & Perfis (RBAC)
+                                        </h3>
+                                    </div>
+                                    <div class="card-body">
+                                        <div class="row mb-3">
+                                            <div class="col-md-6">
+                                                <input type="text" class="form-control" id="search-users" placeholder="Buscar por usuário ou perfil...">
+                                            </div>
+                                        </div>
+                                        
+                                        <div class="permission-matrix">
+                                            <h6>Matriz de Permissões</h6>
+                                            <div class="table-responsive">
+                                                <table class="table table-bordered table-sm">
+                                                    <thead class="table-dark">
+                                                        <tr>
+                                                            <th>Permissão</th>
+                                                            <th>Admin</th>
+                                                            <th>Segurança</th>
+                                                            <th>Recepção</th>
+                                                            <th>RH</th>
+                                                            <th>Porteiro</th>
+                                                        </tr>
+                                                    </thead>
+                                                    <tbody id="permissions-matrix">
+                                                        <!-- Populated by JavaScript -->
+                                                    </tbody>
+                                                </table>
+                                            </div>
+                                        </div>
+                                        
+                                        <button class="btn btn-primary mt-3" id="btn-save-permissions">
+                                            <i class="fas fa-save"></i> Salvar Permissões
+                                        </button>
+                                    </div>
+                                </div>
+                            </div>

+                            <!-- Export Section -->
+                            <div id="section-export" class="config-section" style="display: none;">
+                                <div class="card">
+                                    <div class="card-header">
+                                        <h3 class="card-title">
+                                            <i class="fas fa-download"></i> Exportações
+                                        </h3>
+                                    </div>
+                                    <div class="card-body">
+                                        <div class="row">
+                                            <div class="col-md-6">
+                                                <div class="card">
+                                                    <div class="card-header">
+                                                        <h5>Exportar Funcionários</h5>
+                                                    </div>
+                                                    <div class="card-body">
+                                                        <p class="text-muted">Baixa planilha com dados dos funcionários respeitando suas permissões de visualização.</p>
+                                                        
+                                                        <form id="form-export-funcionarios">
+                                                            <div class="form-group">
+                                                                <label>Status:</label>
+                                                                <select name="status" class="form-control">
+                                                                    <option value="">Todos</option>
+                                                                    <option value="ativo">Ativos</option>
+                                                                    <option value="inativo">Inativos</option>
+                                                                </select>
+                                                            </div>
+                                                            
+                                                            <button type="submit" class="btn btn-success w-100">
+                                                                <i class="fas fa-file-csv"></i> Exportar CSV
+                                                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
+                                                            </button>
+                                                        </form>
+                                                    </div>
+                                                </div>
+                                            </div>
+                                        </div>
+                                    </div>
+                                </div>
+                            </div>
+                        </div>
+                    </div>
+                </div>
+            </section>
+        </div>
+    </div>

+    <!-- Auto-save indicator -->
+    <div id="auto-save-indicator" class="auto-save-indicator alert alert-success d-none" role="alert">
+        <i class="fas fa-check"></i> Configurações salvas automaticamente
+    </div>

+    <!-- Scripts -->
+    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
+    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
+    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
+    
+    <script>
+        class ConfigManager {
+            constructor() {
+                this.init();
+                this.bindEvents();
+                this.loadInitialData();
+            }
+
+            init() {
+                this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
+                this.autoSaveTimeout = null;
+            }

+            bindEvents() {
+                // Section navigation
+                document.querySelectorAll('.config-nav .nav-link').forEach(link => {
+                    link.addEventListener('click', (e) => {
+                        e.preventDefault();
+                        this.showSection(link.dataset.section);
+                        this.setActiveNav(link);
+                    });
+                });

+                // Tab navigation
+                document.querySelectorAll('[data-tab]').forEach(tab => {
+                    tab.addEventListener('click', (e) => {
+                        e.preventDefault();
+                        this.showTab(tab.dataset.tab);
+                    });
+                });

+                // Forms
+                document.getElementById('form-organization').addEventListener('submit', (e) => {
+                    e.preventDefault();
+                    this.saveOrganization();
+                });

+                document.getElementById('form-export-funcionarios').addEventListener('submit', (e) => {
+                    e.preventDefault();
+                    this.exportFuncionarios();
+                });

+                // Auto-save for organization
+                document.querySelectorAll('#form-organization input, #form-organization select').forEach(input => {
+                    input.addEventListener('input', () => {
+                        this.scheduleAutoSave();
+                    });
+                });
+            }

+            showSection(sectionId) {
+                document.querySelectorAll('.config-section').forEach(section => {
+                    section.style.display = 'none';
+                });
+                document.getElementById(`section-${sectionId}`).style.display = 'block';
+            }

+            setActiveNav(activeLink) {
+                document.querySelectorAll('.config-nav .nav-link').forEach(link => {
+                    link.classList.remove('active');
+                });
+                activeLink.classList.add('active');
+            }

+            showTab(tabId) {
+                document.querySelectorAll('.tab-content').forEach(tab => {
+                    tab.classList.remove('active');
+                });
+                document.getElementById(`tab-${tabId}`).classList.add('active');
+            }

+            async loadInitialData() {
+                try {
+                    await this.loadOrganizationData();
+                    await this.loadRbacMatrix();
+                } catch (error) {
+                    console.error('Erro ao carregar dados:', error);
+                }
+            }

+            async loadOrganizationData() {
+                const response = await fetch('/config/organization', {
+                    headers: {
+                        'X-CSRF-TOKEN': this.csrfToken
+                    }
+                });
+                
+                if (response.ok) {
+                    const data = await response.json();
+                    if (data.success) {
+                        this.populateOrganizationForm(data.data);
+                    }
+                }
+            }

+            populateOrganizationForm(data) {
+                Object.keys(data).forEach(key => {
+                    const input = document.getElementById(key);
+                    if (input) {
+                        input.value = data[key] || '';
+                    }
+                });
+            }

+            scheduleAutoSave() {
+                if (this.autoSaveTimeout) {
+                    clearTimeout(this.autoSaveTimeout);
+                }
+                
+                this.autoSaveTimeout = setTimeout(() => {
+                    this.saveOrganization(true);
+                }, 2000);
+            }

+            async saveOrganization(isAutoSave = false) {
+                const form = document.getElementById('form-organization');
+                const formData = new FormData(form);
+                const data = Object.fromEntries(formData);

+                try {
+                    const response = await fetch('/config/organization', {
+                        method: 'PUT',
+                        headers: {
+                            'Content-Type': 'application/json',
+                            'X-CSRF-TOKEN': this.csrfToken
+                        },
+                        body: JSON.stringify(data)
+                    });

+                    const result = await response.json();
+                    
+                    if (result.success) {
+                        if (isAutoSave) {
+                            this.showAutoSaveIndicator();
+                        } else {
+                            this.showAlert('Configurações salvas com sucesso!', 'success');
+                        }
+                    } else {
+                        this.showAlert(result.message, 'error');
+                    }
+                } catch (error) {
+                    this.showAlert('Erro ao salvar configurações', 'error');
+                }
+            }

+            async loadRbacMatrix() {
+                const response = await fetch('/config/rbac-matrix', {
+                    headers: {
+                        'X-CSRF-TOKEN': this.csrfToken
+                    }
+                });
+                
+                if (response.ok) {
+                    const data = await response.json();
+                    if (data.success) {
+                        this.populateRbacMatrix(data.data);
+                    }
+                }
+            }

+            populateRbacMatrix(data) {
+                const tbody = document.getElementById('permissions-matrix');
+                tbody.innerHTML = '';

+                data.permissions.forEach(permission => {
+                    const row = document.createElement('tr');
+                    
+                    let html = `<td><strong>${permission.key}</strong><br><small class="text-muted">${permission.description || ''}</small></td>`;
+                    
+                    data.roles.forEach(role => {
+                        const hasPermission = data.matrix[role.id]?.includes(permission.key);
+                        const disabled = role.name === 'administrador' && permission.key === 'config.write' ? 'disabled' : '';
+                        
+                        html += `
+                            <td class="text-center">
+                                <input type="checkbox" 
+                                       class="form-check-input" 
+                                       data-role="${role.id}" 
+                                       data-permission="${permission.key}"
+                                       ${hasPermission ? 'checked' : ''} 
+                                       ${disabled}>
+                            </td>
+                        `;
+                    });
+                    
+                    row.innerHTML = html;
+                    tbody.appendChild(row);
+                });
+            }

+            async exportFuncionarios() {
+                const form = document.getElementById('form-export-funcionarios');
+                const submitBtn = form.querySelector('button[type="submit"]');
+                const spinner = submitBtn.querySelector('.spinner-border');
+                
+                // Show loading
+                spinner.classList.remove('d-none');
+                submitBtn.disabled = true;
+                
+                try {
+                    const formData = new FormData(form);
+                    const params = new URLSearchParams(formData);
+                    
+                    const response = await fetch(`/export/funcionarios?${params}`, {
+                        headers: {
+                            'X-CSRF-TOKEN': this.csrfToken
+                        }
+                    });
+                    
+                    if (response.ok) {
+                        // Download do arquivo
+                        const blob = await response.blob();
+                        const url = window.URL.createObjectURL(blob);
+                        const a = document.createElement('a');
+                        a.style.display = 'none';
+                        a.href = url;
+                        a.download = `funcionarios_${new Date().toISOString().split('T')[0]}.csv`;
+                        document.body.appendChild(a);
+                        a.click();
+                        window.URL.revokeObjectURL(url);
+                        document.body.removeChild(a);
+                        
+                        this.showAlert('Exportação realizada com sucesso!', 'success');
+                    } else {
+                        this.showAlert('Erro ao exportar dados', 'error');
+                    }
+                } catch (error) {
+                    this.showAlert('Erro ao processar exportação', 'error');
+                } finally {
+                    // Hide loading
+                    spinner.classList.add('d-none');
+                    submitBtn.disabled = false;
+                }
+            }

+            showAutoSaveIndicator() {
+                const indicator = document.getElementById('auto-save-indicator');
+                indicator.classList.remove('d-none');
+                setTimeout(() => {
+                    indicator.classList.add('d-none');
+                }, 3000);
+            }

+            showAlert(message, type) {
+                // Implementar sistema de alertas
+                console.log(`${type}: ${message}`);
+            }
+        }

+        // Initialize when DOM is ready
+        document.addEventListener('DOMContentLoaded', () => {
+            new ConfigManager();
+        });
+    </script>
+</body>
+</html>
```

### 3.2 Atualização public/index.php (Rotas)

```diff
 // Existing routes...

+// === CONFIGURAÇÕES ===
+if (preg_match('/^\/config$/', $path)) {
+    $controller = new ConfigController();
+    $controller->index();
+    exit;
+}

+if (preg_match('/^\/config\/organization$/', $path)) {
+    $controller = new ConfigController();
+    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
+        $controller->getOrganization();
+    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
+        $controller->updateOrganization();
+    }
+    exit;
+}

+if (preg_match('/^\/config\/sites$/', $path)) {
+    $controller = new ConfigController();
+    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
+        $controller->getSites();
+    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
+        $controller->createSite();
+    }
+    exit;
+}

+if (preg_match('/^\/config\/rbac-matrix$/', $path)) {
+    $controller = new ConfigController();
+    $controller->getRbacMatrix();
+    exit;
+}

+if (preg_match('/^\/config\/audit$/', $path)) {
+    $controller = new ConfigController();
+    $controller->getAuditLogs();
+    exit;
+}

+if (preg_match('/^\/export\/funcionarios$/', $path)) {
+    $controller = new ReportController();
+    $controller->exportFuncionarios();
+    exit;
+}
```

---

## 4. TESTES & QA

### 4.1 Testes Unitários (PHP)

```diff
+<?php
+// tests/unit/CnpjValidatorTest.php
+
+require_once __DIR__ . '/../../src/utils/CnpjValidator.php';
+
+class CnpjValidatorTest extends PHPUnit\Framework\TestCase {
+    
+    public function testValidCnpj() {
+        $this->assertTrue(CnpjValidator::isValid('11.222.333/0001-81'));
+        $this->assertTrue(CnpjValidator::isValid('11222333000181'));
+    }
+    
+    public function testInvalidCnpj() {
+        $this->assertFalse(CnpjValidator::isValid('11.222.333/0001-82'));
+        $this->assertFalse(CnpjValidator::isValid('11111111111111'));
+        $this->assertFalse(CnpjValidator::isValid('123456789'));
+    }
+    
+    public function testCnpjFormat() {
+        $this->assertEquals('11.222.333/0001-81', CnpjValidator::format('11222333000181'));
+    }
+}

+// tests/unit/AuthorizationServiceTest.php
+
+require_once __DIR__ . '/../../src/services/AuthorizationService.php';

+class AuthorizationServiceTest extends PHPUnit\Framework\TestCase {
+    
+    private $authService;
+    
+    protected function setUp(): void {
+        $this->authService = new AuthorizationService();
+        $_SESSION = []; // Reset session
+    }
+    
+    public function testAdminHasAllPermissions() {
+        $_SESSION['user_profile'] = 'administrador';
+        
+        $this->assertTrue($this->authService->hasPermission('config.write'));
+        $this->assertTrue($this->authService->hasPermission('audit.read'));
+        $this->assertTrue($this->authService->hasPermission('usuarios.manage'));
+    }
+    
+    public function testPorteiroLimitedPermissions() {
+        $_SESSION['user_profile'] = 'porteiro';
+        
+        $this->assertTrue($this->authService->hasPermission('registro_acesso.create'));
+        $this->assertFalse($this->authService->hasPermission('config.write'));
+        $this->assertFalse($this->authService->hasPermission('audit.read'));
+    }
+    
+    public function testFilterEditableData() {
+        $_SESSION['user_profile'] = 'recepcao';
+        
+        $input = [
+            'nome' => 'João',
+            'empresa' => 'ACME',
+            'entrada_at' => '2024-01-01 10:00:00',
+            'sensitive_field' => 'secret'
+        ];
+        
+        $filtered = $this->authService->filterEditableData($input);
+        
+        $this->assertArrayHasKey('nome', $filtered);
+        $this->assertArrayHasKey('empresa', $filtered);
+        $this->assertArrayNotHasKey('entrada_at', $filtered);
+        $this->assertArrayNotHasKey('sensitive_field', $filtered);
+    }
+}
```

### 4.2 Testes de Integração

```diff
+<?php
+// tests/integration/ConfigControllerTest.php

+class ConfigControllerTest extends PHPUnit\Framework\TestCase {
+    
+    private $db;
+    
+    protected function setUp(): void {
+        $this->db = new Database();
+        
+        // Setup admin session
+        $_SESSION = [
+            'user_id' => 1,
+            'user_profile' => 'administrador'
+        ];
+    }
+    
+    public function testUpdateOrganizationSettings() {
+        $controller = new ConfigController();
+        
+        $_SERVER['REQUEST_METHOD'] = 'PUT';
+        
+        // Mock input
+        $input = [
+            'company_name' => 'Empresa Teste LTDA',
+            'cnpj' => '11.222.333/0001-81',
+            'timezone' => 'America/Sao_Paulo'
+        ];
+        
+        // Mock file_get_contents
+        $GLOBALS['mock_input'] = json_encode($input);
+        
+        ob_start();
+        $controller->updateOrganization();
+        $output = ob_get_clean();
+        
+        $response = json_decode($output, true);
+        
+        $this->assertTrue($response['success']);
+        $this->assertEquals('Empresa Teste LTDA', $response['data']['company_name']);
+    }
+    
+    public function testRbacPermissionDenied() {
+        // Set user as porteiro
+        $_SESSION['user_profile'] = 'porteiro';
+        
+        $controller = new ConfigController();
+        
+        $this->expectException(Exception::class);
+        $controller->getOrganization();
+    }
+}
```

### 4.3 Critérios de Aceite

#### ✅ Checklist de Funcionalidades

**Organização:**
- [ ] Salvar nome da empresa (mín. 2 caracteres)
- [ ] Validar e salvar CNPJ com dígito verificador
- [ ] Upload/configuração de logo
- [ ] Configurar fuso horário (padrão America/Sao_Paulo)
- [ ] Configurar idioma (padrão pt-BR)

**Locais/Sites & Setores:**
- [ ] CRUD completo de sites/locais
- [ ] Capacidade ≥ 0 validada
- [ ] CRUD de setores com FK para sites
- [ ] Horários de funcionamento por dia da semana
- [ ] Exceções de horário para datas específicas
- [ ] Feriados globais e por site

**RBAC:**
- [ ] Roles padrão: Admin, Segurança, Recepção, RH, Porteiro
- [ ] Matriz de permissões funcional com checkboxes
- [ ] Admin nunca perde config.write
- [ ] Ver CPF não mascarado só com person.cpf.view_unmasked
- [ ] Recepção não pode alterar configurações

**Políticas de Autenticação:**
- [ ] Configurar senha mínima (padrão 8 caracteres)
- [ ] Configurar expiração de senha (padrão 90 dias)
- [ ] Configurar tempo de sessão (padrão 1440 min)
- [ ] Toggle 2FA (apenas configuração, sem implementação)
- [ ] Toggle SSO (apenas configuração, sem implementação)

**Auditoria:**
- [ ] Gravar antes/depois, IP, timestamp, user agent
- [ ] Filtros por usuário, entidade, ação, datas
- [ ] Paginação funcional
- [ ] Exportação de trilhas em CSV

**Exportar Funcionários:**
- [ ] Botão baixa planilha CSV
- [ ] Respeita permissão report.export
- [ ] CPF mascarado para quem não tem person.cpf.view_unmasked
- [ ] CPF completo para quem tem permissão
- [ ] Filtros por status, setor (quando implementado)

**Higiene de Código:**
- [ ] Sair/voltar entre abas sem vazamento de estado
- [ ] Requests podem ser cancelados (via navegador)
- [ ] Auto-save em toggles simples
- [ ] Botão Salvar para formulários grandes
- [ ] Mensagens de sucesso/erro claras em pt-BR

---

## 5. RESUMO DA IMPLEMENTAÇÃO

### Arquivos Criados:
- `src/controllers/ConfigController.php`
- `src/services/ConfigService.php`
- `src/utils/CnpjValidator.php`
- `views/config/index.php`
- Migrações SQL para todas as tabelas

### Arquivos Modificados:
- `public/index.php` (rotas)
- `src/controllers/ReportController.php` (RBAC na exportação)
- `src/services/AuthorizationService.php` (adaptação para nova estrutura)

### Endpoints Criados:
- `GET /config` - Interface principal
- `GET/PUT /config/organization` - Configurações organizacionais
- `GET/POST /config/sites` - Gerenciar locais
- `GET /config/rbac-matrix` - Matriz de permissões
- `GET /config/audit` - Logs de auditoria
- `GET /export/funcionarios` - Exportar funcionários com RBAC

### Performance:
- Índices estratégicos em todas as tabelas
- Paginação em auditoria e relatórios
- Streaming CSV para grandes exportações
- Queries otimizadas com JOINs eficientes

---

**Posso executar as alterações de Configurações? Responda executa para prosseguir ou não para cancelar.**