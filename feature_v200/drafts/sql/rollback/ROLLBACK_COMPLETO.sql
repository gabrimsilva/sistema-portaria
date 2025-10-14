-- ================================================
-- ROLLBACK COMPLETO - VERSÃO 2.0.0
-- Data: 14/10/2025
-- Objetivo: Reverter TODAS as migrations da v2.0.0
-- ================================================

-- ATENÇÃO: Este script reverte TODAS as alterações!
-- Use apenas em caso de problemas graves
-- Executar na ORDEM INVERSA das migrations

\echo '========================================='
\echo 'INICIANDO ROLLBACK COMPLETO V2.0.0'
\echo '========================================='

\echo ''
\echo '🔄 Executando Rollback 004: Auditoria Retroativa...'
\i 004_auditoria_retroativa_rollback.sql

\echo ''
\echo '🔄 Executando Rollback 003: Correção Saídas/Placas...'
\i 003_fix_saida_placas_rollback.sql

\echo ''
\echo '🔄 Executando Rollback 002: Validade de Cadastros...'
\i 002_validade_cadastros_rollback.sql

\echo ''
\echo '🔄 Executando Rollback 001: Documentos Estrangeiros...'
\i 001_docs_estrangeiros_rollback.sql

\echo ''
\echo '========================================='
\echo '✅ ROLLBACK COMPLETO CONCLUÍDO!'
\echo '========================================='
\echo ''
\echo 'Sistema revertido para versão anterior à 2.0.0'
\echo 'Reinicie a aplicação e verifique funcionalidades'
