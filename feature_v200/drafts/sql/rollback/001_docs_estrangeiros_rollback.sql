-- ================================================
-- ROLLBACK 001: DOCUMENTOS ESTRANGEIROS
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Reverter migration de documentos estrangeiros
-- ================================================

-- ATENÇÃO: Este script remove as alterações da migration 001
-- Use apenas se necessário reverter para versão anterior

BEGIN;

-- ================================================
-- PARTE 1: REMOVER TRIGGERS
-- ================================================

DROP TRIGGER IF EXISTS trg_visitantes_sync_cpf ON visitantes;
DROP TRIGGER IF EXISTS trg_prestadores_sync_cpf ON prestadores_servico;
DROP TRIGGER IF EXISTS trg_profissionais_sync_cpf ON profissionais_renner;
DROP TRIGGER IF EXISTS trg_registro_sync_cpf ON registro_acesso;

DROP FUNCTION IF EXISTS sync_cpf_doc_number();

-- ================================================
-- PARTE 2: REMOVER CONSTRAINTS
-- ================================================

ALTER TABLE visitantes DROP CONSTRAINT IF EXISTS chk_visitantes_doc_consistency;
ALTER TABLE prestadores_servico DROP CONSTRAINT IF EXISTS chk_prestadores_doc_consistency;
ALTER TABLE profissionais_renner DROP CONSTRAINT IF EXISTS chk_profissionais_doc_consistency;
ALTER TABLE registro_acesso DROP CONSTRAINT IF EXISTS chk_registro_doc_consistency;

-- ================================================
-- PARTE 3: REMOVER ÍNDICES
-- ================================================

DROP INDEX IF EXISTS idx_visitantes_doc_type;
DROP INDEX IF EXISTS idx_visitantes_doc_number;
DROP INDEX IF EXISTS idx_visitantes_doc_country;
DROP INDEX IF EXISTS idx_visitantes_validity_status;
DROP INDEX IF EXISTS idx_visitantes_valid_dates;

DROP INDEX IF EXISTS idx_prestadores_doc_type;
DROP INDEX IF EXISTS idx_prestadores_doc_number;
DROP INDEX IF EXISTS idx_prestadores_doc_country;
DROP INDEX IF EXISTS idx_prestadores_validity_status;
DROP INDEX IF EXISTS idx_prestadores_valid_dates;

DROP INDEX IF EXISTS idx_profissionais_doc_type;
DROP INDEX IF EXISTS idx_profissionais_doc_number;
DROP INDEX IF EXISTS idx_profissionais_doc_country;

DROP INDEX IF EXISTS idx_registro_doc_type;
DROP INDEX IF EXISTS idx_registro_doc_number;
DROP INDEX IF EXISTS idx_registro_doc_country;

-- ================================================
-- PARTE 4: REMOVER COLUNAS NOVAS
-- ================================================

-- Visitantes
ALTER TABLE visitantes
    DROP COLUMN IF EXISTS doc_type,
    DROP COLUMN IF EXISTS doc_number,
    DROP COLUMN IF EXISTS doc_country,
    DROP COLUMN IF EXISTS valid_from,
    DROP COLUMN IF EXISTS validity_status;

-- Prestadores
ALTER TABLE prestadores_servico
    DROP COLUMN IF EXISTS doc_type,
    DROP COLUMN IF EXISTS doc_number,
    DROP COLUMN IF EXISTS doc_country,
    DROP COLUMN IF EXISTS valid_from,
    DROP COLUMN IF EXISTS valid_until,
    DROP COLUMN IF EXISTS validity_status;

-- Profissionais
ALTER TABLE profissionais_renner
    DROP COLUMN IF EXISTS doc_type,
    DROP COLUMN IF EXISTS doc_number,
    DROP COLUMN IF EXISTS doc_country;

-- Registro Acesso
ALTER TABLE registro_acesso
    DROP COLUMN IF EXISTS doc_type,
    DROP COLUMN IF EXISTS doc_number,
    DROP COLUMN IF EXISTS doc_country;

-- ================================================
-- PARTE 5: REMOVER TIPO ENUM
-- ================================================

DROP TYPE IF EXISTS document_type CASCADE;

COMMIT;

-- Rollback concluído
SELECT 'Rollback 001 concluído com sucesso!' AS status;
