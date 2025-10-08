-- ========================================
-- ÍNDICES OPCIONAIS PARA PAINEL DE BRIGADA
-- ========================================

-- ANÁLISE DOS ÍNDICES EXISTENTES:
-- ✅ idx_registro_acesso_profissional (profissional_renner_id, entrada_at DESC) - JÁ EXISTE
-- ✅ idx_brigadistas_active (active) - JÁ EXISTE
-- ✅ idx_brigadistas_professional_id (professional_id) - JÁ EXISTE

-- CONCLUSÃO: Os índices atuais já são SUFICIENTES para a query do painel.
-- A query utiliza:
-- 1. JOIN em profissional_renner_id (coberto por idx_registro_acesso_profissional)
-- 2. WHERE saida_final IS NULL (pode usar idx parcial se criado)
-- 3. WHERE b.active = TRUE (coberto por idx_brigadistas_active)

-- ÍNDICE PARCIAL ADICIONAL (OPCIONAL - provavelmente desnecessário):
-- Benefício marginal, apenas se houver muitos registros com saida_final preenchida

CREATE INDEX IF NOT EXISTS idx_registro_acesso_brigada_presente 
ON registro_acesso(profissional_renner_id, entrada_at DESC)
WHERE tipo = 'profissional_renner' AND saida_final IS NULL;

-- OBSERVAÇÃO:
-- Este índice é OPCIONAL e pode ser criado SOMENTE se:
-- 1. A tabela registro_acesso crescer muito (>100k registros)
-- 2. A performance da query do painel degradar
-- 3. Análise EXPLAIN ANALYZE mostrar necessidade

-- COMANDO PARA TESTAR PERFORMANCE:
-- EXPLAIN ANALYZE
-- SELECT p.nome, p.setor, r.profissional_renner_id, COALESCE(r.retorno, r.entrada_at) as desde
-- FROM registro_acesso r
-- JOIN profissionais_renner p ON p.id = r.profissional_renner_id
-- JOIN brigadistas b ON b.professional_id = p.id
-- WHERE r.tipo = 'profissional_renner' AND r.saida_final IS NULL AND b.active = TRUE;

-- Se o EXPLAIN mostrar Sequential Scan em registro_acesso, então criar o índice acima.
-- Caso contrário, os índices atuais são suficientes.
