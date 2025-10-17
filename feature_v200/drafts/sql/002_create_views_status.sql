-- ============================================================================
-- M2 - MIGRATION 002: Criar Views com Status Derivado
-- ============================================================================
-- Data: 17/10/2025
-- Objetivo: Calcular status de validade dinamicamente (válido/expirando/expirado)
-- Status: DRAFT - NÃO EXECUTAR SEM APROVAÇÃO
-- ============================================================================

-- ============================================================================
-- 1. VIEW: Visitantes com Status de Validade
-- ============================================================================
CREATE OR REPLACE VIEW vw_visitantes_cadastro_status AS
SELECT 
    vc.*,
    
    -- Status Derivado (calculado dinamicamente)
    CASE 
        WHEN vc.deleted_at IS NOT NULL THEN 'excluido'
        WHEN vc.ativo = false THEN 'inativo'
        WHEN vc.valid_until < CURRENT_DATE THEN 'expirado'
        WHEN vc.valid_until <= (CURRENT_DATE + INTERVAL '30 days') THEN 'expirando'
        ELSE 'valido'
    END AS status_validade,
    
    -- Dias Restantes (para badges de aviso)
    CASE 
        WHEN vc.valid_until >= CURRENT_DATE 
        THEN (vc.valid_until - CURRENT_DATE)::INTEGER
        ELSE NULL
    END AS dias_restantes,
    
    -- Dias Expirado (para alertas)
    CASE 
        WHEN vc.valid_until < CURRENT_DATE 
        THEN (CURRENT_DATE - vc.valid_until)::INTEGER
        ELSE NULL
    END AS dias_expirado,
    
    -- Contagem de Entradas (quantas vezes usou este cadastro)
    (
        SELECT COUNT(*) 
        FROM visitantes_registros vr 
        WHERE vr.cadastro_id = vc.id 
          AND vr.deleted_at IS NULL
    ) AS total_entradas,
    
    -- Última Entrada (data da última visita)
    (
        SELECT MAX(vr.entrada) 
        FROM visitantes_registros vr 
        WHERE vr.cadastro_id = vc.id 
          AND vr.deleted_at IS NULL
    ) AS ultima_entrada,
    
    -- Está Presente Agora (se tem entrada sem saída)
    EXISTS (
        SELECT 1 
        FROM visitantes_registros vr 
        WHERE vr.cadastro_id = vc.id 
          AND vr.saida IS NULL 
          AND vr.deleted_at IS NULL
    ) AS presente_agora

FROM visitantes_cadastro vc
WHERE vc.deleted_at IS NULL;

-- Comentários
COMMENT ON VIEW vw_visitantes_cadastro_status IS 'View com status de validade calculado dinamicamente e estatísticas de uso';

-- ============================================================================
-- 2. VIEW: Prestadores com Status de Validade
-- ============================================================================
CREATE OR REPLACE VIEW vw_prestadores_cadastro_status AS
SELECT 
    pc.*,
    
    -- Status Derivado (calculado dinamicamente)
    CASE 
        WHEN pc.deleted_at IS NOT NULL THEN 'excluido'
        WHEN pc.ativo = false THEN 'inativo'
        WHEN pc.valid_until < CURRENT_DATE THEN 'expirado'
        WHEN pc.valid_until <= (CURRENT_DATE + INTERVAL '30 days') THEN 'expirando'
        ELSE 'valido'
    END AS status_validade,
    
    -- Dias Restantes
    CASE 
        WHEN pc.valid_until >= CURRENT_DATE 
        THEN (pc.valid_until - CURRENT_DATE)::INTEGER
        ELSE NULL
    END AS dias_restantes,
    
    -- Dias Expirado
    CASE 
        WHEN pc.valid_until < CURRENT_DATE 
        THEN (CURRENT_DATE - pc.valid_until)::INTEGER
        ELSE NULL
    END AS dias_expirado,
    
    -- Contagem de Entradas
    (
        SELECT COUNT(*) 
        FROM prestadores_registros pr 
        WHERE pr.cadastro_id = pc.id 
          AND pr.deleted_at IS NULL
    ) AS total_entradas,
    
    -- Última Entrada
    (
        SELECT MAX(pr.entrada) 
        FROM prestadores_registros pr 
        WHERE pr.cadastro_id = pc.id 
          AND pr.deleted_at IS NULL
    ) AS ultima_entrada,
    
    -- Está Presente Agora
    EXISTS (
        SELECT 1 
        FROM prestadores_registros pr 
        WHERE pr.cadastro_id = pc.id 
          AND pr.saida IS NULL 
          AND pr.deleted_at IS NULL
    ) AS presente_agora

FROM prestadores_cadastro pc
WHERE pc.deleted_at IS NULL;

-- Comentários
COMMENT ON VIEW vw_prestadores_cadastro_status IS 'View com status de validade calculado dinamicamente e estatísticas de uso';

-- ============================================================================
-- 3. VIEW: Cadastros Expirados (para relatórios)
-- ============================================================================
CREATE OR REPLACE VIEW vw_cadastros_expirados AS
SELECT 
    'visitante' AS tipo,
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    valid_until,
    (CURRENT_DATE - valid_until)::INTEGER AS dias_expirado,
    total_entradas,
    ultima_entrada
FROM vw_visitantes_cadastro_status
WHERE status_validade = 'expirado'

UNION ALL

SELECT 
    'prestador' AS tipo,
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    valid_until,
    (CURRENT_DATE - valid_until)::INTEGER AS dias_expirado,
    total_entradas,
    ultima_entrada
FROM vw_prestadores_cadastro_status
WHERE status_validade = 'expirado';

-- Comentários
COMMENT ON VIEW vw_cadastros_expirados IS 'View consolidada de todos os cadastros expirados (visitantes + prestadores)';

-- ============================================================================
-- 4. VIEW: Cadastros Expirando em Breve (próximos 30 dias)
-- ============================================================================
CREATE OR REPLACE VIEW vw_cadastros_expirando AS
SELECT 
    'visitante' AS tipo,
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    valid_until,
    dias_restantes,
    total_entradas,
    ultima_entrada
FROM vw_visitantes_cadastro_status
WHERE status_validade = 'expirando'

UNION ALL

SELECT 
    'prestador' AS tipo,
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    valid_until,
    dias_restantes,
    total_entradas,
    ultima_entrada
FROM vw_prestadores_cadastro_status
WHERE status_validade = 'expirando';

-- Comentários
COMMENT ON VIEW vw_cadastros_expirando IS 'View consolidada de cadastros expirando nos próximos 30 dias';

-- ============================================================================
-- 5. VIEW: Histórico Completo de Visitante (para detalhes)
-- ============================================================================
CREATE OR REPLACE VIEW vw_visitante_historico AS
SELECT 
    vcs.id AS cadastro_id,
    vcs.nome,
    vcs.empresa,
    vcs.doc_type,
    vcs.doc_number,
    vcs.valid_from,
    vcs.valid_until,
    vcs.status_validade,
    vcs.dias_restantes,
    vr.id AS registro_id,
    vr.entrada,
    vr.saida,
    vr.funcionario_responsavel,
    vr.setor,
    vr.observacoes_entrada,
    -- Tempo de permanência (calculado)
    CASE 
        WHEN vr.saida IS NOT NULL 
        THEN EXTRACT(EPOCH FROM (vr.saida - vr.entrada))/3600 
        ELSE NULL 
    END AS horas_permanencia
FROM vw_visitantes_cadastro_status vcs
LEFT JOIN visitantes_registros vr ON vr.cadastro_id = vcs.id AND vr.deleted_at IS NULL
ORDER BY vr.entrada DESC;

-- Comentários
COMMENT ON VIEW vw_visitante_historico IS 'View completa com cadastro + todas as entradas/saídas do visitante';

-- ============================================================================
-- 6. VIEW: Histórico Completo de Prestador
-- ============================================================================
CREATE OR REPLACE VIEW vw_prestador_historico AS
SELECT 
    pcs.id AS cadastro_id,
    pcs.nome,
    pcs.empresa,
    pcs.doc_type,
    pcs.doc_number,
    pcs.valid_from,
    pcs.valid_until,
    pcs.status_validade,
    pcs.dias_restantes,
    pr.id AS registro_id,
    pr.entrada,
    pr.saida,
    pr.funcionario_responsavel,
    pr.setor,
    pr.observacoes_entrada,
    -- Tempo de permanência
    CASE 
        WHEN pr.saida IS NOT NULL 
        THEN EXTRACT(EPOCH FROM (pr.saida - pr.entrada))/3600 
        ELSE NULL 
    END AS horas_permanencia
FROM vw_prestadores_cadastro_status pcs
LEFT JOIN prestadores_registros pr ON pr.cadastro_id = pcs.id AND pr.deleted_at IS NULL
ORDER BY pr.entrada DESC;

-- Comentários
COMMENT ON VIEW vw_prestador_historico IS 'View completa com cadastro + todas as entradas/saídas do prestador';

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
-- IMPORTANTE: Este script está em DRAFT e NÃO deve ser executado sem aprovação
-- Próximo: 003_create_indexes.sql (Índices de performance)
-- ============================================================================
