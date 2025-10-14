-- ================================================
-- ROLLBACK 002: VALIDADE DE CADASTROS
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Reverter migration de validade de cadastros
-- ================================================

BEGIN;

-- Remover tabela de configurações
DROP TABLE IF EXISTS validity_configurations CASCADE;

-- Remover funções
DROP FUNCTION IF EXISTS stats_entradas_retroativas(DATE, DATE);
DROP FUNCTION IF EXISTS marcar_cadastros_expirados();
DROP FUNCTION IF EXISTS renovar_cadastro_prestador(INTEGER, INTEGER);
DROP FUNCTION IF EXISTS renovar_cadastro_visitante(INTEGER, INTEGER);
DROP FUNCTION IF EXISTS calculate_validity_status(TIMESTAMP, TIMESTAMP, VARCHAR);

-- Remover views
DROP VIEW IF EXISTS vw_prestadores_expirando;
DROP VIEW IF EXISTS vw_visitantes_expirando;

-- Remover triggers
DROP TRIGGER IF EXISTS trg_prestadores_validity_status ON prestadores_servico;
DROP TRIGGER IF EXISTS trg_visitantes_validity_status ON visitantes;

DROP FUNCTION IF EXISTS update_validity_status_prestadores();
DROP FUNCTION IF EXISTS update_validity_status_visitantes();

-- Remover índices
DROP INDEX IF EXISTS idx_prestadores_expirando_soon;
DROP INDEX IF EXISTS idx_prestadores_validade_ativa;
DROP INDEX IF EXISTS idx_visitantes_expirando_soon;
DROP INDEX IF EXISTS idx_visitantes_validade_ativa;

-- Nota: Campos valid_from, valid_until, validity_status 
-- foram adicionados na migration 001, serão removidos no rollback 001

COMMIT;

SELECT 'Rollback 002 concluído com sucesso!' AS status;
