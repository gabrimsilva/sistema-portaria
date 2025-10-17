-- ============================================================================
-- M5 - RBAC: Criar Permissões para Pré-Cadastros
-- ============================================================================
-- Data: 17/10/2025
-- Objetivo: Adicionar permissões de pré-cadastros no sistema RBAC moderno
-- Status: DRAFT - NÃO EXECUTAR SEM APROVAÇÃO
-- ============================================================================

-- ============================================================================
-- 1. CRIAR MÓDULO: Pré-Cadastros
-- ============================================================================

-- Verificar se a tabela de módulos existe (sistema RBAC moderno)
-- Se não existir, este script será ignorado (usar sistema legado)

DO $$
BEGIN
    -- Verificar se tabela rbac_modules existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'rbac_modules'
    ) THEN
        -- Inserir módulo de pré-cadastros (se não existir)
        INSERT INTO rbac_modules (name, description, active)
        VALUES (
            'pre_cadastros',
            'Pré-Cadastros de Visitantes e Prestadores (validade 1 ano)',
            true
        )
        ON CONFLICT (name) DO NOTHING;
        
        RAISE NOTICE 'Módulo pre_cadastros criado/verificado';
    ELSE
        RAISE NOTICE 'Tabela rbac_modules não existe - usando sistema legado';
    END IF;
END $$;

-- ============================================================================
-- 2. CRIAR PERMISSÕES
-- ============================================================================

DO $$
DECLARE
    v_module_id INTEGER;
BEGIN
    -- Verificar se tabela rbac_permissions existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'rbac_permissions'
    ) THEN
        -- Buscar ID do módulo
        SELECT id INTO v_module_id 
        FROM rbac_modules 
        WHERE name = 'pre_cadastros';
        
        IF v_module_id IS NOT NULL THEN
            -- Inserir permissões
            
            -- 1. Visualizar (Read)
            INSERT INTO rbac_permissions (module_id, name, description, active)
            VALUES (
                v_module_id,
                'pre_cadastros.read',
                'Visualizar lista de pré-cadastros e suas estatísticas',
                true
            )
            ON CONFLICT (name) DO NOTHING;
            
            -- 2. Criar (Create)
            INSERT INTO rbac_permissions (module_id, name, description, active)
            VALUES (
                v_module_id,
                'pre_cadastros.create',
                'Criar novos pré-cadastros de visitantes e prestadores',
                true
            )
            ON CONFLICT (name) DO NOTHING;
            
            -- 3. Editar (Update)
            INSERT INTO rbac_permissions (module_id, name, description, active)
            VALUES (
                v_module_id,
                'pre_cadastros.update',
                'Editar dados de pré-cadastros existentes',
                true
            )
            ON CONFLICT (name) DO NOTHING;
            
            -- 4. Excluir (Delete)
            INSERT INTO rbac_permissions (module_id, name, description, active)
            VALUES (
                v_module_id,
                'pre_cadastros.delete',
                'Excluir pré-cadastros (soft delete se sem registros)',
                true
            )
            ON CONFLICT (name) DO NOTHING;
            
            -- 5. Renovar (Renew)
            INSERT INTO rbac_permissions (module_id, name, description, active)
            VALUES (
                v_module_id,
                'pre_cadastros.renovar',
                'Renovar validade de pré-cadastros expirados (+1 ano)',
                true
            )
            ON CONFLICT (name) DO NOTHING;
            
            RAISE NOTICE 'Permissões de pre_cadastros criadas';
        ELSE
            RAISE NOTICE 'Módulo pre_cadastros não encontrado';
        END IF;
    ELSE
        RAISE NOTICE 'Tabela rbac_permissions não existe - usando sistema legado';
    END IF;
END $$;

-- ============================================================================
-- 3. ATRIBUIR PERMISSÕES AOS PERFIS
-- ============================================================================

DO $$
DECLARE
    v_role_admin_id INTEGER;
    v_role_porteiro_id INTEGER;
    v_perm_read_id INTEGER;
    v_perm_create_id INTEGER;
    v_perm_update_id INTEGER;
    v_perm_delete_id INTEGER;
    v_perm_renovar_id INTEGER;
BEGIN
    -- Verificar se tabela rbac_role_permissions existe
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'rbac_role_permissions'
    ) THEN
        -- Buscar IDs dos perfis
        SELECT id INTO v_role_admin_id FROM rbac_roles WHERE name = 'administrador';
        SELECT id INTO v_role_porteiro_id FROM rbac_roles WHERE name = 'porteiro';
        
        -- Buscar IDs das permissões
        SELECT id INTO v_perm_read_id FROM rbac_permissions WHERE name = 'pre_cadastros.read';
        SELECT id INTO v_perm_create_id FROM rbac_permissions WHERE name = 'pre_cadastros.create';
        SELECT id INTO v_perm_update_id FROM rbac_permissions WHERE name = 'pre_cadastros.update';
        SELECT id INTO v_perm_delete_id FROM rbac_permissions WHERE name = 'pre_cadastros.delete';
        SELECT id INTO v_perm_renovar_id FROM rbac_permissions WHERE name = 'pre_cadastros.renovar';
        
        -- Administrador: TODAS as permissões
        IF v_role_admin_id IS NOT NULL THEN
            INSERT INTO rbac_role_permissions (role_id, permission_id)
            VALUES 
                (v_role_admin_id, v_perm_read_id),
                (v_role_admin_id, v_perm_create_id),
                (v_role_admin_id, v_perm_update_id),
                (v_role_admin_id, v_perm_delete_id),
                (v_role_admin_id, v_perm_renovar_id)
            ON CONFLICT (role_id, permission_id) DO NOTHING;
            
            RAISE NOTICE 'Permissões atribuídas ao Administrador';
        END IF;
        
        -- Porteiro: TODAS exceto DELETE
        IF v_role_porteiro_id IS NOT NULL THEN
            INSERT INTO rbac_role_permissions (role_id, permission_id)
            VALUES 
                (v_role_porteiro_id, v_perm_read_id),
                (v_role_porteiro_id, v_perm_create_id),
                (v_role_porteiro_id, v_perm_update_id),
                (v_role_porteiro_id, v_perm_renovar_id)
            ON CONFLICT (role_id, permission_id) DO NOTHING;
            
            RAISE NOTICE 'Permissões atribuídas ao Porteiro (sem delete)';
        END IF;
    ELSE
        RAISE NOTICE 'Tabela rbac_role_permissions não existe - usando sistema legado';
    END IF;
END $$;

-- ============================================================================
-- 4. VERIFICAÇÃO (Query de teste)
-- ============================================================================

/*
-- Verificar permissões criadas:
SELECT 
    m.name as modulo,
    p.name as permissao,
    p.description
FROM rbac_permissions p
JOIN rbac_modules m ON m.id = p.module_id
WHERE m.name = 'pre_cadastros'
ORDER BY p.name;

-- Verificar permissões por perfil:
SELECT 
    r.name as perfil,
    m.name as modulo,
    p.name as permissao
FROM rbac_role_permissions rp
JOIN rbac_roles r ON r.id = rp.role_id
JOIN rbac_permissions p ON p.id = rp.permission_id
JOIN rbac_modules m ON m.id = p.module_id
WHERE m.name = 'pre_cadastros'
ORDER BY r.name, p.name;
*/

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
-- IMPORTANTE: Este script está em DRAFT e NÃO deve ser executado sem aprovação
-- Sistema híbrido: funciona com RBAC moderno OU sistema legado
-- ============================================================================
