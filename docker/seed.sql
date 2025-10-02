-- SEED: Dados iniciais do sistema
-- Sistema de Controle de Acesso - Renner Coatings
-- Executado automaticamente após init.sql

-- 1) Criar role 'administrador' se não existir
INSERT INTO roles (name, description, system_role, active, created_at)
VALUES ('administrador', 'Administrador do Sistema com acesso total', TRUE, TRUE, CURRENT_TIMESTAMP)
ON CONFLICT (name) DO NOTHING;

-- 2) Criar usuário admin padrão
-- Email: gmsilva@rennercoatings.com
-- Senha: rhsa@2019
-- Hash gerado com password_hash('rhsa@2019', PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, email, senha_hash, role_id, ativo, data_criacao)
VALUES (
    'Administrador',
    'gmsilva@rennercoatings.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    (SELECT id FROM roles WHERE name = 'administrador' LIMIT 1),
    TRUE,
    CURRENT_TIMESTAMP
)
ON CONFLICT (email) DO UPDATE SET
    senha_hash = EXCLUDED.senha_hash,
    role_id = EXCLUDED.role_id,
    ativo = TRUE;

-- 3) Criar permissões básicas do administrador
INSERT INTO permissions (key, description, module, created_at)
VALUES 
    ('config.*', 'Acesso total às configurações', 'config', CURRENT_TIMESTAMP),
    ('users.*', 'Gestão completa de usuários', 'users', CURRENT_TIMESTAMP),
    ('reports.*', 'Visualização de todos os relatórios', 'reports', CURRENT_TIMESTAMP),
    ('audit.*', 'Acesso aos logs de auditoria', 'audit', CURRENT_TIMESTAMP),
    ('access.*', 'Gestão de controle de acesso', 'access', CURRENT_TIMESTAMP),
    ('privacy.*', 'Gestão de LGPD e privacidade', 'privacy', CURRENT_TIMESTAMP)
ON CONFLICT (key) DO NOTHING;

-- 4) Associar todas as permissões à role administrador
INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT 
    r.id,
    p.id,
    CURRENT_TIMESTAMP
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'administrador'
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 5) Configuração inicial da organização
INSERT INTO organization_settings (
    company_name, 
    cnpj, 
    timezone, 
    locale, 
    created_at
)
VALUES (
    'Renner Coatings',
    '00.000.000/0001-00',
    'America/Sao_Paulo',
    'pt_BR',
    CURRENT_TIMESTAMP
)
ON CONFLICT DO NOTHING;

-- 6) Políticas de autenticação padrão
INSERT INTO auth_policies (
    min_password_length,
    password_expiry_days,
    max_session_timeout,
    require_2fa,
    allow_sso,
    created_at
)
VALUES (
    8,
    90,
    3600,
    FALSE,
    FALSE,
    CURRENT_TIMESTAMP
)
ON CONFLICT DO NOTHING;

-- Mensagem de confirmação
DO $$
BEGIN
    RAISE NOTICE '✅ Seed executado com sucesso!';
    RAISE NOTICE '📧 Admin criado: gmsilva@rennercoatings.com';
    RAISE NOTICE '🔑 Senha padrão: rhsa@2019';
    RAISE NOTICE '⚠️  ALTERE A SENHA IMEDIATAMENTE APÓS PRIMEIRO LOGIN!';
END $$;
