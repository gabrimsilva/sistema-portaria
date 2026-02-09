<?php
/**
 * Script de Migração do Banco de Dados
 * Sistema de Controle de Acesso v2.0
 * 
 * Uso: php scripts/migrate.php
 */

echo "===========================================\n";
echo "  Sistema de Controle de Acesso v2.0\n";
echo "  Script de Migração de Banco de Dados\n";
echo "===========================================\n\n";

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    $localConfigFile = __DIR__ . '/../config/database.local.php';
    if (file_exists($localConfigFile)) {
        $config = require $localConfigFile;
        $databaseUrl = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            $config['host'],
            $config['port'],
            $config['database']
        );
        $username = $config['username'];
        $password = $config['password'];
    } else {
        die("ERRO: Configure DATABASE_URL ou crie config/database.local.php\n");
    }
} else {
    $parsed = parse_url($databaseUrl);
    $databaseUrl = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $parsed['host'],
        $parsed['port'] ?? 5432,
        ltrim($parsed['path'], '/')
    );
    $username = $parsed['user'];
    $password = $parsed['pass'];
}

try {
    $pdo = new PDO($databaseUrl, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "[OK] Conexão com banco de dados estabelecida\n\n";
} catch (PDOException $e) {
    die("ERRO: Não foi possível conectar ao banco: " . $e->getMessage() . "\n");
}

$migrations = [
    "CREATE TABLE IF NOT EXISTS roles (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        system_role BOOLEAN DEFAULT false,
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS organization_settings (
        id SERIAL PRIMARY KEY,
        nome_empresa VARCHAR(255) DEFAULT 'Sistema de Controle de Acesso',
        logo_path VARCHAR(255),
        cor_primaria VARCHAR(20) DEFAULT '#0d6efd',
        cor_secundaria VARCHAR(20) DEFAULT '#6c757d',
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS usuarios (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        perfil VARCHAR(50) DEFAULT 'operador',
        ativo BOOLEAN DEFAULT true,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_login TIMESTAMP,
        role_id INTEGER,
        anonymized_at TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS profissionais_renner (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cpf VARCHAR(14),
        cargo VARCHAR(100),
        setor VARCHAR(100),
        foto VARCHAR(255),
        ativo BOOLEAN DEFAULT true,
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        brigadista BOOLEAN DEFAULT false
    )",
    
    "CREATE TABLE IF NOT EXISTS registro_acesso (
        id SERIAL PRIMARY KEY,
        profissional_renner_id INTEGER REFERENCES profissionais_renner(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        placa_veiculo VARCHAR(20),
        observacao TEXT,
        justificativa_retroativa TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS visitantes_cadastro (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo_documento VARCHAR(50),
        numero_documento VARCHAR(50),
        pais_documento VARCHAR(100),
        empresa VARCHAR(255),
        foto VARCHAR(255),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validade_ate DATE,
        ativo BOOLEAN DEFAULT true
    )",
    
    "CREATE TABLE IF NOT EXISTS visitantes_registros (
        id SERIAL PRIMARY KEY,
        visitante_cadastro_id INTEGER REFERENCES visitantes_cadastro(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        motivo TEXT,
        setor_destino VARCHAR(100),
        pessoa_contato VARCHAR(255),
        cracha VARCHAR(50),
        observacao TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS prestadores_cadastro (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo_documento VARCHAR(50),
        numero_documento VARCHAR(50),
        pais_documento VARCHAR(100),
        empresa VARCHAR(255),
        foto VARCHAR(255),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validade_ate DATE,
        ativo BOOLEAN DEFAULT true
    )",
    
    "CREATE TABLE IF NOT EXISTS prestadores_registros (
        id SERIAL PRIMARY KEY,
        prestador_cadastro_id INTEGER REFERENCES prestadores_cadastro(id),
        data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_saida TIMESTAMP,
        motivo TEXT,
        setor_destino VARCHAR(100),
        pessoa_contato VARCHAR(255),
        cracha VARCHAR(50),
        observacao TEXT,
        registrado_por INTEGER REFERENCES usuarios(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS ramais (
        id SERIAL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        area VARCHAR(255),
        ramal_celular VARCHAR(50),
        tipo VARCHAR(20) DEFAULT 'interno',
        observacoes TEXT,
        ativo BOOLEAN DEFAULT true,
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS sites (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT,
        capacity INTEGER,
        timezone VARCHAR(100) DEFAULT 'America/Sao_Paulo',
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS sectors (
        id SERIAL PRIMARY KEY,
        site_id INTEGER REFERENCES sites(id),
        name VARCHAR(255) NOT NULL,
        capacity INTEGER,
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS holidays (
        id SERIAL PRIMARY KEY,
        date DATE NOT NULL,
        name VARCHAR(255) NOT NULL,
        scope VARCHAR(50) DEFAULT 'global',
        site_id INTEGER REFERENCES sites(id),
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS permissions (
        id SERIAL PRIMARY KEY,
        key VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        module VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS role_permissions (
        id SERIAL PRIMARY KEY,
        role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
        permission_id INTEGER REFERENCES permissions(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(role_id, permission_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS audit_log (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES usuarios(id),
        acao VARCHAR(100) NOT NULL,
        entidade VARCHAR(100),
        entidade_id INTEGER,
        dados_antes JSONB,
        dados_depois JSONB,
        ip_address VARCHAR(45),
        user_agent TEXT,
        severidade VARCHAR(20) DEFAULT 'INFO',
        modulo VARCHAR(100),
        resultado VARCHAR(50) DEFAULT 'success',
        is_retroactive BOOLEAN DEFAULT false,
        justificativa TEXT,
        timestamp TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS lgpd_consentimento (
        id SERIAL PRIMARY KEY,
        usuario_id INTEGER,
        tipo_consentimento VARCHAR(100) NOT NULL,
        aceito BOOLEAN DEFAULT false,
        ip_address VARCHAR(45),
        data_consentimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE INDEX IF NOT EXISTS idx_registro_acesso_profissional ON registro_acesso(profissional_renner_id)",
    "CREATE INDEX IF NOT EXISTS idx_ramais_area ON ramais(area)",
    "CREATE INDEX IF NOT EXISTS idx_audit_log_timestamp ON audit_log(timestamp)",
    "CREATE INDEX IF NOT EXISTS idx_audit_log_user ON audit_log(user_id)",
    "CREATE INDEX IF NOT EXISTS idx_audit_log_entidade ON audit_log(entidade)",
    "CREATE INDEX IF NOT EXISTS idx_sectors_site ON sectors(site_id)",
    "CREATE INDEX IF NOT EXISTS idx_role_permissions_role ON role_permissions(role_id)",
    "CREATE INDEX IF NOT EXISTS idx_role_permissions_permission ON role_permissions(permission_id)"
];

$repairs = [
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='usuario_id') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='user_id') THEN ALTER TABLE audit_log RENAME COLUMN usuario_id TO user_id; RAISE NOTICE 'Renamed usuario_id to user_id'; END IF; END $$;",
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='tabela_afetada') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='entidade') THEN ALTER TABLE audit_log RENAME COLUMN tabela_afetada TO entidade; RAISE NOTICE 'Renamed tabela_afetada to entidade'; END IF; END $$;",
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='registro_id') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='entidade_id') THEN ALTER TABLE audit_log RENAME COLUMN registro_id TO entidade_id; RAISE NOTICE 'Renamed registro_id to entidade_id'; END IF; END $$;",
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='dados_anteriores') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='dados_antes') THEN ALTER TABLE audit_log RENAME COLUMN dados_anteriores TO dados_antes; RAISE NOTICE 'Renamed dados_anteriores to dados_antes'; END IF; END $$;",
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='dados_novos') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='dados_depois') THEN ALTER TABLE audit_log RENAME COLUMN dados_novos TO dados_depois; RAISE NOTICE 'Renamed dados_novos to dados_depois'; END IF; END $$;",
    "DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='data_hora') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='timestamp') THEN ALTER TABLE audit_log RENAME COLUMN data_hora TO timestamp; RAISE NOTICE 'Renamed data_hora to timestamp'; END IF; END $$;",
    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='severidade') THEN ALTER TABLE audit_log ADD COLUMN severidade VARCHAR(20) DEFAULT 'INFO'; END IF; END $$;",
    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='modulo') THEN ALTER TABLE audit_log ADD COLUMN modulo VARCHAR(100); END IF; END $$;",
    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='resultado') THEN ALTER TABLE audit_log ADD COLUMN resultado VARCHAR(50) DEFAULT 'success'; END IF; END $$;",
    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='is_retroactive') THEN ALTER TABLE audit_log ADD COLUMN is_retroactive BOOLEAN DEFAULT false; END IF; END $$;",
    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='audit_log' AND column_name='justificativa') THEN ALTER TABLE audit_log ADD COLUMN justificativa TEXT; END IF; END $$;"
];

echo "Reparando estruturas existentes...\n";
foreach ($repairs as $repair) {
    try {
        $pdo->exec($repair);
    } catch (PDOException $e) {
        echo "[AVISO] " . $e->getMessage() . "\n";
    }
}
echo "[OK] Reparações concluídas\n\n";

echo "Executando migrações...\n\n";

$success = 0;
$errors = 0;

foreach ($migrations as $i => $sql) {
    try {
        $pdo->exec($sql);
        $success++;
        $preview = substr(preg_replace('/\s+/', ' ', $sql), 0, 60);
        echo "[OK] " . ($i + 1) . ". " . $preview . "...\n";
    } catch (PDOException $e) {
        $errors++;
        echo "[ERRO] " . ($i + 1) . ". " . $e->getMessage() . "\n";
    }
}

echo "\n===========================================\n";
echo "Migrações concluídas!\n";
echo "Sucesso: $success | Erros: $errors\n";
echo "===========================================\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM roles");
if ($stmt->fetchColumn() == 0) {
    echo "\nCriando roles padrão...\n";
    $pdo->exec("INSERT INTO roles (id, name, description, system_role) VALUES 
        (1, 'Administrador', 'Acesso total ao sistema', true),
        (2, 'RH', 'Gestão de colaboradores', true),
        (3, 'Operador', 'Registro de acessos', true),
        (4, 'Visualizador', 'Apenas visualização', true)
        ON CONFLICT DO NOTHING");
    $pdo->exec("SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles))");
    echo "[OK] Roles padrão criadas\n";
}

echo "\nSincronizando permissões...\n";
{
    $permissionsSQL = "INSERT INTO permissions (key, description, module) VALUES
        ('config.write', 'Editar configurações gerais', 'config'),
        ('config.auth.write', 'Gerenciar autenticação', 'config'),
        ('config.rbac.write', 'Gerenciar permissões RBAC', 'config'),
        ('config.manage', 'Gerenciar configurações do sistema', 'config'),
        ('config.retention.write', 'Gerenciar políticas de retenção', 'config'),
        ('config.retention.delete', 'Excluir políticas de retenção', 'config'),
        ('config.retention.restore', 'Restaurar dados retidos', 'config'),
        ('config.retention.anonymize', 'Anonimizar dados', 'config'),
        ('registro_acesso.create', 'Criar registro de acesso', 'access'),
        ('registro_acesso.read', 'Visualizar registros de acesso', 'access'),
        ('registro_acesso.update', 'Editar registros de acesso', 'access'),
        ('registro_acesso.delete', 'Excluir registros de acesso', 'access'),
        ('registro_acesso.edit_all_fields', 'Editar todos os campos', 'access'),
        ('entrada.retroativa', 'Registrar entrada retroativa', 'access'),
        ('acesso.aprovar_retroativo', 'Aprovar entrada retroativa', 'access'),
        ('audit_log.read', 'Visualizar logs de auditoria', 'audit'),
        ('audit_log.export', 'Exportar logs de auditoria', 'audit'),
        ('usuarios.manage', 'Gerenciar usuários', 'users'),
        ('pre_cadastros.read', 'Visualizar pré-cadastros', 'access'),
        ('pre_cadastros.create', 'Criar pré-cadastros', 'access'),
        ('pre_cadastros.update', 'Editar pré-cadastros', 'access'),
        ('pre_cadastros.delete', 'Excluir pré-cadastros', 'access'),
        ('pre_cadastros.renovar', 'Renovar pré-cadastros', 'access'),
        ('ramais.read', 'Visualizar ramais', 'access'),
        ('ramais.write', 'Gerenciar ramais', 'access'),
        ('brigada.read', 'Visualizar brigada', 'access'),
        ('brigada.write', 'Gerenciar brigada', 'access'),
        ('brigada.manage', 'Administrar brigada', 'access'),
        ('importacao.visualizar', 'Acessar importação', 'access'),
        ('relatorios.editar_linha', 'Editar linha em relatórios', 'reports'),
        ('relatorios.excluir_linha', 'Excluir linha em relatórios', 'reports'),
        ('relatorios.exportar', 'Exportar relatórios', 'reports'),
        ('documentos.manage', 'Gerenciar documentos', 'access'),
        ('profissionais_renner.excluir', 'Excluir profissionais', 'access'),
        ('person.cpf.view_unmasked', 'Ver CPF sem máscara (LGPD)', 'privacy'),
        ('biometric.store', 'Armazenar biometria', 'privacy'),
        ('biometric.read', 'Visualizar biometria', 'privacy'),
        ('biometric.delete', 'Excluir biometria', 'privacy'),
        ('admin.system.manage', 'Administração avançada do sistema', 'config')
        ON CONFLICT (key) DO NOTHING";
    $pdo->exec($permissionsSQL);
    echo "[OK] Permissões sincronizadas\n";
    
    echo "Garantindo permissões do Administrador...\n";
    $pdo->exec("INSERT INTO role_permissions (role_id, permission_id) 
        SELECT 1, id FROM permissions 
        WHERE id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = 1)
        ON CONFLICT DO NOTHING");
    echo "[OK] Permissões do Administrador configuradas\n";
}

$stmt = $pdo->query("SELECT COUNT(*) FROM organization_settings");
if ($stmt->fetchColumn() == 0) {
    echo "Criando configuração inicial...\n";
    $pdo->exec("INSERT INTO organization_settings (nome_empresa) VALUES ('Sistema de Controle de Acesso')");
    echo "[OK] Configuração inicial criada\n";
}

$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE email = 'admin@sistema.com'");
if ($stmt->fetchColumn() == 0) {
    echo "\nCriando usuário administrador padrão...\n";
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo, role_id) 
                VALUES ('Administrador', 'admin@sistema.com', '$hash', 'administrador', true, 1)");
    echo "[OK] Usuário admin@sistema.com criado (senha: admin123)\n";
}

echo "\nMigração finalizada com sucesso!\n";
