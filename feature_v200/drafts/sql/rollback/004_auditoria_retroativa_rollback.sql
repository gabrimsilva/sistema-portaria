-- ================================================
-- ROLLBACK 004: AUDITORIA RETROATIVA
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Reverter auditoria retroativa
-- ================================================

BEGIN;

-- Remover funções
DROP FUNCTION IF EXISTS stats_entradas_retroativas(DATE, DATE);
DROP FUNCTION IF EXISTS registrar_entrada_retroativa_profissional(INTEGER, TIMESTAMP, TEXT, INTEGER, VARCHAR, TEXT);

-- Remover view
DROP VIEW IF EXISTS vw_entradas_retroativas;

-- Remover permissão
DELETE FROM permissions WHERE name = 'acesso.retroativo';

-- Remover tabela de entradas retroativas
DROP TABLE IF EXISTS entradas_retroativas CASCADE;

-- Remover índice
DROP INDEX IF EXISTS idx_audit_modulo_retroativo;
DROP INDEX IF EXISTS idx_audit_retroative;

-- Remover colunas de audit_log
ALTER TABLE audit_log
    DROP COLUMN IF EXISTS is_retroactive,
    DROP COLUMN IF EXISTS justificativa;

COMMIT;

SELECT 'Rollback 004 concluído com sucesso!' AS status;
