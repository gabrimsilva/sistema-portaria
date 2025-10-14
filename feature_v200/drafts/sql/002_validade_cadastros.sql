-- ================================================
-- MIGRATION 002: VALIDADE DE CADASTROS
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Gerenciar período de validade para cadastros recorrentes
-- ================================================

-- IMPORTANTE: Este é um DRAFT - NÃO EXECUTAR sem aprovação!

BEGIN;

-- ================================================
-- PARTE 1: ATUALIZAR VISITANTES (JÁ TEM data_vencimento!)
-- ================================================

-- Visitantes já tem data_vencimento, só precisa de valid_from e status
-- (valid_from e validity_status já foram adicionados na migration 001)

-- Atualizar registros existentes sem data_vencimento
UPDATE visitantes 
SET data_vencimento = valid_from + INTERVAL '90 days'
WHERE data_vencimento IS NULL AND valid_from IS NOT NULL;

-- Criar função para calcular status baseado nas datas
CREATE OR REPLACE FUNCTION calculate_validity_status(
    p_valid_from TIMESTAMP,
    p_valid_until TIMESTAMP,
    p_current_status VARCHAR
) RETURNS VARCHAR AS $$
DECLARE
    v_now TIMESTAMP := CURRENT_TIMESTAMP;
BEGIN
    -- Se bloqueado manualmente, manter bloqueado
    IF p_current_status = 'bloqueado' THEN
        RETURN 'bloqueado';
    END IF;
    
    -- Se ainda não iniciou
    IF p_valid_from IS NOT NULL AND v_now < p_valid_from THEN
        RETURN 'pendente';
    END IF;
    
    -- Se expirado
    IF p_valid_until IS NOT NULL AND v_now > p_valid_until THEN
        RETURN 'expirado';
    END IF;
    
    -- Se ativo
    IF p_valid_from IS NOT NULL AND (p_valid_until IS NULL OR v_now <= p_valid_until) THEN
        RETURN 'ativo';
    END IF;
    
    -- Default
    RETURN 'ativo';
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- ================================================
-- PARTE 2: PRESTADORES_SERVICO (ADICIONAR LÓGICA)
-- ================================================

-- Campos já foram adicionados na migration 001
-- Agora vamos adicionar lógica de atualização automática

-- Atualizar registros existentes sem validade definida
-- Por padrão: 30 dias de validade para prestadores
UPDATE prestadores_servico 
SET valid_from = COALESCE(created_at, entrada, CURRENT_TIMESTAMP),
    valid_until = COALESCE(created_at, entrada, CURRENT_TIMESTAMP) + INTERVAL '30 days'
WHERE valid_from IS NULL;

-- ================================================
-- PARTE 3: TRIGGER PARA AUTO-ATUALIZAR STATUS
-- ================================================

-- Trigger para visitantes
CREATE OR REPLACE FUNCTION update_validity_status_visitantes()
RETURNS TRIGGER AS $$
BEGIN
    NEW.validity_status := calculate_validity_status(
        NEW.valid_from,
        NEW.data_vencimento, -- visitantes usa data_vencimento
        NEW.validity_status
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_visitantes_validity_status
    BEFORE INSERT OR UPDATE OF valid_from, data_vencimento, validity_status
    ON visitantes
    FOR EACH ROW
    EXECUTE FUNCTION update_validity_status_visitantes();

-- Trigger para prestadores
CREATE OR REPLACE FUNCTION update_validity_status_prestadores()
RETURNS TRIGGER AS $$
BEGIN
    NEW.validity_status := calculate_validity_status(
        NEW.valid_from,
        NEW.valid_until,
        NEW.validity_status
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_prestadores_validity_status
    BEFORE INSERT OR UPDATE OF valid_from, valid_until, validity_status
    ON prestadores_servico
    FOR EACH ROW
    EXECUTE FUNCTION update_validity_status_prestadores();

-- ================================================
-- PARTE 4: ÍNDICES PARA QUERIES DE VALIDADE
-- ================================================

-- Índices compostos para filtros de validade
CREATE INDEX idx_visitantes_validade_ativa 
    ON visitantes(validity_status, data_vencimento) 
    WHERE validity_status = 'ativo';

CREATE INDEX idx_visitantes_expirando_soon 
    ON visitantes(data_vencimento) 
    WHERE validity_status = 'ativo' AND data_vencimento IS NOT NULL;

CREATE INDEX idx_prestadores_validade_ativa 
    ON prestadores_servico(validity_status, valid_until) 
    WHERE validity_status = 'ativo';

CREATE INDEX idx_prestadores_expirando_soon 
    ON prestadores_servico(valid_until) 
    WHERE validity_status = 'ativo' AND valid_until IS NOT NULL;

-- ================================================
-- PARTE 5: VIEW PARA CADASTROS EXPIRANDO
-- ================================================

-- View de visitantes expirando em 7 dias
CREATE OR REPLACE VIEW vw_visitantes_expirando AS
SELECT 
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    pessoa_visitada,
    valid_from,
    data_vencimento AS valid_until,
    validity_status,
    DATE_PART('day', data_vencimento - CURRENT_TIMESTAMP) AS dias_restantes
FROM visitantes
WHERE 
    validity_status = 'ativo'
    AND data_vencimento IS NOT NULL
    AND data_vencimento > CURRENT_TIMESTAMP
    AND data_vencimento <= CURRENT_TIMESTAMP + INTERVAL '7 days'
ORDER BY data_vencimento ASC;

-- View de prestadores expirando em 7 dias
CREATE OR REPLACE VIEW vw_prestadores_expirando AS
SELECT 
    id,
    nome,
    doc_type,
    doc_number,
    empresa,
    setor,
    funcionario_responsavel,
    valid_from,
    valid_until,
    validity_status,
    DATE_PART('day', valid_until - CURRENT_TIMESTAMP) AS dias_restantes
FROM prestadores_servico
WHERE 
    validity_status = 'ativo'
    AND valid_until IS NOT NULL
    AND valid_until > CURRENT_TIMESTAMP
    AND valid_until <= CURRENT_TIMESTAMP + INTERVAL '7 days'
ORDER BY valid_until ASC;

-- ================================================
-- PARTE 6: FUNÇÃO PARA RENOVAÇÃO AUTOMÁTICA
-- ================================================

-- Função para renovar validade de cadastro
CREATE OR REPLACE FUNCTION renovar_cadastro_visitante(
    p_visitante_id INTEGER,
    p_dias_adicionar INTEGER DEFAULT 90
) RETURNS BOOLEAN AS $$
DECLARE
    v_nova_data TIMESTAMP;
BEGIN
    -- Calcular nova data (a partir de hoje ou da data de vencimento atual)
    SELECT GREATEST(CURRENT_TIMESTAMP, COALESCE(data_vencimento, CURRENT_TIMESTAMP)) + (p_dias_adicionar || ' days')::INTERVAL
    INTO v_nova_data
    FROM visitantes
    WHERE id = p_visitante_id;
    
    -- Atualizar
    UPDATE visitantes
    SET 
        data_vencimento = v_nova_data,
        validity_status = 'ativo',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_visitante_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- Função para renovar prestador
CREATE OR REPLACE FUNCTION renovar_cadastro_prestador(
    p_prestador_id INTEGER,
    p_dias_adicionar INTEGER DEFAULT 30
) RETURNS BOOLEAN AS $$
DECLARE
    v_nova_data TIMESTAMP;
BEGIN
    SELECT GREATEST(CURRENT_TIMESTAMP, COALESCE(valid_until, CURRENT_TIMESTAMP)) + (p_dias_adicionar || ' days')::INTERVAL
    INTO v_nova_data
    FROM prestadores_servico
    WHERE id = p_prestador_id;
    
    UPDATE prestadores_servico
    SET 
        valid_until = v_nova_data,
        validity_status = 'ativo',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_prestador_id;
    
    RETURN FOUND;
END;
$$ LANGUAGE plpgsql;

-- ================================================
-- PARTE 7: JOB DIÁRIO PARA ATUALIZAR STATUS EXPIRADOS
-- ================================================

-- Função para marcar cadastros expirados
CREATE OR REPLACE FUNCTION marcar_cadastros_expirados()
RETURNS TABLE(
    entidade VARCHAR,
    total_expirados INTEGER
) AS $$
DECLARE
    v_visitantes INTEGER;
    v_prestadores INTEGER;
BEGIN
    -- Marcar visitantes expirados
    UPDATE visitantes
    SET validity_status = 'expirado'
    WHERE 
        validity_status = 'ativo'
        AND data_vencimento IS NOT NULL
        AND data_vencimento < CURRENT_TIMESTAMP;
    
    GET DIAGNOSTICS v_visitantes = ROW_COUNT;
    
    -- Marcar prestadores expirados
    UPDATE prestadores_servico
    SET validity_status = 'expirado'
    WHERE 
        validity_status = 'ativo'
        AND valid_until IS NOT NULL
        AND valid_until < CURRENT_TIMESTAMP;
    
    GET DIAGNOSTICS v_prestadores = ROW_COUNT;
    
    -- Retornar resultados
    RETURN QUERY
    SELECT 'visitantes'::VARCHAR, v_visitantes
    UNION ALL
    SELECT 'prestadores'::VARCHAR, v_prestadores;
END;
$$ LANGUAGE plpgsql;

-- ================================================
-- PARTE 8: CONFIGURAÇÕES DE VALIDADE PADRÃO
-- ================================================

-- Tabela de configurações de validade por tipo
CREATE TABLE IF NOT EXISTS validity_configurations (
    id SERIAL PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL UNIQUE, -- 'visitante', 'prestador'
    default_days INTEGER NOT NULL DEFAULT 30,
    auto_renew BOOLEAN DEFAULT FALSE,
    notify_days_before INTEGER DEFAULT 7, -- Notificar X dias antes de expirar
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT chk_default_days_positive CHECK (default_days > 0),
    CONSTRAINT chk_notify_days_positive CHECK (notify_days_before >= 0)
);

-- Inserir configurações padrão
INSERT INTO validity_configurations (entity_type, default_days, auto_renew, notify_days_before)
VALUES 
    ('visitante', 90, FALSE, 7),
    ('prestador', 30, FALSE, 7)
ON CONFLICT (entity_type) DO NOTHING;

-- Comentários
COMMENT ON TABLE validity_configurations IS 'Configurações de validade de cadastros por tipo de entidade';
COMMENT ON COLUMN validity_configurations.default_days IS 'Quantidade padrão de dias de validade';
COMMENT ON COLUMN validity_configurations.auto_renew IS 'Renovar automaticamente ao expirar';
COMMENT ON COLUMN validity_configurations.notify_days_before IS 'Dias antes de expirar para notificar';

COMMIT;

-- ================================================
-- VERIFICAÇÕES PÓS-MIGRATION
-- ================================================

-- Verificar visitantes com validade
-- SELECT id, nome, valid_from, data_vencimento, validity_status FROM visitantes LIMIT 10;

-- Verificar prestadores com validade
-- SELECT id, nome, valid_from, valid_until, validity_status FROM prestadores_servico LIMIT 10;

-- Verificar cadastros expirando
-- SELECT * FROM vw_visitantes_expirando;
-- SELECT * FROM vw_prestadores_expirando;

-- Testar função de renovação
-- SELECT renovar_cadastro_visitante(1, 90);
-- SELECT renovar_cadastro_prestador(1, 30);

-- Marcar expirados
-- SELECT * FROM marcar_cadastros_expirados();

-- ================================================
-- NOTAS IMPORTANTES
-- ================================================

/*
FUNCIONALIDADES:
1. Cálculo automático de validity_status via trigger
2. Views para cadastros expirando em 7 dias
3. Funções para renovação manual ou automática
4. Job diário para marcar expirados (executar via CRON)
5. Configurações personalizáveis por tipo

VISITANTES:
- Usa campo existente data_vencimento
- Padrão: 90 dias de validade
- Trigger atualiza status automaticamente

PRESTADORES:
- Novos campos valid_from e valid_until
- Padrão: 30 dias de validade
- Trigger atualiza status automaticamente

INTEGRAÇÃO COM UI:
1. Badge de status (ativo/expirado/bloqueado/pendente)
2. Alertas de expiração próxima
3. Botão de renovação rápida
4. Filtros por status de validade

CRON JOB (sugestão):
0 2 * * * psql $DATABASE_URL -c "SELECT marcar_cadastros_expirados();"

ROLLBACK:
Ver arquivo 002_validade_cadastros_rollback.sql
*/
