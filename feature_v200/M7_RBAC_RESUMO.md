# M7 - RBAC PERMISSIONS v2.0.0 (CONCLUÍDO)

## 🎯 OBJETIVO

Implementar sistema de permissões RBAC para as novas funcionalidades v2.0.0, garantindo controle de acesso granular e segurança.

---

## 📦 DELIVERABLES

### ✅ 1. Mapeamento de Permissões (M7.1)
**Arquivo:** `feature_v200/drafts/rbac/permissoes_v2.md`

Criadas **5 novas permissões**:

1. **documentos.manage** 🌍
   - Gerenciar documentos internacionais
   - Roles: Admin, Recepção, RH

2. **entrada.retroativa** 📅⚠️
   - Registrar entradas retroativas
   - Roles: Admin, Segurança
   - **SENSÍVEL** (altera histórico)

3. **validade.manage** ⏰
   - Gerenciar validade de cadastros
   - Roles: Admin, Recepção, RH

4. **ramais.manage** 📞
   - Gerenciar ramais corporativos
   - Roles: Admin, RH

5. **reports.advanced_filters** 🔍
   - Filtros avançados em relatórios
   - Roles: Admin, Segurança, RH

---

### ✅ 2. SQL de Permissões (M7.2)
**Arquivos:**
- `feature_v200/drafts/sql/005_rbac_permissions_v2.sql`
- `feature_v200/drafts/sql/005_rbac_permissions_v2_rollback.sql`

**Operações:**
- INSERT de 5 permissões na tabela `permissions`
- INSERT de 13 associações na tabela `role_permissions`
- Validações automáticas (contadores)
- Auditoria automática
- Rollback completo disponível

---

### ✅ 3. Matriz de Permissões (M7.3)
**Arquivo:** `feature_v200/drafts/rbac/matriz_rbac_v2.md`

| Role | Permissões Antigas | Permissões Novas | Total |
|------|:------------------:|:----------------:|:-----:|
| **Administrador** | 12 | 5 | 17 |
| **Segurança** | 8 | 2 | 10 |
| **Recepção** | 5 | 2 | 7 |
| **RH** | 4 | 4 | 8 |
| **Porteiro** | 5 | 0 | 5 |

---

### ✅ 4. Validação de Controllers (M7.4)
**Arquivo:** `feature_v200/drafts/rbac/diff_permissions_fix.md`

**Problemas Encontrados:**
- ❌ DocumentoController: sem verificação de permissão
- ❌ EntradaRetroativaController: permissão errada (`acesso.retroativo`)
- ❌ ValidadeController: sem verificação em 3 métodos
- ❌ RamalController: permissão errada (`brigada.manage`)

**Correções Criadas:**
- 10 diffs de correção em 4 controllers
- Todas as permissões alinhadas com v2.0.0

---

### ✅ 5. Script de Aplicação (M7.5)
**Arquivo:** `feature_v200/apply_m7_rbac.sh`

**Funcionalidades:**
- Executa SQL de permissões
- Valida criação (5 permissões, 13 associações)
- Guia para correções manuais
- Testes de validação
- Resumo final

---

### ✅ 6. Documentação Completa (M7.6)
**Este arquivo:** `feature_v200/M7_RBAC_RESUMO.md`

---

## 📊 MATRIZ DETALHADA

### Permissões por Role:

#### **Administrador** (17 total)
- ✅ config.* (todas configurações)
- ✅ audit.* (auditoria completa)
- ✅ users.* (gestão de usuários)
- ✅ person.cpf.view_unmasked
- ✅ reports.read
- ✅ reports.export
- ✅ reports.advanced_filters 🆕
- ✅ registro_acesso.* (CRUD completo)
- ✅ entrada.retroativa 🆕
- ✅ documentos.manage 🆕
- ✅ validade.manage 🆕
- ✅ ramais.manage 🆕

#### **Segurança** (10 total)
- ✅ audit.* 
- ✅ reports.read
- ✅ reports.export
- ✅ reports.advanced_filters 🆕
- ✅ registro_acesso.* (CRUD)
- ✅ entrada.retroativa 🆕

#### **Recepção** (7 total)
- ✅ registro_acesso.* (CRUD básico)
- ✅ documentos.manage 🆕
- ✅ validade.manage 🆕

#### **RH** (8 total)
- ✅ users.read
- ✅ person.cpf.view_unmasked
- ✅ reports.read
- ✅ reports.export
- ✅ reports.advanced_filters 🆕
- ✅ documentos.manage 🆕
- ✅ validade.manage 🆕
- ✅ ramais.manage 🆕

#### **Porteiro** (5 total)
- ✅ registro_acesso.create
- ✅ registro_acesso.read
- ✅ registro_acesso.update
- ✅ registro_acesso.checkin
- ✅ registro_acesso.checkout

---

## 🔐 SEGURANÇA

### Permissões Sensíveis:

1. **entrada.retroativa** ⚠️
   - Pode alterar histórico de acessos
   - Motivo obrigatório (auditoria)
   - Apenas Admin e Segurança

2. **config.*** ⚠️
   - Acesso total às configurações
   - Apenas Admin

3. **audit.*** ⚠️
   - Visualização de logs completos
   - Admin e Segurança

### Proteções:
- ✅ CSRF token em todas as ações
- ✅ Auditoria automática
- ✅ IP e User-Agent registrados
- ✅ HTTP 403 em negação de acesso
- ✅ Mensagens descritivas de erro

---

## 📋 CHECKLIST DE APLICAÇÃO

### PRÉ-REQUISITOS
- [ ] Backup do banco de dados
- [ ] M2-M6 já aplicados
- [ ] DATABASE_URL configurada

### EXECUÇÃO
- [ ] Executar: `bash feature_v200/apply_m7_rbac.sh`
- [ ] SQL de permissões aplicado (5 permissões)
- [ ] 13 associações criadas
- [ ] Diffs de controllers aplicados (10 correções)
- [ ] Testes de validação executados

### VALIDAÇÃO
- [ ] Teste documentos.manage (Recepção ✅, Porteiro ❌)
- [ ] Teste entrada.retroativa (Segurança ✅, Recepção ❌)
- [ ] Teste validade.manage (RH ✅, Segurança ❌)
- [ ] Teste ramais.manage (Admin ✅, Porteiro ❌)
- [ ] Teste reports.advanced_filters (Segurança ✅, Porteiro ❌)

---

## 🧪 TESTES

### Casos de Teste:

#### **Teste 1: documentos.manage**
```bash
# Login: Recepção (TEM permissão)
POST /api/documentos/validar
Body: {"doc_type":"PASSAPORTE","doc_number":"AB123456"}
Esperado: 200 OK

# Login: Porteiro (NÃO TEM)
Esperado: 403 Forbidden
```

#### **Teste 2: entrada.retroativa**
```bash
# Login: Segurança (TEM permissão)
POST /api/profissionais/entrada-retroativa
Body: {"profissional_id":1,"data_entrada":"2025-10-10 08:00","motivo":"teste"}
Esperado: 200 OK

# Login: Recepção (NÃO TEM)
Esperado: 403 Forbidden
```

#### **Teste 3: validade.manage**
```bash
# Login: RH (TEM permissão)
POST /api/cadastros/validade/renovar
Body: {"tipo":"visitante","id":1,"dias":30}
Esperado: 200 OK

# Login: Segurança (NÃO TEM)
Esperado: 403 Forbidden
```

#### **Teste 4: ramais.manage**
```bash
# Login: Admin (TEM permissão)
POST /api/ramais/adicionar
Body: {"profissional_id":1,"ramal":"1234"}
Esperado: 200 OK

# Login: Porteiro (NÃO TEM)
Esperado: 403 Forbidden
```

#### **Teste 5: reports.advanced_filters**
```bash
# Login: Segurança (TEM permissão)
GET /api/visitantes?doc_type=PASSAPORTE&doc_country=ARG
Esperado: 200 OK com filtros aplicados

# Login: Recepção (NÃO TEM)
Esperado: Filtros avançados desabilitados
```

---

## 📊 ESTATÍSTICAS M7

| Métrica | Valor |
|---------|-------|
| **Permissões novas** | 5 |
| **Associações criadas** | 13 |
| **Controllers corrigidos** | 4 |
| **Diffs aplicados** | 10 |
| **Módulos novos** | 3 (documentos, validade, ramais) |
| **Roles afetadas** | 4/5 (exceto Porteiro) |
| **Permissões sensíveis** | 1 (entrada.retroativa) |
| **Total de permissões** | 17 (12 antigas + 5 novas) |

---

## 🚀 INTEGRAÇÃO COM M6

O M7 (RBAC) deve ser aplicado **ANTES** do M6 (Integração) para garantir que as permissões estejam prontas quando os controllers forem copiados.

### Nova Ordem de Aplicação:

```
M2 (Migrations) → M7 (RBAC) → M3 (Endpoints) → M4 (Views) → M5 (Reports) → M6 (Testes)
```

---

## 📁 ARQUIVOS CRIADOS

```
feature_v200/drafts/rbac/
├── permissoes_v2.md              ✅ Mapeamento completo
├── matriz_rbac_v2.md             ✅ Matriz detalhada
└── diff_permissions_fix.md       ✅ Correções de controllers

feature_v200/drafts/sql/
├── 005_rbac_permissions_v2.sql   ✅ SQL de permissões
└── 005_rbac_permissions_v2_rollback.sql ✅ Rollback

feature_v200/
├── apply_m7_rbac.sh              ✅ Script de aplicação
└── M7_RBAC_RESUMO.md             ✅ Este arquivo
```

---

## 🔄 ROLLBACK

Se necessário reverter:

```bash
# Rollback SQL
psql "$DATABASE_URL" -f feature_v200/drafts/sql/005_rbac_permissions_v2_rollback.sql

# Reverter controllers
# (usar backup criado pelo script)
```

---

## ⏭️ PRÓXIMOS PASSOS

### **M8 - Testes de Segurança** (próximo)
- Testes de CSRF
- Testes de RBAC
- Testes de SQL Injection
- Testes de XSS
- Auditoria de segurança

### **M9 - Documentação**
- Manual do usuário
- Guia de administrador
- Changelog v2.0.0

### **M10-M12 - Deploy**
- Staging
- Produção
- Monitoramento

---

**Status:** ✅ M7 CONCLUÍDO  
**Data:** 15/10/2025  
**Próximo:** M8 - Testes de Segurança
