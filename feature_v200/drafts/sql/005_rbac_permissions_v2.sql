-- ================================================
-- MIGRATION 005: RBAC Permissions v2.0.0
-- Descrição: Adiciona 5 novas permissões para funcionalidades v2.0.0
-- Data: 2025-10-15
-- ================================================

-- ================================================
-- PARTE 1: INSERIR NOVAS PERMISSÕES
-- ================================================

-- 1. documentos.manage - Gerenciar documentos internacionais
INSERT INTO permissions (key, description, module) 
VALUES ('documentos.manage', 'Gerenciar documentos internacionais (Passaporte, RNE, DNI, CI)', 'documentos')
ON CONFLICT (key) DO NOTHING;

-- 2. entrada.retroativa - Registrar entradas retroativas
INSERT INTO permissions (key, description, module) 
VALUES ('entrada.retroativa', 'Registrar entradas retroativas (alterar histórico)', 'acesso')
ON CONFLICT (key) DO NOTHING;

-- 3. validade.manage - Gerenciar validade de cadastros
INSERT INTO permissions (key, description, module) 
VALUES ('validade.manage', 'Gerenciar validade de cadastros (renovar/bloquear)', 'validade')
ON CONFLICT (key) DO NOTHING;

-- 4. ramais.manage - Gerenciar ramais corporativos
INSERT INTO permissions (key, description, module) 
VALUES ('ramais.manage', 'Gerenciar ramais corporativos (CRUD)', 'ramais')
ON CONFLICT (key) DO NOTHING;

-- 5. reports.advanced_filters - Filtros avançados em relatórios
INSERT INTO permissions (key, description, module) 
VALUES ('reports.advanced_filters', 'Usar filtros avançados em relatórios (tipo doc, país, validade)', 'reports')
ON CONFLICT (key) DO NOTHING;

-- ================================================
-- PARTE 2: ASSOCIAR PERMISSÕES A ROLES
-- ================================================

-- IMPORTANTE: Usar subqueries para pegar IDs dinamicamente

-- ===============================================
-- ADMINISTRADOR - TEM TODAS AS 5 PERMISSÕES
-- ===============================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'administrador'),
    (SELECT id FROM permissions WHERE key = 'documentos.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'administrador')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'documentos.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'administrador'),
    (SELECT id FROM permissions WHERE key = 'entrada.retroativa')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'administrador')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'entrada.retroativa')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'administrador'),
    (SELECT id FROM permissions WHERE key = 'validade.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'administrador')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'validade.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'administrador'),
    (SELECT id FROM permissions WHERE key = 'ramais.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'administrador')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'ramais.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'administrador'),
    (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'administrador')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
);

-- ===============================================
-- SEGURANÇA - TEM 2 PERMISSÕES
-- ===============================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'seguranca'),
    (SELECT id FROM permissions WHERE key = 'entrada.retroativa')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'seguranca')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'entrada.retroativa')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'seguranca'),
    (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'seguranca')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
);

-- ===============================================
-- RECEPÇÃO - TEM 2 PERMISSÕES
-- ===============================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'recepcao'),
    (SELECT id FROM permissions WHERE key = 'documentos.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'recepcao')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'documentos.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'recepcao'),
    (SELECT id FROM permissions WHERE key = 'validade.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'recepcao')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'validade.manage')
);

-- ===============================================
-- RH - TEM 4 PERMISSÕES
-- ===============================================

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'rh'),
    (SELECT id FROM permissions WHERE key = 'documentos.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'rh')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'documentos.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'rh'),
    (SELECT id FROM permissions WHERE key = 'validade.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'rh')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'validade.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'rh'),
    (SELECT id FROM permissions WHERE key = 'ramais.manage')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'rh')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'ramais.manage')
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'rh'),
    (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
WHERE NOT EXISTS (
    SELECT 1 FROM role_permissions 
    WHERE role_id = (SELECT id FROM roles WHERE name = 'rh')
      AND permission_id = (SELECT id FROM permissions WHERE key = 'reports.advanced_filters')
);

-- ===============================================
-- PORTEIRO - NÃO TEM NOVAS PERMISSÕES
-- ===============================================
-- (Nenhuma inserção necessária)

-- ================================================
-- PARTE 3: VERIFICAÇÕES
-- ================================================

-- Verificar se todas as 5 permissões foram criadas
DO $$
DECLARE
    perm_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO perm_count 
    FROM permissions 
    WHERE key IN (
        'documentos.manage',
        'entrada.retroativa',
        'validade.manage',
        'ramais.manage',
        'reports.advanced_filters'
    );
    
    IF perm_count != 5 THEN
        RAISE EXCEPTION 'Erro: Esperado 5 permissões, encontrado %', perm_count;
    END IF;
    
    RAISE NOTICE 'OK: 5 permissões criadas com sucesso';
END $$;

-- Verificar associações (deve ter 13 no total)
-- Admin: 5, Segurança: 2, Recepção: 2, RH: 4, Porteiro: 0 = 13
DO $$
DECLARE
    assoc_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO assoc_count 
    FROM role_permissions rp
    JOIN permissions p ON rp.permission_id = p.id
    WHERE p.key IN (
        'documentos.manage',
        'entrada.retroativa',
        'validade.manage',
        'ramais.manage',
        'reports.advanced_filters'
    );
    
    IF assoc_count != 13 THEN
        RAISE EXCEPTION 'Erro: Esperado 13 associações, encontrado %', assoc_count;
    END IF;
    
    RAISE NOTICE 'OK: 13 associações role-permission criadas';
END $$;

-- ================================================
-- PARTE 4: AUDITORIA
-- ================================================

-- Registrar na auditoria
INSERT INTO audit_log (
    user_id,
    acao,
    entidade,
    entidade_id,
    dados_antes,
    dados_depois,
    ip_address,
    user_agent,
    severidade,
    modulo,
    resultado
) VALUES (
    1,
    'migration_005_rbac_v2',
    'permissions',
    0,
    NULL,
    jsonb_build_object(
        'novas_permissoes', 5,
        'associacoes', 13,
        'roles_afetadas', ARRAY['administrador', 'seguranca', 'recepcao', 'rh']
    ),
    '127.0.0.1',
    'Migration Script v2.0.0',
    'info',
    'rbac',
    'sucesso'
);

-- ================================================
-- FIM DA MIGRATION 005
-- ================================================

-- Exibir resumo
SELECT 
    'RBAC v2.0.0 aplicado com sucesso!' as status,
    (SELECT COUNT(*) FROM permissions WHERE module IN ('documentos', 'validade', 'ramais')) as novas_permissoes,
    (SELECT COUNT(*) FROM role_permissions rp 
     JOIN permissions p ON rp.permission_id = p.id 
     WHERE p.key IN ('documentos.manage', 'entrada.retroativa', 'validade.manage', 'ramais.manage', 'reports.advanced_filters')) as associacoes;
