-- ============================================================================
-- M2 - MIGRATION 001: Criar Tabelas de Pré-Cadastros
-- ============================================================================
-- Data: 17/10/2025
-- Objetivo: Separar pré-cadastro (dados de 1 ano) de registros de acesso
-- Status: DRAFT - NÃO EXECUTAR SEM APROVAÇÃO
-- ============================================================================

-- ============================================================================
-- 1. VISITANTES - PRÉ-CADASTRO
-- ============================================================================
CREATE TABLE IF NOT EXISTS visitantes_cadastro (
    -- Identificação
    id SERIAL PRIMARY KEY,
    
    -- Dados Pessoais
    nome VARCHAR(255) NOT NULL,
    empresa VARCHAR(255),
    
    -- Documento (Sistema Multi-Documentos)
    doc_type VARCHAR(20) DEFAULT 'CPF',
    doc_number VARCHAR(50) NOT NULL,
    doc_country VARCHAR(100) DEFAULT 'Brasil',
    
    -- Veículo (opcional)
    placa_veiculo VARCHAR(20),
    
    -- Período de Validade (1 ano padrão)
    valid_from DATE NOT NULL DEFAULT CURRENT_DATE,
    valid_until DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '1 year'),
    
    -- Observações gerais do cadastro
    observacoes TEXT,
    
    -- Status e Metadata
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- LGPD: Soft Delete e Anonimização
    deleted_at TIMESTAMPTZ,
    deletion_reason TEXT,
    anonymized_at TIMESTAMPTZ,
    
    -- Constraints
    CONSTRAINT chk_visitantes_cadastro_doc_consistency 
        CHECK (
            (doc_type IS NULL AND doc_number IS NULL) OR 
            (doc_type IS NOT NULL AND doc_number IS NOT NULL)
        ),
    CONSTRAINT chk_visitantes_cadastro_validade 
        CHECK (valid_until > valid_from),
    
    -- Unicidade: mesmo documento não pode ter 2 cadastros ativos
    CONSTRAINT uq_visitantes_cadastro_doc 
        UNIQUE (doc_type, doc_number, ativo)
);

-- Comentários
COMMENT ON TABLE visitantes_cadastro IS 'Pré-cadastros de visitantes com validade de 1 ano (reutilizáveis em múltiplas entradas)';
COMMENT ON COLUMN visitantes_cadastro.valid_from IS 'Data de início da validade do cadastro';
COMMENT ON COLUMN visitantes_cadastro.valid_until IS 'Data de fim da validade (padrão: +1 ano)';
COMMENT ON COLUMN visitantes_cadastro.ativo IS 'Se false, cadastro desativado (soft delete)';

-- ============================================================================
-- 2. VISITANTES - REGISTROS DE ACESSO
-- ============================================================================
CREATE TABLE IF NOT EXISTS visitantes_registros (
    -- Identificação
    id SERIAL PRIMARY KEY,
    
    -- Vínculo com pré-cadastro (FK)
    cadastro_id INTEGER NOT NULL REFERENCES visitantes_cadastro(id) ON DELETE RESTRICT,
    
    -- Evento de Acesso
    entrada TIMESTAMPTZ NOT NULL,
    saida TIMESTAMPTZ,
    
    -- Dados Específicos desta Entrada
    funcionario_responsavel VARCHAR(255),
    setor VARCHAR(255),
    observacoes_entrada TEXT,
    
    -- Metadata
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- LGPD: Soft Delete
    deleted_at TIMESTAMPTZ,
    
    -- Constraints
    CONSTRAINT chk_visitantes_registros_saida 
        CHECK (saida IS NULL OR saida >= entrada)
);

-- Comentários
COMMENT ON TABLE visitantes_registros IS 'Registros de entradas/saídas de visitantes (eventos pontuais vinculados a pré-cadastros)';
COMMENT ON COLUMN visitantes_registros.cadastro_id IS 'Referência ao pré-cadastro do visitante';
COMMENT ON COLUMN visitantes_registros.entrada IS 'Data/hora da entrada na empresa';
COMMENT ON COLUMN visitantes_registros.saida IS 'Data/hora da saída (NULL se ainda presente)';

-- ============================================================================
-- 3. PRESTADORES - PRÉ-CADASTRO
-- ============================================================================
CREATE TABLE IF NOT EXISTS prestadores_cadastro (
    -- Identificação
    id SERIAL PRIMARY KEY,
    
    -- Dados Pessoais
    nome VARCHAR(255) NOT NULL,
    empresa VARCHAR(255),
    
    -- Documento (Sistema Multi-Documentos)
    doc_type VARCHAR(20) DEFAULT 'CPF',
    doc_number VARCHAR(50) NOT NULL,
    doc_country VARCHAR(100) DEFAULT 'Brasil',
    
    -- Veículo (opcional)
    placa_veiculo VARCHAR(20),
    
    -- Período de Validade (1 ano padrão)
    valid_from DATE NOT NULL DEFAULT CURRENT_DATE,
    valid_until DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '1 year'),
    
    -- Observações gerais do cadastro
    observacoes TEXT,
    
    -- Status e Metadata
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- LGPD: Soft Delete e Anonimização
    deleted_at TIMESTAMPTZ,
    deletion_reason TEXT,
    anonymized_at TIMESTAMPTZ,
    
    -- Constraints
    CONSTRAINT chk_prestadores_cadastro_doc_consistency 
        CHECK (
            (doc_type IS NULL AND doc_number IS NULL) OR 
            (doc_type IS NOT NULL AND doc_number IS NOT NULL)
        ),
    CONSTRAINT chk_prestadores_cadastro_validade 
        CHECK (valid_until > valid_from),
    
    -- Unicidade: mesmo documento não pode ter 2 cadastros ativos
    CONSTRAINT uq_prestadores_cadastro_doc 
        UNIQUE (doc_type, doc_number, ativo)
);

-- Comentários
COMMENT ON TABLE prestadores_cadastro IS 'Pré-cadastros de prestadores de serviço com validade de 1 ano (reutilizáveis)';
COMMENT ON COLUMN prestadores_cadastro.valid_from IS 'Data de início da validade do cadastro';
COMMENT ON COLUMN prestadores_cadastro.valid_until IS 'Data de fim da validade (padrão: +1 ano)';

-- ============================================================================
-- 4. PRESTADORES - REGISTROS DE ACESSO
-- ============================================================================
CREATE TABLE IF NOT EXISTS prestadores_registros (
    -- Identificação
    id SERIAL PRIMARY KEY,
    
    -- Vínculo com pré-cadastro (FK)
    cadastro_id INTEGER NOT NULL REFERENCES prestadores_cadastro(id) ON DELETE RESTRICT,
    
    -- Evento de Acesso
    entrada TIMESTAMPTZ NOT NULL,
    saida TIMESTAMPTZ,
    
    -- Dados Específicos desta Entrada
    funcionario_responsavel VARCHAR(255),
    setor VARCHAR(255),
    observacoes_entrada TEXT,
    
    -- Metadata
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    -- LGPD: Soft Delete
    deleted_at TIMESTAMPTZ,
    
    -- Constraints
    CONSTRAINT chk_prestadores_registros_saida 
        CHECK (saida IS NULL OR saida >= entrada)
);

-- Comentários
COMMENT ON TABLE prestadores_registros IS 'Registros de entradas/saídas de prestadores (eventos pontuais vinculados a pré-cadastros)';
COMMENT ON COLUMN prestadores_registros.cadastro_id IS 'Referência ao pré-cadastro do prestador';

-- ============================================================================
-- TRIGGER: Atualizar updated_at automaticamente
-- ============================================================================

-- Função genérica
CREATE OR REPLACE FUNCTION trigger_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Aplicar nos cadastros
CREATE TRIGGER set_timestamp_visitantes_cadastro
    BEFORE UPDATE ON visitantes_cadastro
    FOR EACH ROW
    EXECUTE FUNCTION trigger_set_timestamp();

CREATE TRIGGER set_timestamp_prestadores_cadastro
    BEFORE UPDATE ON prestadores_cadastro
    FOR EACH ROW
    EXECUTE FUNCTION trigger_set_timestamp();

CREATE TRIGGER set_timestamp_visitantes_registros
    BEFORE UPDATE ON visitantes_registros
    FOR EACH ROW
    EXECUTE FUNCTION trigger_set_timestamp();

CREATE TRIGGER set_timestamp_prestadores_registros
    BEFORE UPDATE ON prestadores_registros
    FOR EACH ROW
    EXECUTE FUNCTION trigger_set_timestamp();

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
-- IMPORTANTE: Este script está em DRAFT e NÃO deve ser executado sem aprovação
-- Próximo: 002_create_views_status.sql (Views para status derivado)
-- ============================================================================
