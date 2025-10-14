-- ================================================
-- APLICAR MIGRATIONS - VERSÃO 2.0.0
-- Data: 14/10/2025
-- Objetivo: Aplicar TODAS as migrations da v2.0.0
-- ================================================

-- ATENÇÃO: Faça backup completo antes de executar!
-- pg_dump -U usuario -d nome_banco > backup_antes_v200.sql

\echo '========================================='
\echo 'INICIANDO APLICAÇÃO V2.0.0'
\echo '========================================='

\echo ''
\echo '📋 Migration 001: Documentos Estrangeiros...'
\i 001_docs_estrangeiros.sql

\echo ''
\echo '📋 Migration 002: Validade de Cadastros...'
\i 002_validade_cadastros.sql

\echo ''
\echo '📋 Migration 003: Correção Saídas/Placas...'
\i 003_fix_saida_placas.sql

\echo ''
\echo '📋 Migration 004: Auditoria Retroativa...'
\i 004_auditoria_retroativa.sql

\echo ''
\echo '========================================='
\echo '🔧 PÓS-MIGRATIONS: Correções e Validações'
\echo '========================================='

\echo ''
\echo '🔧 Executando correção de saídas inconsistentes...'
SELECT * FROM corrigir_saidas_inconsistentes();

\echo ''
\echo '🔧 Marcando cadastros expirados...'
SELECT * FROM marcar_cadastros_expirados();

\echo ''
\echo '========================================='
\echo '📊 VERIFICAÇÕES FINAIS'
\echo '========================================='

\echo ''
\echo '📊 Verificando documentos migrados:'
SELECT 
    'Visitantes' AS tabela,
    COUNT(*) AS total_registros,
    COUNT(doc_number) AS com_doc_number,
    COUNT(CASE WHEN doc_type = 'CPF' THEN 1 END) AS tipo_cpf
FROM visitantes
UNION ALL
SELECT 
    'Prestadores',
    COUNT(*),
    COUNT(doc_number),
    COUNT(CASE WHEN doc_type = 'CPF' THEN 1 END)
FROM prestadores_servico
UNION ALL
SELECT 
    'Profissionais',
    COUNT(*),
    COUNT(doc_number),
    COUNT(CASE WHEN doc_type = 'CPF' THEN 1 END)
FROM profissionais_renner;

\echo ''
\echo '📊 Verificando validade de cadastros:'
SELECT 
    'Visitantes' AS tabela,
    validity_status,
    COUNT(*) AS total
FROM visitantes
GROUP BY validity_status
UNION ALL
SELECT 
    'Prestadores',
    validity_status,
    COUNT(*)
FROM prestadores_servico
GROUP BY validity_status
ORDER BY tabela, validity_status;

\echo ''
\echo '📊 Verificando prestadores em aberto:'
SELECT 
    'Via Tabela Antiga' AS metodo,
    COUNT(*) AS total_abertos
FROM prestadores_servico
WHERE entrada IS NOT NULL AND saida IS NULL
UNION ALL
SELECT 
    'Via View Consolidada',
    COUNT(*)
FROM vw_prestadores_consolidado
WHERE status_acesso = 'aberto';

\echo ''
\echo '========================================='
\echo '✅ MIGRATIONS APLICADAS COM SUCESSO!'
\echo '========================================='
\echo ''
\echo 'Próximos passos:'
\echo '1. Atualizar código da aplicação (ver COMPATIBILIDADE_IMPACTO.md)'
\echo '2. Testar funcionalidades críticas'
\echo '3. Monitorar performance por 24h'
\echo '4. Verificar logs de erro'
