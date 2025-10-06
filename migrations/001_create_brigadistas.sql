-- =====================================================
-- MIGRATION: Criar tabela BRIGADISTAS
-- Data: 2025-10-06
-- Descrição: Tabela para gerenciar brigadistas de incêndio
--            vinculados aos profissionais Renner
-- =====================================================

-- 1) Criar extensão unaccent (se não existir) para busca sem acentos
CREATE EXTENSION IF NOT EXISTS unaccent;

-- 2) Criar tabela brigadistas
CREATE TABLE IF NOT EXISTS public.brigadistas (
    id              BIGSERIAL PRIMARY KEY,
    professional_id BIGINT NOT NULL UNIQUE,
    active          BOOLEAN NOT NULL DEFAULT TRUE,
    note            TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- Foreign Key para profissionais_renner
    CONSTRAINT fk_brigadista_profissional 
        FOREIGN KEY (professional_id) 
        REFERENCES public.profissionais_renner(id) 
        ON DELETE CASCADE
);

-- 3) Criar índices para performance
CREATE INDEX IF NOT EXISTS idx_brigadistas_active 
    ON public.brigadistas(active);

CREATE INDEX IF NOT EXISTS idx_brigadistas_professional_id 
    ON public.brigadistas(professional_id);

-- 4) Criar função para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION public.update_brigadistas_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 5) Criar trigger para updated_at
DROP TRIGGER IF EXISTS trg_brigadistas_updated_at ON public.brigadistas;
CREATE TRIGGER trg_brigadistas_updated_at
    BEFORE UPDATE ON public.brigadistas
    FOR EACH ROW
    EXECUTE FUNCTION public.update_brigadistas_updated_at();

-- 6) Comentários nas tabelas e colunas (documentação)
COMMENT ON TABLE public.brigadistas IS 'Tabela de brigadistas de incêndio vinculados aos profissionais Renner';
COMMENT ON COLUMN public.brigadistas.id IS 'ID único do brigadista';
COMMENT ON COLUMN public.brigadistas.professional_id IS 'ID do profissional Renner (FK, UNIQUE - um profissional só pode ser brigadista uma vez)';
COMMENT ON COLUMN public.brigadistas.active IS 'Status do brigadista (ativo/inativo)';
COMMENT ON COLUMN public.brigadistas.note IS 'Observações sobre o brigadista';
COMMENT ON COLUMN public.brigadistas.created_at IS 'Data de inclusão na brigada';
COMMENT ON COLUMN public.brigadistas.updated_at IS 'Data da última atualização (atualizado automaticamente por trigger)';

-- =====================================================
-- FIM DA MIGRATION
-- =====================================================
