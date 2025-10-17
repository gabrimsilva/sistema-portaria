-- ============================================================================
-- M2 - MIGRATION 003: Criar Índices de Performance
-- ============================================================================
-- Data: 17/10/2025
-- Objetivo: Otimizar queries de busca, filtragem e relatórios
-- Status: DRAFT - NÃO EXECUTAR SEM APROVAÇÃO
-- ============================================================================

-- ============================================================================
-- ÍNDICES: visitantes_cadastro
-- ============================================================================

-- 1. Busca por Documento (CPF, RG, Passaporte, etc.)
-- USO: Dashboard autocomplete, validação de duplicidade
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_doc 
    ON visitantes_cadastro(doc_type, doc_number) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 2. Busca por Nome (autocomplete)
-- USO: Dashboard autocomplete "Digite o nome do visitante"
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_nome 
    ON visitantes_cadastro(nome) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 3. Filtro por Validade (expirados, expirando, válidos)
-- USO: Relatórios de cadastros expirados, dashboard stats
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_validade 
    ON visitantes_cadastro(valid_until) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 4. Filtro por Empresa (listagens)
-- USO: Filtro "Visitantes da empresa X"
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_empresa 
    ON visitantes_cadastro(empresa) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 5. Busca por Placa (se precisar filtrar veículos)
-- USO: "Quem chegou com placa ABC1234?"
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_placa 
    ON visitantes_cadastro(placa_veiculo) 
    WHERE placa_veiculo IS NOT NULL 
      AND deleted_at IS NULL 
      AND ativo = true;

-- 6. Status Ativo (queries frequentes)
-- USO: Listar apenas cadastros ativos
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_ativo 
    ON visitantes_cadastro(ativo, created_at DESC) 
    WHERE deleted_at IS NULL;

-- ============================================================================
-- ÍNDICES: visitantes_registros
-- ============================================================================

-- 1. FK para Cadastro (JOIN principal)
-- USO: Buscar todas as entradas de um visitante específico
CREATE INDEX IF NOT EXISTS idx_visitantes_registros_cadastro 
    ON visitantes_registros(cadastro_id) 
    WHERE deleted_at IS NULL;

-- 2. Filtro por Data de Entrada (relatórios)
-- USO: "Visitantes que entraram hoje", "Entradas de Outubro"
CREATE INDEX IF NOT EXISTS idx_visitantes_registros_entrada 
    ON visitantes_registros(entrada DESC) 
    WHERE deleted_at IS NULL;

-- 3. Visitantes Presentes Agora (saída = NULL)
-- USO: Dashboard "Pessoas na Empresa" (sem saída registrada)
CREATE INDEX IF NOT EXISTS idx_visitantes_registros_presentes 
    ON visitantes_registros(cadastro_id, entrada) 
    WHERE saida IS NULL AND deleted_at IS NULL;

-- 4. Composite: Cadastro + Entrada (queries complexas)
-- USO: Histórico completo ordenado por data
CREATE INDEX IF NOT EXISTS idx_visitantes_registros_cadastro_entrada 
    ON visitantes_registros(cadastro_id, entrada DESC) 
    WHERE deleted_at IS NULL;

-- ============================================================================
-- ÍNDICES: prestadores_cadastro
-- ============================================================================

-- 1. Busca por Documento
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_doc 
    ON prestadores_cadastro(doc_type, doc_number) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 2. Busca por Nome
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_nome 
    ON prestadores_cadastro(nome) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 3. Filtro por Validade
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_validade 
    ON prestadores_cadastro(valid_until) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 4. Filtro por Empresa
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_empresa 
    ON prestadores_cadastro(empresa) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 5. Busca por Placa
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_placa 
    ON prestadores_cadastro(placa_veiculo) 
    WHERE placa_veiculo IS NOT NULL 
      AND deleted_at IS NULL 
      AND ativo = true;

-- 6. Status Ativo
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_ativo 
    ON prestadores_cadastro(ativo, created_at DESC) 
    WHERE deleted_at IS NULL;

-- ============================================================================
-- ÍNDICES: prestadores_registros
-- ============================================================================

-- 1. FK para Cadastro
CREATE INDEX IF NOT EXISTS idx_prestadores_registros_cadastro 
    ON prestadores_registros(cadastro_id) 
    WHERE deleted_at IS NULL;

-- 2. Filtro por Data de Entrada
CREATE INDEX IF NOT EXISTS idx_prestadores_registros_entrada 
    ON prestadores_registros(entrada DESC) 
    WHERE deleted_at IS NULL;

-- 3. Prestadores Presentes Agora
CREATE INDEX IF NOT EXISTS idx_prestadores_registros_presentes 
    ON prestadores_registros(cadastro_id, entrada) 
    WHERE saida IS NULL AND deleted_at IS NULL;

-- 4. Composite: Cadastro + Entrada
CREATE INDEX IF NOT EXISTS idx_prestadores_registros_cadastro_entrada 
    ON prestadores_registros(cadastro_id, entrada DESC) 
    WHERE deleted_at IS NULL;

-- ============================================================================
-- ÍNDICES FULL-TEXT SEARCH (opcional, para buscas avançadas)
-- ============================================================================

-- 1. Busca Full-Text em Nome de Visitantes
-- USO: Busca "João" encontra "João Silva", "João Pedro", etc.
CREATE INDEX IF NOT EXISTS idx_visitantes_cadastro_nome_fts 
    ON visitantes_cadastro 
    USING gin(to_tsvector('portuguese', nome)) 
    WHERE deleted_at IS NULL AND ativo = true;

-- 2. Busca Full-Text em Nome de Prestadores
CREATE INDEX IF NOT EXISTS idx_prestadores_cadastro_nome_fts 
    ON prestadores_cadastro 
    USING gin(to_tsvector('portuguese', nome)) 
    WHERE deleted_at IS NULL AND ativo = true;

-- ============================================================================
-- ESTATÍSTICAS DE ÍNDICES
-- ============================================================================

-- Atualizar estatísticas para o otimizador de queries
ANALYZE visitantes_cadastro;
ANALYZE visitantes_registros;
ANALYZE prestadores_cadastro;
ANALYZE prestadores_registros;

-- ============================================================================
-- QUERY DE VERIFICAÇÃO (executar após aplicar)
-- ============================================================================

/*
-- Verificar índices criados:
SELECT 
    tablename, 
    indexname, 
    indexdef 
FROM pg_indexes 
WHERE tablename IN (
    'visitantes_cadastro', 
    'visitantes_registros',
    'prestadores_cadastro',
    'prestadores_registros'
)
ORDER BY tablename, indexname;

-- Verificar tamanho dos índices:
SELECT 
    schemaname,
    tablename,
    indexname,
    pg_size_pretty(pg_relation_size(indexname::regclass)) AS index_size
FROM pg_indexes
WHERE tablename IN (
    'visitantes_cadastro', 
    'visitantes_registros',
    'prestadores_cadastro',
    'prestadores_registros'
)
ORDER BY pg_relation_size(indexname::regclass) DESC;
*/

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
-- IMPORTANTE: Este script está em DRAFT e NÃO deve ser executado sem aprovação
-- Total de índices criados: 24
-- Impacto estimado: +10-50 MB de armazenamento, -80% tempo de busca
-- ============================================================================
