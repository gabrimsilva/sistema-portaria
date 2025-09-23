-- ETAPA 1: Correções Críticas de Integridade de Dados
-- Migração para implementar constraints únicos e validações de horário
-- Data: 2025-09-23

-- =============================================================================
-- PARTE 1: LIMPEZA DE DADOS CORROMPIDOS (PRÉ-REQUISITO PARA CONSTRAINTS)
-- =============================================================================

-- Corrigir CPFs duplicados ativos (fechar entradas mais antigas)
UPDATE visitantes_novo 
SET hora_saida = hora_entrada + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT v1.id
    FROM visitantes_novo v1
    INNER JOIN visitantes_novo v2 ON v1.cpf = v2.cpf 
    WHERE v1.id < v2.id 
      AND v1.hora_saida IS NULL 
      AND v2.hora_saida IS NULL
      AND v1.cpf IS NOT NULL 
      AND v1.cpf != ''
);

-- Corrigir placas duplicadas ativas (fechar entradas mais antigas)
UPDATE visitantes_novo 
SET hora_saida = hora_entrada + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT v1.id
    FROM visitantes_novo v1
    INNER JOIN visitantes_novo v2 ON v1.placa_veiculo = v2.placa_veiculo 
    WHERE v1.id < v2.id 
      AND v1.hora_saida IS NULL 
      AND v2.hora_saida IS NULL
      AND v1.placa_veiculo IS NOT NULL 
      AND v1.placa_veiculo != ''
);

-- Corrigir horários inválidos (saída < entrada)
UPDATE visitantes_novo 
SET hora_saida = hora_entrada + INTERVAL '1 hour'
WHERE hora_saida IS NOT NULL AND hora_saida < hora_entrada;

-- Corrigir datas futuras (entrada > agora)
UPDATE visitantes_novo 
SET hora_entrada = NOW()
WHERE hora_entrada > NOW();

-- =============================================================================
-- REPETIR LIMPEZA PARA PRESTADORES_SERVICO
-- =============================================================================

UPDATE prestadores_servico 
SET saida = entrada + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT p1.id
    FROM prestadores_servico p1
    INNER JOIN prestadores_servico p2 ON p1.cpf = p2.cpf 
    WHERE p1.id < p2.id 
      AND p1.saida IS NULL 
      AND p2.saida IS NULL
      AND p1.cpf IS NOT NULL 
      AND p1.cpf != ''
);

UPDATE prestadores_servico 
SET saida = entrada + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT p1.id
    FROM prestadores_servico p1
    INNER JOIN prestadores_servico p2 ON p1.placa_veiculo = p2.placa_veiculo 
    WHERE p1.id < p2.id 
      AND p1.saida IS NULL 
      AND p2.saida IS NULL
      AND p1.placa_veiculo IS NOT NULL 
      AND p1.placa_veiculo != ''
);

UPDATE prestadores_servico 
SET saida = entrada + INTERVAL '1 hour'
WHERE saida IS NOT NULL AND saida < entrada;

UPDATE prestadores_servico 
SET entrada = NOW()
WHERE entrada > NOW();

-- =============================================================================
-- REPETIR LIMPEZA PARA REGISTRO_ACESSO  
-- =============================================================================

UPDATE registro_acesso 
SET saida_at = entrada_at + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT r1.id
    FROM registro_acesso r1
    INNER JOIN registro_acesso r2 ON r1.cpf = r2.cpf 
    WHERE r1.id < r2.id 
      AND r1.saida_at IS NULL 
      AND r2.saida_at IS NULL
      AND r1.cpf IS NOT NULL 
      AND r1.cpf != ''
);

UPDATE registro_acesso 
SET saida_at = entrada_at + INTERVAL '1 hour'
WHERE id IN (
    SELECT DISTINCT r1.id
    FROM registro_acesso r1
    INNER JOIN registro_acesso r2 ON r1.placa_veiculo = r2.placa_veiculo 
    WHERE r1.id < r2.id 
      AND r1.saida_at IS NULL 
      AND r2.saida_at IS NULL
      AND r1.placa_veiculo IS NOT NULL 
      AND r1.placa_veiculo != ''
);

UPDATE registro_acesso 
SET saida_at = entrada_at + INTERVAL '1 hour'
WHERE saida_at IS NOT NULL AND saida_at < entrada_at;

UPDATE registro_acesso 
SET entrada_at = NOW()
WHERE entrada_at > NOW();

-- =============================================================================
-- PARTE 2: CRIAÇÃO DE CONSTRAINTS ÚNICOS PARA CPF ATIVO
-- =============================================================================

-- Visitantes: CPF único apenas quando ativo (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_visitantes_cpf_ativo 
ON visitantes_novo (cpf) 
WHERE cpf IS NOT NULL AND cpf != '' AND hora_saida IS NULL;

-- Prestadores: CPF único apenas quando ativo (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_prestadores_cpf_ativo 
ON prestadores_servico (cpf) 
WHERE cpf IS NOT NULL AND cpf != '' AND saida IS NULL;

-- Registro Acesso: CPF único apenas quando ativo (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_registro_cpf_ativo 
ON registro_acesso (cpf) 
WHERE cpf IS NOT NULL AND cpf != '' AND saida_at IS NULL;

-- =============================================================================
-- PARTE 3: CRIAÇÃO DE CONSTRAINTS ÚNICOS PARA PLACA ATIVA
-- =============================================================================

-- Visitantes: Placa única apenas quando ativa (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_visitantes_placa_ativa 
ON visitantes_novo (placa_veiculo) 
WHERE placa_veiculo IS NOT NULL AND placa_veiculo != '' AND hora_saida IS NULL;

-- Prestadores: Placa única apenas quando ativa (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_prestadores_placa_ativa 
ON prestadores_servico (placa_veiculo) 
WHERE placa_veiculo IS NOT NULL AND placa_veiculo != '' AND saida IS NULL;

-- Registro Acesso: Placa única apenas quando ativa (sem saída)
CREATE UNIQUE INDEX IF NOT EXISTS ux_registro_placa_ativa 
ON registro_acesso (placa_veiculo) 
WHERE placa_veiculo IS NOT NULL AND placa_veiculo != '' AND saida_at IS NULL;

-- =============================================================================
-- PARTE 4: CRIAÇÃO DE CONSTRAINTS CHECK PARA HORÁRIOS VÁLIDOS
-- =============================================================================

-- Visitantes: Saída deve ser posterior ou igual à entrada
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'chk_visitantes_horario_valido'
    ) THEN
        ALTER TABLE visitantes_novo 
        ADD CONSTRAINT chk_visitantes_horario_valido 
        CHECK (hora_saida IS NULL OR hora_saida >= hora_entrada);
    END IF;
END $$;

-- Prestadores: Saída deve ser posterior ou igual à entrada
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'chk_prestadores_horario_valido'
    ) THEN
        ALTER TABLE prestadores_servico 
        ADD CONSTRAINT chk_prestadores_horario_valido 
        CHECK (saida IS NULL OR saida >= entrada);
    END IF;
END $$;

-- Registro Acesso: Saída deve ser posterior ou igual à entrada
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'chk_registro_horario_valido'
    ) THEN
        ALTER TABLE registro_acesso 
        ADD CONSTRAINT chk_registro_horario_valido 
        CHECK (saida_at IS NULL OR saida_at >= entrada_at);
    END IF;
END $$;

-- =============================================================================
-- PARTE 5: VALIDAÇÃO FINAL
-- =============================================================================

-- Verificar se não há mais dados conflitantes
DO $$
DECLARE
    duplicate_count INTEGER;
    invalid_time_count INTEGER;
BEGIN
    -- Contar CPFs duplicados ativos
    SELECT COUNT(*) INTO duplicate_count
    FROM (
        SELECT cpf FROM visitantes_novo WHERE cpf IS NOT NULL AND cpf != '' AND hora_saida IS NULL GROUP BY cpf HAVING COUNT(*) > 1
        UNION ALL
        SELECT cpf FROM prestadores_servico WHERE cpf IS NOT NULL AND cpf != '' AND saida IS NULL GROUP BY cpf HAVING COUNT(*) > 1
        UNION ALL
        SELECT cpf FROM registro_acesso WHERE cpf IS NOT NULL AND cpf != '' AND saida_at IS NULL GROUP BY cpf HAVING COUNT(*) > 1
    ) duplicates;
    
    -- Contar horários inválidos
    SELECT COUNT(*) INTO invalid_time_count
    FROM (
        SELECT 1 FROM visitantes_novo WHERE hora_saida IS NOT NULL AND hora_saida < hora_entrada
        UNION ALL
        SELECT 1 FROM prestadores_servico WHERE saida IS NOT NULL AND saida < entrada
        UNION ALL
        SELECT 1 FROM registro_acesso WHERE saida_at IS NOT NULL AND saida_at < entrada_at
    ) invalid_times;
    
    -- Log dos resultados
    RAISE NOTICE 'MIGRAÇÃO CONCLUÍDA - CPFs duplicados ativos: %, Horários inválidos: %', duplicate_count, invalid_time_count;
    
    IF duplicate_count > 0 OR invalid_time_count > 0 THEN
        RAISE EXCEPTION 'MIGRAÇÃO FALHOU - Ainda existem dados conflitantes';
    END IF;
END $$;