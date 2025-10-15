# 📊 MATRIZ RBAC v2.0.0

## 🎯 VISÃO GERAL

Esta matriz mostra **todas as permissões** (antigas + novas) e quais roles têm acesso.

---

## 📋 MATRIZ COMPLETA

| Permissão | Módulo | Admin | Segurança | Recepção | RH | Porteiro |
|-----------|--------|:-----:|:---------:|:--------:|:--:|:--------:|
| **config.*** | config | ✅ | ❌ | ❌ | ❌ | ❌ |
| **audit.*** | audit | ✅ | ✅ | ❌ | ❌ | ❌ |
| **users.*** | users | ✅ | ❌ | ❌ | ✅ | ❌ |
| **person.cpf.view_unmasked** | privacy | ✅ | ❌ | ❌ | ✅ | ❌ |
| **reports.read** | reports | ✅ | ✅ | ❌ | ✅ | ❌ |
| **reports.export** | reports | ✅ | ✅ | ❌ | ✅ | ❌ |
| **reports.advanced_filters** 🆕 | reports | ✅ | ✅ | ❌ | ✅ | ❌ |
| **registro_acesso.create** | access | ✅ | ✅ | ✅ | ❌ | ✅ |
| **registro_acesso.read** | access | ✅ | ✅ | ✅ | ❌ | ✅ |
| **registro_acesso.update** | access | ✅ | ✅ | ✅ | ❌ | ✅ |
| **registro_acesso.delete** | access | ✅ | ❌ | ❌ | ❌ | ❌ |
| **registro_acesso.checkin** | access | ✅ | ✅ | ✅ | ❌ | ✅ |
| **registro_acesso.checkout** | access | ✅ | ✅ | ✅ | ❌ | ✅ |
| **entrada.retroativa** 🆕⚠️ | access | ✅ | ✅ | ❌ | ❌ | ❌ |
| **documentos.manage** 🆕 | documentos | ✅ | ❌ | ✅ | ✅ | ❌ |
| **validade.manage** 🆕 | validade | ✅ | ❌ | ✅ | ✅ | ❌ |
| **ramais.manage** 🆕 | ramais | ✅ | ❌ | ❌ | ✅ | ❌ |

**Legenda:**
- ✅ = Tem permissão
- ❌ = Não tem permissão
- 🆕 = Nova em v2.0.0
- ⚠️ = Permissão sensível

---

## 📈 ESTATÍSTICAS POR ROLE

| Role | Permissões Antigas | Permissões Novas | Total | % do Sistema |
|------|:------------------:|:----------------:|:-----:|:------------:|
| **Administrador** | 12 | 5 | **17** | 100% |
| **Segurança** | 8 | 2 | **10** | 59% |
| **Recepção** | 5 | 2 | **7** | 41% |
| **RH** | 4 | 4 | **8** | 47% |
| **Porteiro** | 5 | 0 | **5** | 29% |

---

## 🔐 PERMISSÕES POR MÓDULO

### **Módulo: config**
- `config.*` → Admin

### **Módulo: audit**
- `audit.*` → Admin, Segurança

### **Módulo: users**
- `users.*` → Admin, RH

### **Módulo: privacy**
- `person.cpf.view_unmasked` → Admin, RH

### **Módulo: reports**
- `reports.read` → Admin, Segurança, RH
- `reports.export` → Admin, Segurança, RH
- `reports.advanced_filters` 🆕 → Admin, Segurança, RH

### **Módulo: access**
- `registro_acesso.create` → Admin, Segurança, Recepção, Porteiro
- `registro_acesso.read` → Admin, Segurança, Recepção, Porteiro
- `registro_acesso.update` → Admin, Segurança, Recepção, Porteiro
- `registro_acesso.delete` → Admin
- `registro_acesso.checkin` → Admin, Segurança, Recepção, Porteiro
- `registro_acesso.checkout` → Admin, Segurança, Recepção, Porteiro
- `entrada.retroativa` 🆕⚠️ → Admin, Segurança

### **Módulo: documentos** 🆕
- `documentos.manage` 🆕 → Admin, Recepção, RH

### **Módulo: validade** 🆕
- `validade.manage` 🆕 → Admin, Recepção, RH

### **Módulo: ramais** 🆕
- `ramais.manage` 🆕 → Admin, RH

---

## 🎯 CASOS DE USO

### **1. Cadastrar Visitante Internacional**

**Fluxo:**
1. Recepção acessa cadastro de visitante
2. Seleciona tipo de documento "Passaporte"
3. Sistema verifica permissão `documentos.manage`
4. ✅ Recepção TEM → Permite cadastro
5. Salva com auditoria

**Quem pode:** Administrador, Recepção, RH  
**Quem não pode:** Segurança, Porteiro

---

### **2. Registrar Entrada Retroativa**

**Fluxo:**
1. Segurança percebe entrada não registrada (ontem)
2. Acessa função de entrada retroativa
3. Sistema verifica permissão `entrada.retroativa`
4. ✅ Segurança TEM → Abre modal
5. Informa data/hora passada + motivo
6. Sistema valida conflitos
7. Salva com auditoria (motivo obrigatório)

**Quem pode:** Administrador, Segurança  
**Quem não pode:** Recepção, RH, Porteiro  
**⚠️ Sensível:** Altera histórico

---

### **3. Renovar Cadastro de Prestador**

**Fluxo:**
1. Recepção vê que prestador expira em 3 dias (widget)
2. Clica em "Renovar Rápido"
3. Sistema verifica permissão `validade.manage`
4. ✅ Recepção TEM → Abre modal
5. Seleciona período (30 dias)
6. Confirma renovação
7. Sistema atualiza validade + auditoria

**Quem pode:** Administrador, Recepção, RH  
**Quem não pode:** Segurança, Porteiro

---

### **4. Gerenciar Ramais**

**Fluxo:**
1. RH precisa adicionar novo ramal
2. Acessa /ramais (público - qualquer um vê)
3. Clica "Adicionar Ramal"
4. Sistema verifica permissão `ramais.manage`
5. ✅ RH TEM → Abre formulário
6. Preenche dados e salva
7. Auditoria registrada

**Quem pode (CRUD):** Administrador, RH  
**Quem pode (Consultar):** TODOS (público)

---

### **5. Filtro Avançado em Relatórios**

**Fluxo:**
1. Segurança acessa relatório de visitantes
2. Quer filtrar por "Passaporte" e país "ARG"
3. Sistema verifica permissão `reports.advanced_filters`
4. ✅ Segurança TEM → Mostra filtros avançados
5. Aplica filtros e exporta CSV

**Quem pode:** Administrador, Segurança, RH  
**Quem não pode:** Recepção (vê filtros básicos), Porteiro

---

## 🔒 SEGURANÇA

### **Permissões Sensíveis:**

1. **entrada.retroativa** ⚠️
   - Pode alterar histórico de acessos
   - Motivo obrigatório (auditoria)
   - Apenas Admin e Segurança
   - Log automático de mudanças

2. **config.*** ⚠️
   - Acesso total às configurações
   - Pode desativar segurança
   - Apenas Admin

3. **audit.*** ⚠️
   - Acesso aos logs de auditoria
   - Pode ver todas as ações
   - Admin e Segurança

### **Proteções Automáticas:**

- ✅ CSRF token em todas as ações
- ✅ Session timeout (configurável)
- ✅ Auditoria automática de mudanças
- ✅ IP e User-Agent registrados
- ✅ Bloqueio após tentativas falhas

---

## 📊 RESUMO FINAL

| Métrica | Valor |
|---------|-------|
| **Total de permissões** | 17 |
| **Permissões antigas** | 12 |
| **Permissões novas** | 5 |
| **Módulos totais** | 8 |
| **Módulos novos** | 3 |
| **Roles totais** | 5 |
| **Roles com mudanças** | 4 |
| **Permissões sensíveis** | 3 |

---

## 🎯 PRÓXIMOS PASSOS

1. ✅ Aplicar SQL de permissões (005_rbac_permissions_v2.sql)
2. ⏳ Validar controllers M3 usam permissões
3. ⏳ Testar matriz no sistema
4. ⏳ Documentar para usuários

**Status:** ✅ MATRIZ COMPLETA  
**Arquivo:** `feature_v200/drafts/rbac/matriz_rbac_v2.md`
