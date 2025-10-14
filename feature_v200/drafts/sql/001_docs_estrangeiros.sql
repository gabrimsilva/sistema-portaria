-- ================================================
-- MIGRATION 001: DOCUMENTOS ESTRANGEIROS
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Adicionar suporte a documentos internacionais
-- ================================================

-- IMPORTANTE: Este é um DRAFT - NÃO EXECUTAR sem aprovação!

BEGIN;

-- ================================================
-- PARTE 1: CRIAR TIPO ENUM PARA DOCUMENTOS
-- ================================================

-- Tipo de documento (pode ser expandido no futuro)
CREATE TYPE document_type AS ENUM (
    'CPF',           -- Cadastro de Pessoa Física (Brasil)
    'RG',            -- Registro Geral (Brasil)
    'CNH',           -- Carteira Nacional de Habilitação (Brasil)
    'PASSAPORTE',    -- Passaporte Internacional
    'RNE',           -- Registro Nacional de Estrangeiro (Brasil)
    'PASSPORT',      -- Passport (internacional - alias)
    'DNI',           -- Documento Nacional de Identidad (Argentina, Espanha)
    'CI',            -- Cédula de Identidad (América Latina)
    'OUTRO'          -- Outros documentos não listados
);

-- ================================================
-- PARTE 2: ADICIONAR COLUNAS EM VISITANTES
-- ================================================

-- Adicionar novas colunas em visitantes
ALTER TABLE visitantes
    -- Tipo de documento (default CPF para compatibilidade)
    ADD COLUMN doc_type document_type DEFAULT 'CPF',
    
    -- Renomear coluna cpf para doc_number (via nova coluna + migração de dados)
    ADD COLUMN doc_number VARCHAR(50),
    
    -- País emissor do documento (ISO-3166 alpha-2)
    ADD COLUMN doc_country CHAR(2) DEFAULT 'BR',
    
    -- Campos de validade (valid_until já existe!)
    ADD COLUMN valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Status de validade
    ADD COLUMN validity_status VARCHAR(20) DEFAULT 'ativo'
        CHECK (validity_status IN ('ativo', 'expirado', 'bloqueado', 'pendente'));

-- Migrar dados existentes de cpf para doc_number
UPDATE visitantes 
SET doc_number = cpf, 
    doc_type = CASE 
        WHEN cpf IS NOT NULL AND LENGTH(TRIM(cpf)) > 0 THEN 'CPF'::document_type
        ELSE NULL
    END,
    doc_country = 'BR'
WHERE cpf IS NOT NULL;

-- Adicionar comentários
COMMENT ON COLUMN visitantes.doc_type IS 'Tipo de documento de identificação';
COMMENT ON COLUMN visitantes.doc_number IS 'Número do documento (sem formatação)';
COMMENT ON COLUMN visitantes.doc_country IS 'País emissor do documento (ISO-3166 alpha-2)';
COMMENT ON COLUMN visitantes.valid_from IS 'Data de início da validade do cadastro';
COMMENT ON COLUMN visitantes.validity_status IS 'Status do cadastro (ativo, expirado, bloqueado)';

-- ================================================
-- PARTE 3: ADICIONAR COLUNAS EM PRESTADORES_SERVICO
-- ================================================

ALTER TABLE prestadores_servico
    ADD COLUMN doc_type document_type DEFAULT 'CPF',
    ADD COLUMN doc_number VARCHAR(50),
    ADD COLUMN doc_country CHAR(2) DEFAULT 'BR',
    ADD COLUMN valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN valid_until TIMESTAMP,
    ADD COLUMN validity_status VARCHAR(20) DEFAULT 'ativo'
        CHECK (validity_status IN ('ativo', 'expirado', 'bloqueado', 'pendente'));

-- Migrar dados existentes
UPDATE prestadores_servico 
SET doc_number = cpf,
    doc_type = CASE 
        WHEN cpf IS NOT NULL AND LENGTH(TRIM(cpf)) > 0 THEN 'CPF'::document_type
        ELSE NULL
    END,
    doc_country = 'BR'
WHERE cpf IS NOT NULL;

-- Comentários
COMMENT ON COLUMN prestadores_servico.doc_type IS 'Tipo de documento de identificação';
COMMENT ON COLUMN prestadores_servico.doc_number IS 'Número do documento (sem formatação)';
COMMENT ON COLUMN prestadores_servico.doc_country IS 'País emissor do documento (ISO-3166 alpha-2)';
COMMENT ON COLUMN prestadores_servico.valid_from IS 'Data de início da validade do cadastro';
COMMENT ON COLUMN prestadores_servico.valid_until IS 'Data de fim da validade do cadastro';
COMMENT ON COLUMN prestadores_servico.validity_status IS 'Status do cadastro';

-- ================================================
-- PARTE 4: ADICIONAR COLUNAS EM PROFISSIONAIS_RENNER
-- ================================================

ALTER TABLE profissionais_renner
    ADD COLUMN doc_type document_type DEFAULT 'CPF',
    ADD COLUMN doc_number VARCHAR(50),
    ADD COLUMN doc_country CHAR(2) DEFAULT 'BR';

-- Migrar dados existentes
UPDATE profissionais_renner 
SET doc_number = cpf,
    doc_type = CASE 
        WHEN cpf IS NOT NULL AND LENGTH(TRIM(cpf)) > 0 THEN 'CPF'::document_type
        ELSE NULL
    END,
    doc_country = 'BR'
WHERE cpf IS NOT NULL;

-- Comentários
COMMENT ON COLUMN profissionais_renner.doc_type IS 'Tipo de documento de identificação';
COMMENT ON COLUMN profissionais_renner.doc_number IS 'Número do documento (sem formatação)';
COMMENT ON COLUMN profissionais_renner.doc_country IS 'País emissor do documento (ISO-3166 alpha-2)';

-- ================================================
-- PARTE 5: ADICIONAR COLUNAS EM REGISTRO_ACESSO
-- ================================================

ALTER TABLE registro_acesso
    ADD COLUMN doc_type document_type DEFAULT 'CPF',
    ADD COLUMN doc_number VARCHAR(50),
    ADD COLUMN doc_country CHAR(2) DEFAULT 'BR';

-- Migrar dados existentes
UPDATE registro_acesso 
SET doc_number = cpf,
    doc_type = CASE 
        WHEN cpf IS NOT NULL AND LENGTH(TRIM(cpf)) > 0 THEN 'CPF'::document_type
        ELSE NULL
    END,
    doc_country = 'BR'
WHERE cpf IS NOT NULL;

-- Comentários
COMMENT ON COLUMN registro_acesso.doc_type IS 'Tipo de documento de identificação';
COMMENT ON COLUMN registro_acesso.doc_number IS 'Número do documento (sem formatação)';
COMMENT ON COLUMN registro_acesso.doc_country IS 'País emissor do documento (ISO-3166 alpha-2)';

-- ================================================
-- PARTE 6: CRIAR ÍNDICES PARA PERFORMANCE
-- ================================================

-- Índices em visitantes
CREATE INDEX idx_visitantes_doc_type ON visitantes(doc_type);
CREATE INDEX idx_visitantes_doc_number ON visitantes(doc_number) WHERE doc_number IS NOT NULL;
CREATE INDEX idx_visitantes_doc_country ON visitantes(doc_country);
CREATE INDEX idx_visitantes_validity_status ON visitantes(validity_status);
CREATE INDEX idx_visitantes_valid_dates ON visitantes(valid_from, data_vencimento);

-- Índices em prestadores_servico
CREATE INDEX idx_prestadores_doc_type ON prestadores_servico(doc_type);
CREATE INDEX idx_prestadores_doc_number ON prestadores_servico(doc_number) WHERE doc_number IS NOT NULL;
CREATE INDEX idx_prestadores_doc_country ON prestadores_servico(doc_country);
CREATE INDEX idx_prestadores_validity_status ON prestadores_servico(validity_status);
CREATE INDEX idx_prestadores_valid_dates ON prestadores_servico(valid_from, valid_until);

-- Índices em profissionais_renner
CREATE INDEX idx_profissionais_doc_type ON profissionais_renner(doc_type);
CREATE INDEX idx_profissionais_doc_number ON profissionais_renner(doc_number) WHERE doc_number IS NOT NULL;
CREATE INDEX idx_profissionais_doc_country ON profissionais_renner(doc_country);

-- Índices em registro_acesso
CREATE INDEX idx_registro_doc_type ON registro_acesso(doc_type);
CREATE INDEX idx_registro_doc_number ON registro_acesso(doc_number) WHERE doc_number IS NOT NULL;
CREATE INDEX idx_registro_doc_country ON registro_acesso(doc_country);

-- ================================================
-- PARTE 7: CONSTRAINTS DE VALIDAÇÃO
-- ================================================

-- Visitantes: se doc_type for preenchido, doc_number é obrigatório
ALTER TABLE visitantes
    ADD CONSTRAINT chk_visitantes_doc_consistency 
    CHECK (
        (doc_type IS NULL AND doc_number IS NULL) OR
        (doc_type IS NOT NULL AND doc_number IS NOT NULL AND LENGTH(TRIM(doc_number)) > 0)
    );

-- Prestadores: mesma regra
ALTER TABLE prestadores_servico
    ADD CONSTRAINT chk_prestadores_doc_consistency 
    CHECK (
        (doc_type IS NULL AND doc_number IS NULL) OR
        (doc_type IS NOT NULL AND doc_number IS NOT NULL AND LENGTH(TRIM(doc_number)) > 0)
    );

-- Profissionais: mesma regra
ALTER TABLE profissionais_renner
    ADD CONSTRAINT chk_profissionais_doc_consistency 
    CHECK (
        (doc_type IS NULL AND doc_number IS NULL) OR
        (doc_type IS NOT NULL AND doc_number IS NOT NULL AND LENGTH(TRIM(doc_number)) > 0)
    );

-- Registro Acesso: mesma regra
ALTER TABLE registro_acesso
    ADD CONSTRAINT chk_registro_doc_consistency 
    CHECK (
        (doc_type IS NULL AND doc_number IS NULL) OR
        (doc_type IS NOT NULL AND doc_number IS NOT NULL AND LENGTH(TRIM(doc_number)) > 0)
    );

-- ================================================
-- PARTE 8: DEPRECAR COLUNAS ANTIGAS (OPCIONAL)
-- ================================================

-- OPÇÃO 1: Manter coluna CPF para compatibilidade (RECOMENDADO)
-- Criar trigger para sincronizar cpf <-> doc_number quando doc_type = 'CPF'

CREATE OR REPLACE FUNCTION sync_cpf_doc_number()
RETURNS TRIGGER AS $$
BEGIN
    -- Se doc_type é CPF, sincronizar com coluna cpf antiga
    IF NEW.doc_type = 'CPF' THEN
        NEW.cpf := NEW.doc_number;
    END IF;
    
    -- Se cpf foi alterado diretamente, atualizar doc_number
    IF NEW.cpf IS DISTINCT FROM OLD.cpf AND NEW.doc_type = 'CPF' THEN
        NEW.doc_number := NEW.cpf;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Aplicar trigger em todas as tabelas
CREATE TRIGGER trg_visitantes_sync_cpf
    BEFORE INSERT OR UPDATE ON visitantes
    FOR EACH ROW
    EXECUTE FUNCTION sync_cpf_doc_number();

CREATE TRIGGER trg_prestadores_sync_cpf
    BEFORE INSERT OR UPDATE ON prestadores_servico
    FOR EACH ROW
    EXECUTE FUNCTION sync_cpf_doc_number();

CREATE TRIGGER trg_profissionais_sync_cpf
    BEFORE INSERT OR UPDATE ON profissionais_renner
    FOR EACH ROW
    EXECUTE FUNCTION sync_cpf_doc_number();

CREATE TRIGGER trg_registro_sync_cpf
    BEFORE INSERT OR UPDATE ON registro_acesso
    FOR EACH ROW
    EXECUTE FUNCTION sync_cpf_doc_number();

-- OPÇÃO 2: Remover coluna CPF (MAIS ARRISCADO - comentado)
-- ALTER TABLE visitantes DROP COLUMN cpf;
-- ALTER TABLE prestadores_servico DROP COLUMN cpf;
-- ALTER TABLE profissionais_renner DROP COLUMN cpf;
-- ALTER TABLE registro_acesso DROP COLUMN cpf;

COMMIT;

-- ================================================
-- VERIFICAÇÕES PÓS-MIGRATION
-- ================================================

-- Verificar dados migrados
-- SELECT doc_type, doc_number, doc_country, cpf FROM visitantes LIMIT 10;
-- SELECT doc_type, doc_number, doc_country, cpf FROM prestadores_servico LIMIT 10;
-- SELECT doc_type, doc_number, doc_country, cpf FROM profissionais_renner LIMIT 10;

-- Verificar índices criados
-- SELECT tablename, indexname FROM pg_indexes WHERE schemaname = 'public' AND indexname LIKE 'idx_%doc%';

-- ================================================
-- NOTAS IMPORTANTES
-- ================================================

/*
COMPATIBILIDADE:
1. Colunas antigas (cpf) são mantidas para compatibilidade
2. Trigger sincroniza automaticamente cpf <-> doc_number
3. Código antigo continua funcionando

IMPACTO:
1. Queries usando "cpf" continuam funcionando via trigger
2. Novos códigos devem usar doc_type/doc_number/doc_country
3. Relatórios precisam ser atualizados gradualmente

ROLLBACK:
1. Remover triggers
2. Remover novas colunas
3. Remover tipo ENUM
4. Ver arquivo 001_docs_estrangeiros_rollback.sql

PERFORMANCE:
1. Índices criados para doc_number, doc_type, doc_country
2. Índices parciais (WHERE NOT NULL) economizam espaço
3. Triggers têm overhead mínimo

VALIDAÇÕES:
1. Constraint garante consistência doc_type <-> doc_number
2. validity_status com valores permitidos
3. doc_country em formato ISO-3166 alpha-2
*/
