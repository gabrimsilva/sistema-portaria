# 🚀 PLANO DE INTEGRAÇÃO v2.0.0

## ⚠️ IMPORTANTE
Este documento descreve como aplicar as mudanças v2.0.0 ao sistema em produção.
**NÃO execute sem backup completo e janela de manutenção.**

---

## 📋 PRÉ-REQUISITOS

### ✅ Checklist Obrigatório

#### 1. **Backup Completo**
- [ ] Backup do banco de dados PostgreSQL
- [ ] Backup de todos os arquivos PHP (`src/`, `views/`, `public/`)
- [ ] Backup de configurações (`.env`, `config/`)
- [ ] Verificar que backups estão acessíveis e válidos

#### 2. **Ambiente de Testes**
- [ ] Ambiente de teste configurado (clone da produção)
- [ ] Todas as mudanças testadas no ambiente de teste
- [ ] Bugs críticos resolvidos antes de produção

#### 3. **Dependências**
- [ ] PostgreSQL 12+ instalado
- [ ] PHP 8.0+ instalado
- [ ] Extensão PDO PostgreSQL ativada
- [ ] Permissões de escrita em `/public/uploads/`

#### 4. **Acesso**
- [ ] Acesso SSH/admin ao servidor
- [ ] Permissões para executar SQL
- [ ] Acesso para editar arquivos PHP
- [ ] Acesso para reiniciar serviços (se necessário)

#### 5. **Janela de Manutenção**
- [ ] Janela de manutenção agendada (2-4 horas recomendadas)
- [ ] Usuários notificados sobre indisponibilidade
- [ ] Página de manutenção preparada

#### 6. **Equipe**
- [ ] Desenvolvedor disponível para aplicar mudanças
- [ ] DBA disponível para monitorar banco
- [ ] Suporte disponível para testes pós-deploy

---

## 📊 VALIDAÇÕES PRÉ-INTEGRAÇÃO

### Script de Validação

Execute este script SQL para validar o estado atual:

```sql
-- 1. Verificar tabelas existentes
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
  AND table_name IN ('visitantes_novo', 'prestadores_servico', 'profissionais_renner', 'registro_acesso')
ORDER BY table_name;

-- 2. Verificar colunas críticas
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'visitantes_novo' 
  AND column_name IN ('cpf', 'doc_type', 'doc_number');

-- 3. Verificar constraints
SELECT constraint_name, constraint_type 
FROM information_schema.table_constraints 
WHERE table_name = 'prestadores_servico' 
ORDER BY constraint_type;

-- 4. Contar registros (baseline)
SELECT 'visitantes_novo' as tabela, COUNT(*) as total FROM visitantes_novo
UNION ALL
SELECT 'prestadores_servico', COUNT(*) FROM prestadores_servico
UNION ALL
SELECT 'profissionais_renner', COUNT(*) FROM profissionais_renner
UNION ALL
SELECT 'registro_acesso', COUNT(*) FROM registro_acesso;

-- 5. Verificar registros ativos (baseline)
SELECT COUNT(*) as visitantes_ativos 
FROM visitantes_novo 
WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL;

SELECT COUNT(*) as prestadores_ativos 
FROM prestadores_servico 
WHERE entrada IS NOT NULL AND saida IS NULL;
```

**Registrar os resultados antes de prosseguir!**

---

## 🔄 ORDEM DE APLICAÇÃO

### **Sequência Obrigatória:**

```
M2 (Migrations) → M3 (Endpoints) → M4 (Views/JS) → M5 (Reports)
```

**⚠️ NÃO pule etapas ou inverta a ordem!**

---

## 🗂️ ESTRUTURA DE ARQUIVOS

Antes de começar, verifique que todos os arquivos draft existem:

```bash
# Verificar migrations (M2)
ls -la feature_v200/drafts/sql/
# Deve mostrar: 001_*.sql, 002_*.sql, 003_*.sql, 004_*.sql

# Verificar controllers (M3)
ls -la feature_v200/drafts/controllers/
# Deve mostrar: DocumentoController.php, EntradaRetroativaController.php, RamalController.php, ValidadeController.php

# Verificar services (M3)
ls -la feature_v200/drafts/services/
# Deve mostrar: DocumentValidator.php

# Verificar views (M4)
ls -la feature_v200/drafts/views/
# Deve mostrar: ramais/, components/

# Verificar JS (M4)
ls -la feature_v200/drafts/js/
# Deve mostrar: ramais.js, entrada-retroativa.js, widget-cadastros-expirando.js, document-validator.js, gestao-validade.js

# Verificar diffs (M5)
ls -la feature_v200/drafts/snippets/
# Deve mostrar: diff_*.md (4 arquivos)
```

---

## 🎯 OBJETIVOS DA INTEGRAÇÃO

### Funcionalidades a Serem Entregues:

1. ✅ **Documentos Internacionais**
   - Suporte a Passaporte, RNE, DNI, CI, etc
   - 8 tipos de documentos
   - Validação e máscaras

2. ✅ **Entrada Retroativa**
   - Registrar entradas passadas
   - Auditoria completa
   - Validações temporais

3. ✅ **Validade de Cadastros**
   - Períodos de validade
   - Renovação automática/manual
   - Bloqueio/desbloqueio
   - Widget de expirando

4. ✅ **Ramais Corporativos**
   - CRUD de ramais
   - Consulta pública
   - Integração brigadistas

5. ✅ **Filtros Avançados**
   - Por tipo de documento
   - Por país
   - Por status de validade

6. ✅ **Correções de Bugs**
   - Saídas de prestadores (CRÍTICO)
   - Registros de placas consolidados

---

## ⏱️ ESTIMATIVA DE TEMPO

| Etapa | Tempo Estimado | Tempo com Testes |
|-------|----------------|------------------|
| M2 - Migrations | 30-45 min | 1h |
| M3 - Endpoints | 20-30 min | 45 min |
| M4 - Views/JS | 15-20 min | 30 min |
| M5 - Reports | 30-40 min | 1h |
| Testes Integrados | - | 1-2h |
| **TOTAL** | **1h 35min - 2h 15min** | **4-5h** |

**Recomendação:** Reserve **4-6 horas** de janela de manutenção.

---

## 🔒 SEGURANÇA

### Medidas de Segurança Implementadas:

- ✅ **CSRF Protection** em todos os endpoints
- ✅ **RBAC Authorization** (5 novas permissões)
- ✅ **Audit Logging** automático
- ✅ **Input Validation** (client + server)
- ✅ **SQL Injection Protection** (prepared statements)
- ✅ **XSS Protection** (escape HTML)
- ✅ **LGPD Compliance** (máscaras de documentos)
- ✅ **CSV Formula Injection** protegido

---

## 📞 CONTATOS DE EMERGÊNCIA

Prepare lista de contatos antes de começar:

- **Desenvolvedor Lead:** _________________
- **DBA:** _________________
- **Gerente de TI:** _________________
- **Suporte Técnico:** _________________

---

## 🚨 CRITÉRIOS DE ROLLBACK

**Execute rollback imediatamente se:**

1. ❌ Migrations falharem com erro de integridade
2. ❌ Contadores do dashboard mostrarem valores incorretos
3. ❌ Usuários não conseguirem fazer login
4. ❌ Relatórios não carregarem
5. ❌ Erros 500 em endpoints críticos
6. ❌ Perda de dados detectada

**Processo de Rollback:** Ver seção "ROLLBACK" no final deste documento.

---

## ✅ VALIDAÇÕES PÓS-INTEGRAÇÃO

Após aplicar todas as mudanças, execute estes testes:

### 1. **Testes Funcionais Básicos**
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Contadores exibem valores corretos
- [ ] "Pessoas na Empresa" lista corretamente

### 2. **Testes de CRUD**
- [ ] Cadastrar visitante com CPF
- [ ] Cadastrar visitante com Passaporte
- [ ] Editar cadastro
- [ ] Excluir cadastro

### 3. **Testes de Acesso**
- [ ] Registrar entrada de visitante
- [ ] Registrar saída de visitante
- [ ] Registrar entrada de prestador
- [ ] Registrar saída via placa

### 4. **Testes de Relatórios**
- [ ] Relatório de visitantes carrega
- [ ] Filtros funcionam
- [ ] Export CSV funciona
- [ ] Prestadores mostram saídas (BUG FIX!)

### 5. **Testes de Novas Funcionalidades**
- [ ] Consulta de ramais funciona
- [ ] Widget de expirando aparece
- [ ] Entrada retroativa funciona
- [ ] Renovação de cadastro funciona
- [ ] Bloqueio de cadastro funciona

---

**Status:** 📋 CHECKLIST CRIADO  
**Próximo:** Scripts de Aplicação
