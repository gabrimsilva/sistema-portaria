-- ============================================================================
-- M2 - ROLLBACK SCRIPT: Reverter Migrations de Pré-Cadastros
-- ============================================================================
-- Data: 17/10/2025
-- Objetivo: Reverter todas as alterações das migrations 001, 002, 003
-- Status: DRAFT - Usar apenas se precisar reverter
-- ATENÇÃO: Isto apaga TODOS os dados de pré-cadastros!
-- ============================================================================

-- ============================================================================
-- PASSO 1: Remover Views (ordem inversa de criação)
-- ============================================================================

DROP VIEW IF EXISTS vw_prestador_historico CASCADE;
DROP VIEW IF EXISTS vw_visitante_historico CASCADE;
DROP VIEW IF EXISTS vw_cadastros_expirando CASCADE;
DROP VIEW IF EXISTS vw_cadastros_expirados CASCADE;
DROP VIEW IF EXISTS vw_prestadores_cadastro_status CASCADE;
DROP VIEW IF EXISTS vw_visitantes_cadastro_status CASCADE;

-- ============================================================================
-- PASSO 2: Remover Índices
-- ============================================================================

-- Visitantes Cadastro
DROP INDEX IF EXISTS idx_visitantes_cadastro_nome_fts;
DROP INDEX IF EXISTS idx_visitantes_cadastro_ativo;
DROP INDEX IF EXISTS idx_visitantes_cadastro_placa;
DROP INDEX IF EXISTS idx_visitantes_cadastro_empresa;
DROP INDEX IF EXISTS idx_visitantes_cadastro_validade;
DROP INDEX IF EXISTS idx_visitantes_cadastro_nome;
DROP INDEX IF EXISTS idx_visitantes_cadastro_doc;

-- Visitantes Registros
DROP INDEX IF EXISTS idx_visitantes_registros_cadastro_entrada;
DROP INDEX IF EXISTS idx_visitantes_registros_presentes;
DROP INDEX IF EXISTS idx_visitantes_registros_entrada;
DROP INDEX IF EXISTS idx_visitantes_registros_cadastro;

-- Prestadores Cadastro
DROP INDEX IF EXISTS idx_prestadores_cadastro_nome_fts;
DROP INDEX IF EXISTS idx_prestadores_cadastro_ativo;
DROP INDEX IF EXISTS idx_prestadores_cadastro_placa;
DROP INDEX IF EXISTS idx_prestadores_cadastro_empresa;
DROP INDEX IF EXISTS idx_prestadores_cadastro_validade;
DROP INDEX IF EXISTS idx_prestadores_cadastro_nome;
DROP INDEX IF EXISTS idx_prestadores_cadastro_doc;

-- Prestadores Registros
DROP INDEX IF EXISTS idx_prestadores_registros_cadastro_entrada;
DROP INDEX IF EXISTS idx_prestadores_registros_presentes;
DROP INDEX IF EXISTS idx_prestadores_registros_entrada;
DROP INDEX IF EXISTS idx_prestadores_registros_cadastro;

-- ============================================================================
-- PASSO 3: Remover Triggers
-- ============================================================================

DROP TRIGGER IF EXISTS set_timestamp_prestadores_registros ON prestadores_registros;
DROP TRIGGER IF EXISTS set_timestamp_visitantes_registros ON visitantes_registros;
DROP TRIGGER IF EXISTS set_timestamp_prestadores_cadastro ON prestadores_cadastro;
DROP TRIGGER IF EXISTS set_timestamp_visitantes_cadastro ON visitantes_cadastro;

-- Nota: Não removemos a função trigger_set_timestamp() pois pode ser usada em outras tabelas

-- ============================================================================
-- PASSO 4: Remover Tabelas (ordem inversa de dependências)
-- ============================================================================

-- ⚠️ ATENÇÃO: Isto apaga TODOS os dados!
-- Tabelas de registros primeiro (têm FK para cadastros)
DROP TABLE IF EXISTS prestadores_registros CASCADE;
DROP TABLE IF EXISTS visitantes_registros CASCADE;

-- Tabelas de cadastros por último
DROP TABLE IF EXISTS prestadores_cadastro CASCADE;
DROP TABLE IF EXISTS visitantes_cadastro CASCADE;

-- ============================================================================
-- VERIFICAÇÃO PÓS-ROLLBACK
-- ============================================================================

/*
-- Verificar se tabelas foram removidas:
SELECT tablename 
FROM pg_tables 
WHERE tablename IN (
    'visitantes_cadastro',
    'visitantes_registros',
    'prestadores_cadastro',
    'prestadores_registros'
);
-- Resultado esperado: 0 linhas

-- Verificar se views foram removidas:
SELECT viewname 
FROM pg_views 
WHERE viewname LIKE 'vw_%cadastro%' OR viewname LIKE 'vw_%historico';
-- Resultado esperado: 0 linhas

-- Verificar se índices foram removidos:
SELECT indexname 
FROM pg_indexes 
WHERE indexname LIKE 'idx_visitantes_cadastro%' 
   OR indexname LIKE 'idx_prestadores_cadastro%';
-- Resultado esperado: 0 linhas
*/

-- ============================================================================
-- FIM DO ROLLBACK
-- ============================================================================
-- Sistema voltou ao estado anterior (sem pré-cadastros)
-- Tabelas antigas (visitantes_novo, prestadores_servico) permanecem intactas
-- ============================================================================
