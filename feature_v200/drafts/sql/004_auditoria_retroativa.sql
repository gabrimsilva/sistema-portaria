-- ================================================
-- MIGRATION 004: AUDITORIA RETROATIVA
-- Versão: 2.0.0
-- Data: 14/10/2025
-- Objetivo: Suporte robusto para entradas retroativas com auditoria completa
-- ================================================

-- IMPORTANTE: Este é um DRAFT - NÃO EXECUTAR sem aprovação!

BEGIN;

-- ================================================
-- PARTE 1: ANÁLISE DO SISTEMA ATUAL
-- ================================================

/*
SISTEMA DE AUDITORIA ATUAL:
- Tabela audit_log já existe com campos robustos:
  * user_id, acao, entidade, entidade_id
  * dados_antes, dados_depois (JSONB)
  * ip_address, user_agent
  * timestamp (timestamptz UTC)
  * severidade, modulo, resultado

NECESSIDADE PARA RETROATIVO:
1. Campo adicional para "motivo" da entrada retroativa
2. Flag para identificar operações retroativas
3. Validação de integridade temporal
4. Auditoria obrigatória com rastreamento completo
*/

-- ================================================
-- PARTE 2: ADICIONAR CAMPO PARA OPERAÇÕES RETROATIVAS
-- ================================================

-- Adicionar campo opcional para motivo/justificativa
ALTER TABLE audit_log
    ADD COLUMN IF NOT EXISTS justificativa TEXT,
    ADD COLUMN IF NOT EXISTS is_retroactive BOOLEAN DEFAULT FALSE;

-- Comentários
COMMENT ON COLUMN audit_log.justificativa IS 'Motivo/justificativa para operações retroativas ou críticas';
COMMENT ON COLUMN audit_log.is_retroactive IS 'Flag indicando se a operação é retroativa (entrada/alteração de data passada)';

-- Índice para buscar operações retroativas
CREATE INDEX IF NOT EXISTS idx_audit_retroactive 
    ON audit_log(is_retroactive, timestamp) 
    WHERE is_retroactive = TRUE;

CREATE INDEX IF NOT EXISTS idx_audit_modulo_retroativo 
    ON audit_log(modulo, is_retroactive) 
    WHERE is_retroactive = TRUE;

-- ================================================
-- PARTE 3: TABELA DE ENTRADAS RETROATIVAS
-- ================================================

-- Tabela específica para rastrear entradas retroativas
CREATE TABLE IF NOT EXISTS entradas_retroativas (
    id SERIAL PRIMARY KEY,
    
    -- Referência à tabela afetada
    tabela VARCHAR(50) NOT NULL, -- 'profissionais_renner', 'registro_acesso'
    registro_id INTEGER NOT NULL,
    
    -- Dados da entrada retroativa
    data_entrada_original TIMESTAMP, -- Data real que deveria ter sido registrada
    data_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Quando foi registrado
    
    -- Auditoria obrigatória
    motivo TEXT NOT NULL, -- Motivo obrigatório
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
    usuario_nome VARCHAR(255),
    
    -- Validação
    aprovado_por INTEGER REFERENCES usuarios(id), -- Opcional: aprovação de supervisor
    aprovado_em TIMESTAMP,
    
    -- Metadados
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT chk_motivo_obrigatorio CHECK (LENGTH(TRIM(motivo)) >= 10),
    CONSTRAINT chk_data_retroativa CHECK (data_entrada_original < data_registro)
);

-- Comentários
COMMENT ON TABLE entradas_retroativas IS 'Registro de todas as entradas retroativas para auditoria';
COMMENT ON COLUMN entradas_retroativas.motivo IS 'Motivo obrigatório (mínimo 10 caracteres)';
COMMENT ON COLUMN entradas_retroativas.data_entrada_original IS 'Data/hora que deveria ter sido registrada originalmente';
COMMENT ON COLUMN entradas_retroativas.data_registro IS 'Data/hora em que o registro retroativo foi feito';

-- Índices
CREATE INDEX idx_retroativas_tabela_registro ON entradas_retroativas(tabela, registro_id);
CREATE INDEX idx_retroativas_usuario ON entradas_retroativas(usuario_id, data_registro);
CREATE INDEX idx_retroativas_data_original ON entradas_retroativas(data_entrada_original);
CREATE INDEX idx_retroativas_pendentes ON entradas_retroativas(aprovado_por, aprovado_em) 
    WHERE aprovado_por IS NULL;

-- ================================================
-- PARTE 4: FUNÇÃO PARA REGISTRAR ENTRADA RETROATIVA
-- ================================================

-- Função centralizada para criar entrada retroativa em profissionais_renner
CREATE OR REPLACE FUNCTION registrar_entrada_retroativa_profissional(
    p_profissional_id INTEGER,
    p_data_entrada TIMESTAMP,
    p_motivo TEXT,
    p_usuario_id INTEGER,
    p_ip_address VARCHAR DEFAULT NULL,
    p_user_agent TEXT DEFAULT NULL
) RETURNS TABLE(
    sucesso BOOLEAN,
    mensagem TEXT,
    retroativa_id INTEGER,
    registro_acesso_id INTEGER
) AS $$
DECLARE
    v_profissional RECORD;
    v_retroativa_id INTEGER;
    v_registro_id INTEGER;
    v_conflito RECORD;
    v_usuario_nome VARCHAR;
BEGIN
    -- Validar motivo
    IF LENGTH(TRIM(p_motivo)) < 10 THEN
        RETURN QUERY SELECT FALSE, 'Motivo deve ter no mínimo 10 caracteres', NULL::INTEGER, NULL::INTEGER;
        RETURN;
    END IF;
    
    -- Validar data retroativa (não pode ser futura)
    IF p_data_entrada >= CURRENT_TIMESTAMP THEN
        RETURN QUERY SELECT FALSE, 'Data de entrada deve ser no passado', NULL::INTEGER, NULL::INTEGER;
        RETURN;
    END IF;
    
    -- Buscar profissional
    SELECT * INTO v_profissional
    FROM profissionais_renner
    WHERE id = p_profissional_id;
    
    IF NOT FOUND THEN
        RETURN QUERY SELECT FALSE, 'Profissional não encontrado', NULL::INTEGER, NULL::INTEGER;
        RETURN;
    END IF;
    
    -- Buscar nome do usuário
    SELECT name INTO v_usuario_nome FROM usuarios WHERE id = p_usuario_id;
    
    -- Validar se não há conflito com saídas intermediárias
    -- Verificar se existe alguma saída ANTERIOR à entrada retroativa
    SELECT * INTO v_conflito
    FROM registro_acesso
    WHERE profissional_renner_id = p_profissional_id
      AND saida_at IS NOT NULL
      AND saida_at < p_data_entrada
    ORDER BY saida_at DESC
    LIMIT 1;
    
    IF FOUND THEN
        RETURN QUERY SELECT 
            FALSE, 
            'Conflito: existe saída registrada em ' || TO_CHAR(v_conflito.saida_at, 'DD/MM/YYYY HH24:MI') || 
            ' que é anterior à entrada retroativa', 
            NULL::INTEGER, 
            NULL::INTEGER;
        RETURN;
    END IF;
    
    -- Validar se não há entrada em aberto no momento retroativo
    SELECT * INTO v_conflito
    FROM registro_acesso
    WHERE profissional_renner_id = p_profissional_id
      AND entrada_at <= p_data_entrada
      AND (saida_at IS NULL OR saida_at >= p_data_entrada)
    LIMIT 1;
    
    IF FOUND THEN
        RETURN QUERY SELECT 
            FALSE, 
            'Conflito: já existe entrada ativa no horário ' || TO_CHAR(p_data_entrada, 'DD/MM/YYYY HH24:MI'),
            NULL::INTEGER, 
            NULL::INTEGER;
        RETURN;
    END IF;
    
    -- Criar registro de entrada retroativa
    INSERT INTO entradas_retroativas (
        tabela, registro_id, data_entrada_original, motivo, 
        usuario_id, usuario_nome, ip_address, user_agent
    )
    VALUES (
        'profissionais_renner', p_profissional_id, p_data_entrada, p_motivo,
        p_usuario_id, v_usuario_nome, p_ip_address, p_user_agent
    )
    RETURNING id INTO v_retroativa_id;
    
    -- Criar registro em registro_acesso
    INSERT INTO registro_acesso (
        tipo, nome, cpf, doc_type, doc_number, doc_country,
        empresa, setor, placa_veiculo,
        entrada_at, saida_at,
        profissional_renner_id,
        created_by, updated_by,
        observacao
    )
    VALUES (
        'profissional',
        v_profissional.nome,
        v_profissional.cpf,
        v_profissional.doc_type,
        v_profissional.doc_number,
        v_profissional.doc_country,
        v_profissional.empresa,
        v_profissional.setor,
        v_profissional.placa_veiculo,
        p_data_entrada, -- ENTRADA RETROATIVA
        NULL,
        p_profissional_id,
        p_usuario_id,
        p_usuario_id,
        '[RETROATIVO] ' || p_motivo
    )
    RETURNING id INTO v_registro_id;
    
    -- Atualizar data_entrada em profissionais_renner se necessário
    UPDATE profissionais_renner
    SET data_entrada = p_data_entrada
    WHERE id = p_profissional_id
      AND (data_entrada IS NULL OR data_entrada > p_data_entrada);
    
    -- Registrar em audit_log
    INSERT INTO audit_log (
        user_id, acao, entidade, entidade_id,
        dados_antes, dados_depois,
        ip_address, user_agent,
        severidade, modulo, resultado,
        is_retroactive, justificativa
    )
    VALUES (
        p_usuario_id,
        'entrada_retroativa',
        'profissionais_renner',
        p_profissional_id,
        to_jsonb(v_profissional),
        jsonb_build_object(
            'data_entrada_retroativa', p_data_entrada,
            'registro_acesso_id', v_registro_id,
            'retroativa_id', v_retroativa_id
        ),
        p_ip_address,
        p_user_agent,
        'CRITICAL', -- Severidade alta
        'acesso',
        'success',
        TRUE, -- is_retroactive
        p_motivo
    );
    
    -- Retornar sucesso
    RETURN QUERY SELECT TRUE, 'Entrada retroativa registrada com sucesso', v_retroativa_id, v_registro_id;
END;
$$ LANGUAGE plpgsql;

-- ================================================
-- PARTE 5: VIEW PARA RELATÓRIO DE RETROATIVAS
-- ================================================

CREATE OR REPLACE VIEW vw_entradas_retroativas AS
SELECT 
    er.id,
    er.tabela,
    er.registro_id,
    er.data_entrada_original,
    er.data_registro,
    er.motivo,
    er.usuario_id,
    er.usuario_nome,
    er.aprovado_por,
    er.aprovado_em,
    -- Dados do profissional (se aplicável)
    CASE 
        WHEN er.tabela = 'profissionais_renner' THEN pr.nome
        ELSE NULL
    END AS profissional_nome,
    CASE 
        WHEN er.tabela = 'profissionais_renner' THEN pr.setor
        ELSE NULL
    END AS setor,
    -- Aprovador
    u_aprovador.nome AS aprovador_nome,
    -- Tempo decorrido
    EXTRACT(DAY FROM (er.data_registro - er.data_entrada_original)) AS dias_atraso,
    er.created_at
FROM entradas_retroativas er
LEFT JOIN profissionais_renner pr ON (er.tabela = 'profissionais_renner' AND er.registro_id = pr.id)
LEFT JOIN usuarios u_aprovador ON er.aprovado_por = u_aprovador.id
ORDER BY er.created_at DESC;

COMMENT ON VIEW vw_entradas_retroativas IS 'Relatório completo de entradas retroativas com auditoria';

-- ================================================
-- PARTE 6: PERMISSÃO ESPECÍFICA PARA RETROATIVO
-- ================================================

-- Inserir permissão nova no sistema (se não existir)
INSERT INTO permissions (key, description, module)
VALUES (
    'acesso.retroativo',
    'Permite registrar entradas retroativas (datas passadas)',
    'acesso'
)
ON CONFLICT (key) DO NOTHING;

-- ================================================
-- PARTE 7: FUNCTION PARA ESTATÍSTICAS
-- ================================================

CREATE OR REPLACE FUNCTION stats_entradas_retroativas(
    p_data_inicio DATE DEFAULT CURRENT_DATE - INTERVAL '30 days',
    p_data_fim DATE DEFAULT CURRENT_DATE
) RETURNS TABLE(
    total_retroativas BIGINT,
    total_usuarios BIGINT,
    media_dias_atraso NUMERIC,
    maior_atraso_dias NUMERIC,
    pendentes_aprovacao BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(*)::BIGINT AS total_retroativas,
        COUNT(DISTINCT usuario_id)::BIGINT AS total_usuarios,
        AVG(EXTRACT(DAY FROM (data_registro - data_entrada_original)))::NUMERIC AS media_dias_atraso,
        MAX(EXTRACT(DAY FROM (data_registro - data_entrada_original)))::NUMERIC AS maior_atraso_dias,
        COUNT(*) FILTER (WHERE aprovado_por IS NULL)::BIGINT AS pendentes_aprovacao
    FROM entradas_retroativas
    WHERE data_registro::DATE BETWEEN p_data_inicio AND p_data_fim;
END;
$$ LANGUAGE plpgsql;

COMMIT;

-- ================================================
-- VERIFICAÇÕES PÓS-MIGRATION
-- ================================================

-- 1. Verificar estrutura da tabela
-- SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'entradas_retroativas';

-- 2. Testar função de entrada retroativa
-- SELECT * FROM registrar_entrada_retroativa_profissional(
--     1, -- profissional_id
--     CURRENT_TIMESTAMP - INTERVAL '2 days', -- data retroativa
--     'Colaborador esqueceu de registrar entrada na segunda-feira', -- motivo
--     1, -- usuario_id
--     '192.168.1.1', -- ip
--     'Mozilla/5.0...' -- user agent
-- );

-- 3. Ver relatório
-- SELECT * FROM vw_entradas_retroativas LIMIT 10;

-- 4. Ver estatísticas
-- SELECT * FROM stats_entradas_retroativas();

-- ================================================
-- NOTAS IMPORTANTES
-- ================================================

/*
FUNCIONALIDADES IMPLEMENTADAS:
1. ✅ Campo justificativa e is_retroactive em audit_log
2. ✅ Tabela entradas_retroativas para rastreamento específico
3. ✅ Função registrar_entrada_retroativa_profissional() com validações
4. ✅ Validação de conflitos com saídas intermediárias
5. ✅ Auditoria completa com severidade CRITICAL
6. ✅ View para relatórios de retroativas
7. ✅ Permissão específica acesso.retroativo
8. ✅ Estatísticas de uso

VALIDAÇÕES IMPLEMENTADAS:
- Motivo obrigatório (mínimo 10 caracteres)
- Data deve ser no passado
- Não pode haver saída anterior à entrada retroativa
- Não pode haver entrada em aberto no mesmo horário
- Profissional deve existir

FLUXO DE USO:
1. Usuário com permissão acesso.retroativo acessa interface
2. Seleciona profissional
3. Informa data/hora retroativa e motivo detalhado
4. Sistema valida integridade temporal
5. Cria registro em entradas_retroativas
6. Cria entrada em registro_acesso com flag retroativo
7. Registra em audit_log com is_retroactive=TRUE
8. Opcional: supervisor aprova/rejeita

INTEGRAÇÃO COM UI:
- Modal de entrada retroativa em profissionais_renner
- Seletor de data/hora (não pode ser futura)
- Textarea obrigatório para motivo (min 10 chars)
- Exibir badge [RETROATIVO] em registros retroativos
- Relatório dedicado de entradas retroativas

AUDITORIA:
- Toda entrada retroativa gera 2 registros:
  1. entradas_retroativas (rastreamento específico)
  2. audit_log (auditoria geral com is_retroactive=TRUE)
  
ROLLBACK:
Ver arquivo 004_auditoria_retroativa_rollback.sql
*/
