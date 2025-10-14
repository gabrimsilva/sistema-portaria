-- ================================================
-- ROLLBACK 003: CORREÇÃO SAÍDAS/PLACAS
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Reverter correções de saídas e placas
-- ================================================

BEGIN;

-- Remover índices de integridade referencial
DROP INDEX IF EXISTS idx_registro_prestador_lookup;
DROP INDEX IF EXISTS idx_prestadores_lookup;

-- Remover constraints
ALTER TABLE registro_acesso DROP CONSTRAINT IF EXISTS chk_registro_horario_valido;
ALTER TABLE prestadores_servico DROP CONSTRAINT IF EXISTS chk_prestadores_horario_valido;

-- Remover função de correção
DROP FUNCTION IF EXISTS corrigir_saidas_inconsistentes();
DROP FUNCTION IF EXISTS get_saida_consolidada(INTEGER);

-- Remover view consolidada
DROP VIEW IF EXISTS vw_prestadores_consolidado;

-- Remover triggers
DROP TRIGGER IF EXISTS trg_registro_sync_to_prestador ON registro_acesso;
DROP TRIGGER IF EXISTS trg_prestador_sync_saida ON prestadores_servico;

DROP FUNCTION IF EXISTS sync_registro_to_prestador();
DROP FUNCTION IF EXISTS sync_prestador_saida();

-- Remover índices de performance
DROP INDEX IF EXISTS idx_registro_placa_ativa;
DROP INDEX IF EXISTS idx_registro_saida_null;
DROP INDEX IF EXISTS idx_registro_tipo_entrada_saida;
DROP INDEX IF EXISTS idx_prestadores_placa_ativa;
DROP INDEX IF EXISTS idx_prestadores_saida_null;
DROP INDEX IF EXISTS idx_prestadores_entrada_saida;

COMMIT;

SELECT 'Rollback 003 concluído com sucesso!' AS status;
SELECT 'ATENÇÃO: Dados podem ficar inconsistentes novamente após rollback' AS aviso;
