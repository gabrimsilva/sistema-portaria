# 🔙 ESTRATÉGIA DE ROLLBACK v2.0.0

## ⚠️ QUANDO FAZER ROLLBACK?

Execute rollback imediatamente se:

- ❌ Erros críticos em produção (500, crashes)
- ❌ Perda de dados detectada
- ❌ Funcionalidades core não funcionam (login, acesso, relatórios)
- ❌ Performance degradada significativamente
- ❌ Bugs de segurança descobertos

**NÃO faça rollback por:**
- ✅ Bugs menores de UI
- ✅ Funcionalidades novas com pequenos problemas
- ✅ Ajustes de texto/tradução

---

## 🔄 ORDEM DE ROLLBACK

### **Sequência Obrigatória (REVERSA):**

```
M5 (Reports) → M4 (Views/JS) → M3 (Endpoints) → M2 (Migrations)
```

**⚠️ IMPORTANTE:** Ordem reversa da aplicação!

---

## 📋 ROLLBACK M5 - RELATÓRIOS

### Tempo Estimado: 10-15 minutos

### Passos:

1. **Restaurar backups dos controllers**

```bash
# Localizar backup mais recente
ls -lt backups/m5_backup_*/controllers/

# Restaurar todos os controllers
BACKUP_DIR="backups/m5_backup_XXXXXXXXXX_XXXXXX"  # Ajustar data/hora
cp $BACKUP_DIR/controllers/*.php src/controllers/
```

2. **Verificar restauração**

```bash
ls -la src/controllers/ | grep -E "(Prestadores|Visitantes|Profissionais|Dashboard)"
```

3. **Testar**
   - [ ] Dashboard carrega
   - [ ] Relatórios carregam
   - [ ] Contadores funcionam

---

## 📋 ROLLBACK M4 - VIEWS & JAVASCRIPT

### Tempo Estimado: 10 minutos

### Passos:

1. **Remover views novas**

```bash
rm -f views/ramais/index.php
rm -f views/components/modal_entrada_retroativa.php
rm -f views/components/widget_cadastros_expirando.php
rm -f views/components/seletor_documento.php
```

2. **Remover JavaScript novo**

```bash
cd public/assets/js/
rm -f ramais.js entrada-retroativa.js widget-cadastros-expirando.js
rm -f document-validator.js gestao-validade.js
```

3. **Reverter mudanças manuais**

**views/dashboard/index.php:**
- Remover linha: `<?php require_once __DIR__ . '/../components/widget_cadastros_expirando.php'; ?>`

**views/layouts/main.php:**
- Remover linhas dos scripts: `document-validator.js` e `gestao-validade.js`

**src/services/NavigationService.php:**
- Remover item de menu "Ramais"

4. **Verificar**

```bash
# Views não devem existir
ls views/ramais/ 2>/dev/null && echo "ERRO: ramais ainda existe!" || echo "OK"
ls public/assets/js/ramais.js 2>/dev/null && echo "ERRO: ramais.js ainda existe!" || echo "OK"
```

---

## 📋 ROLLBACK M3 - ENDPOINTS

### Tempo Estimado: 15 minutos

### Passos:

1. **Remover controllers novos**

```bash
cd src/controllers/
rm -f DocumentoController.php
rm -f EntradaRetroativaController.php
rm -f RamalController.php
rm -f ValidadeController.php
```

2. **Remover service novo**

```bash
rm -f src/services/DocumentValidator.php
```

3. **Reverter rotas em public/index.php**

Abra `public/index.php` e **remova** as 22 rotas adicionadas:

```php
// Remover rotas de /api/documentos/*
// Remover rotas de /api/profissionais/entrada-retroativa
// Remover rotas de /api/ramais/*
// Remover rotas de /api/cadastros/validade/*
```

**Dica:** Use o backup:

```bash
BACKUP_DIR="backups/m3_backup_XXXXXXXXXX_XXXXXX"  # Ajustar data/hora
# Restaurar public/index.php se tiver backup
```

4. **Verificar**

```bash
ls src/controllers/ | grep -E "(Documento|EntradaRetroativa|Ramal|Validade)" && echo "ERRO!" || echo "OK"
```

---

## 📋 ROLLBACK M2 - MIGRATIONS (CRÍTICO!)

### ⚠️ ATENÇÃO: PODE CAUSAR PERDA DE DADOS

### Tempo Estimado: 20-30 minutos

### Pré-requisitos:
- [ ] Backup do banco válido e acessível
- [ ] Nenhum usuário ativo no sistema
- [ ] Janela de manutenção ativa

### Opção 1: Rollback SQL Scripts (RECOMENDADO)

Execute os rollback scripts **em ordem reversa**:

```bash
# 1. Verificar DATABASE_URL
echo $DATABASE_URL

# 2. Executar rollbacks em ordem reversa
psql "$DATABASE_URL" -f feature_v200/drafts/sql/004_ramais_corporativos_rollback.sql
psql "$DATABASE_URL" -f feature_v200/drafts/sql/003_fix_saida_placas_rollback.sql
psql "$DATABASE_URL" -f feature_v200/drafts/sql/002_validade_cadastros_rollback.sql
psql "$DATABASE_URL" -f feature_v200/drafts/sql/001_documentos_estrangeiros_rollback.sql
```

### Opção 2: Restaurar Backup Completo (EMERGÊNCIA)

Se rollback SQL falhar:

```bash
# 1. Parar todas as conexões
psql "$DATABASE_URL" -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid();"

# 2. Restaurar backup
pg_restore -d "$DATABASE_URL" --clean --if-exists backup_YYYYMMDD.dump

# 3. Verificar restauração
psql "$DATABASE_URL" -c "SELECT COUNT(*) FROM visitantes_novo;"
```

### Verificações Pós-Rollback:

```sql
-- 1. Views consolidadas NÃO devem existir
SELECT table_name FROM information_schema.views 
WHERE table_schema = 'public' AND table_name LIKE 'vw_%';
-- Resultado esperado: 0 linhas

-- 2. Colunas novas NÃO devem existir
SELECT column_name FROM information_schema.columns 
WHERE table_name = 'visitantes_novo' AND column_name IN ('doc_type', 'doc_number');
-- Resultado esperado: 0 linhas

-- 3. Tabela ramais_corporativos NÃO deve existir
SELECT table_name FROM information_schema.tables 
WHERE table_name = 'ramais_corporativos';
-- Resultado esperado: 0 linhas

-- 4. Dados preservados
SELECT COUNT(*) as visitantes FROM visitantes_novo;
SELECT COUNT(*) as prestadores FROM prestadores_servico;
-- Verificar se contadores batem com baseline
```

---

## 🧪 TESTES PÓS-ROLLBACK

Após rollback completo, execute:

### Testes Básicos:
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Cadastrar visitante (CPF)
- [ ] Registrar entrada
- [ ] Registrar saída
- [ ] Relatórios carregam
- [ ] Export CSV funciona

### Verificação de Dados:
- [ ] Nenhum registro perdido (comparar com baseline)
- [ ] Contadores corretos
- [ ] "Pessoas na Empresa" correto

---

## 📊 CHECKLIST DE ROLLBACK COMPLETO

```
[ ] M5 - Controllers revertidos
    [ ] Backups restaurados
    [ ] Relatórios funcionam
    
[ ] M4 - Views/JS removidos
    [ ] Arquivos deletados
    [ ] Mudanças manuais revertidas
    [ ] Navegação funciona
    
[ ] M3 - Endpoints removidos
    [ ] Controllers deletados
    [ ] Services deletados
    [ ] Rotas removidas de index.php
    
[ ] M2 - Migrations revertidas
    [ ] SQL rollbacks executados OU backup restaurado
    [ ] Views consolidadas removidas
    [ ] Colunas novas removidas
    [ ] Tabelas novas removidas
    [ ] Dados preservados
    
[ ] Testes pós-rollback executados
    [ ] Funcionalidades core funcionam
    [ ] Nenhum dado perdido
    [ ] Performance normal
    
[ ] Documentação
    [ ] Motivo do rollback registrado
    [ ] Issues criados para bugs
    [ ] Plano de correção definido
```

---

## 📞 EM CASO DE EMERGÊNCIA

Se rollback falhar ou houver perda de dados:

### 1. **PARAR TUDO**
```bash
# Colocar sistema em manutenção
# Desabilitar acesso público
```

### 2. **AVALIAR DANOS**
```sql
-- Verificar integridade
SELECT COUNT(*) FROM visitantes_novo;
SELECT COUNT(*) FROM prestadores_servico;
SELECT COUNT(*) FROM profissionais_renner;
```

### 3. **RESTAURAR BACKUP COMPLETO**
```bash
pg_restore -d "$DATABASE_URL" --clean --if-exists backup_pre_v200.dump
```

### 4. **NOTIFICAR**
- Gerente de TI
- DBA
- Desenvolvedor Lead
- Usuários afetados

---

## 💾 PREVENÇÃO

Para evitar necessidade de rollback:

### Antes da Integração:
- ✅ Backup completo e validado
- ✅ Testes em ambiente de staging
- ✅ Janela de manutenção adequada
- ✅ Equipe disponível

### Durante a Integração:
- ✅ Aplicar uma etapa por vez
- ✅ Testar após cada etapa
- ✅ Monitorar logs em tempo real
- ✅ Validar dados após migrations

### Após a Integração:
- ✅ Monitoramento 24/7 nas primeiras 48h
- ✅ Logs de erro capturados
- ✅ Feedback dos usuários coletado
- ✅ Backups mantidos por 30 dias

---

**Última Atualização:** v2.0.0  
**Contato Emergência:** [DEFINIR]  
**Backup Disponível:** [DEFINIR LOCAL]
