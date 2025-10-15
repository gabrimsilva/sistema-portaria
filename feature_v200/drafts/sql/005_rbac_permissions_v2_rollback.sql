-- ================================================
-- ROLLBACK MIGRATION 005: RBAC Permissions v2.0.0
-- Descrição: Remove as 5 permissões v2.0.0 e suas associações
-- Data: 2025-10-15
-- ================================================

-- ⚠️ ATENÇÃO: Este rollback remove permissões do sistema!
-- Execute apenas se necessário reverter a migration 005.

-- ================================================
-- PARTE 1: REMOVER ASSOCIAÇÕES ROLE-PERMISSION
-- ================================================

-- Remover todas as associações das 5 novas permissões
DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions 
    WHERE key IN (
        'documentos.manage',
        'entrada.retroativa',
        'validade.manage',
        'ramais.manage',
        'reports.advanced_filters'
    )
);

-- Verificar remoção de associações
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
    
    IF assoc_count != 0 THEN
        RAISE EXCEPTION 'Erro: Ainda existem % associações', assoc_count;
    END IF;
    
    RAISE NOTICE 'OK: Todas as associações removidas';
END $$;

-- ================================================
-- PARTE 2: REMOVER PERMISSÕES
-- ================================================

-- Remover as 5 permissões
DELETE FROM permissions 
WHERE key IN (
    'documentos.manage',
    'entrada.retroativa',
    'validade.manage',
    'ramais.manage',
    'reports.advanced_filters'
);

-- Verificar remoção de permissões
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
    
    IF perm_count != 0 THEN
        RAISE EXCEPTION 'Erro: Ainda existem % permissões', perm_count;
    END IF;
    
    RAISE NOTICE 'OK: Todas as permissões removidas';
END $$;

-- ================================================
-- PARTE 3: AUDITORIA DO ROLLBACK
-- ================================================

INSERT INTO audit_log (
    user_id,
    entity,
    entity_id,
    action,
    before_data,
    after_data,
    ip_address,
    user_agent,
    severidade,
    modulo,
    resultado
) VALUES (
    1, -- Sistema/Admin
    'permissions',
    NULL,
    'rollback_migration_005_rbac_v2',
    jsonb_build_object(
        'permissoes_removidas', ARRAY[
            'documentos.manage',
            'entrada.retroativa',
            'validade.manage',
            'ramais.manage',
            'reports.advanced_filters'
        ],
        'associacoes_removidas', 13
    ),
    NULL,
    '127.0.0.1',
    'Rollback Script v2.0.0',
    'warning',
    'rbac',
    'sucesso'
);

-- ================================================
-- FIM DO ROLLBACK
-- ================================================

-- Exibir confirmação
SELECT 
    'Rollback RBAC v2.0.0 concluído!' as status,
    (SELECT COUNT(*) FROM permissions WHERE key IN (
        'documentos.manage', 'entrada.retroativa', 'validade.manage', 
        'ramais.manage', 'reports.advanced_filters'
    )) as permissoes_restantes;

-- Deve retornar 0 permissões restantes
