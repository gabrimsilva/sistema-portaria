# 📁 PRÉ-CADASTROS v2.0.0 - ÍNDICE DE DRAFTS

**Status:** ⚠️ **TUDO EM DRAFT** - Nenhum código aplicado ao projeto  
**Data:** 17 de outubro de 2025  
**Progresso:** M1-M7 concluídos (100%)

---

## 🎯 **LEIA PRIMEIRO**

👉 **[RESUMO_EXECUTIVO_v200.md](docs/RESUMO_EXECUTIVO_v200.md)** ← **COMECE AQUI**

Este documento contém:
- Visão geral completa do projeto
- Antes vs Depois
- Todos os componentes criados
- Checklist de aprovação
- Plano de aplicação

---

## 📂 **ESTRUTURA DE PASTAS**

```
feature_v200/drafts/
├─ sql/                              # M2 - Banco de Dados
│  ├─ 001_create_pre_cadastros_tables.sql
│  ├─ 002_create_pre_cadastros_views.sql
│  ├─ 003_create_indexes.sql
│  ├─ 004_create_foreign_keys.sql
│  ├─ 005_create_rbac_permissions.sql
│  └─ 999_rollback_all.sql           # Rollback completo
│
├─ controllers/                      # M3 - Backend
│  ├─ PreCadastrosVisitantesController.php (443 linhas)
│  ├─ PreCadastrosPrestadoresController.php (380 linhas)
│  └─ ApiPreCadastrosController.php   (300 linhas)
│
├─ views/pre-cadastros/              # M4 - Frontend
│  ├─ visitantes/
│  │  ├─ index.php                   # Lista
│  │  └─ form.php                    # Formulário
│  └─ prestadores/
│     ├─ index.php                   # Lista
│     └─ form.php                    # Formulário
│
├─ js/                               # M4 - JavaScript
│  ├─ pre-cadastros.js               # Lista (300 linhas)
│  └─ pre-cadastros-form.js          # Formulário (200 linhas)
│
├─ snippets/                         # Patches para arquivos existentes
│  ├─ AuthorizationService.php.diff  # M5 - Permissões
│  ├─ NavigationService.php.diff     # M4 - Menu lateral
│  └─ dashboard_autocomplete.js      # M4 - Integração dashboard
│
└─ docs/                             # Documentação
   ├─ RESUMO_EXECUTIVO_v200.md       # 👈 LEIA PRIMEIRO
   ├─ M2_database_summary.md
   ├─ M3_backend_summary.md
   ├─ M4_views_summary.md
   ├─ M5_rbac_summary.md
   ├─ M6_prestadores_summary.md
   └─ RBAC_permission_matrix.md
```

---

## 📊 **PROGRESSO POR MILESTONE**

| ID | Milestone | Status | Arquivos | Linhas |
|----|-----------|--------|----------|--------|
| M1 | Descoberta | ✅ Completo | 1 doc | - |
| M2 | Banco de Dados | ✅ Completo | 6 SQL | ~800 |
| M3 | Backend | ✅ Completo | 3 PHP | 1.123 |
| M4 | Frontend | ✅ Completo | 7 arquivos | 1.550 |
| M5 | RBAC | ✅ Completo | 2 arquivos | 150 |
| M6 | Prestadores | ✅ Completo | 3 arquivos | 630 |
| M7 | Resumo Executivo | ✅ Completo | 1 doc | - |
| **TOTAL** | **7 milestones** | **100%** | **23 arquivos** | **~5.500** |

---

## 📖 **GUIA DE LEITURA**

### **1. Visão Geral (5 minutos)**

Leia: **RESUMO_EXECUTIVO_v200.md**
- Objetivo do projeto
- Antes vs Depois
- Componentes criados
- Checklist de aprovação

---

### **2. Banco de Dados (10 minutos)**

Leia: **docs/M2_database_summary.md**

Examine:
- `sql/001_create_pre_cadastros_tables.sql` (estrutura das tabelas)
- `sql/002_create_pre_cadastros_views.sql` (views derivadas)
- `sql/003_create_indexes.sql` (performance)

**Foco em:**
- Campos `valid_from` e `valid_until` (lógica de validade)
- Constraint `doc_type` + `doc_number` (ambos NULL ou ambos preenchidos)
- Foreign key `cadastro_id` (vínculo cadastro → registros)

---

### **3. Backend (15 minutos)**

Leia: **docs/M3_backend_summary.md**

Examine:
- `controllers/PreCadastrosVisitantesController.php` (CRUD completo)
- `controllers/ApiPreCadastrosController.php` (APIs)

**Foco em:**
- Método `save()` (validações e normalização)
- Método `renovar()` (lógica de +1 ano)
- API `buscar()` (autocomplete)

---

### **4. Frontend (15 minutos)**

Leia: **docs/M4_views_summary.md**

Examine:
- `views/pre-cadastros/visitantes/index.php` (lista)
- `views/pre-cadastros/visitantes/form.php` (formulário)
- `js/pre-cadastros.js` (interatividade)
- `snippets/dashboard_autocomplete.js` (integração)

**Foco em:**
- Cards de estatísticas (válidos, expirando, expirados)
- Autocomplete no dashboard (fluxo completo)
- Badges de status (✅⚠️❌)

---

### **5. RBAC (5 minutos)**

Leia: **docs/M5_rbac_summary.md** ou **RBAC_permission_matrix.md**

Examine:
- `snippets/AuthorizationService.php.diff` (permissões)
- `sql/005_create_rbac_permissions.sql` (RBAC moderno)

**Foco em:**
- Admin: 5/5 permissões (pode excluir)
- Porteiro: 4/5 permissões (não pode excluir)
- Justificativas das decisões

---

### **6. Prestadores (5 minutos)**

Leia: **docs/M6_prestadores_summary.md**

**Diferenças em relação a Visitantes:**
- Campo empresa obrigatório
- Botões amarelos (match dashboard)
- Mesma lógica CRUD

---

## ✅ **CHECKLIST DE APROVAÇÃO**

Antes de aplicar, verifique:

### **Banco de Dados:**
- [ ] Estrutura de tabelas faz sentido?
- [ ] Views derivadas estão corretas?
- [ ] Índices cobrem queries principais?
- [ ] Foreign keys protegem integridade?
- [ ] Rollback está disponível?

### **Backend:**
- [ ] Controllers seguem padrão existente?
- [ ] Validações estão completas?
- [ ] LGPD está respeitada (masking)?
- [ ] Auditoria está integrada?
- [ ] APIs retornam JSON padronizado?

### **Frontend:**
- [ ] Views são simples e intuitivas?
- [ ] Formulários têm validação JS?
- [ ] Badges e cores fazem sentido?
- [ ] Autocomplete funciona?
- [ ] Menu lateral está posicionado corretamente?

### **RBAC:**
- [ ] Permissões fazem sentido?
- [ ] Admin tem controle total?
- [ ] Porteiro não pode excluir (correto)?
- [ ] Segurança/Recepção sem acesso (correto)?

### **Geral:**
- [ ] Nomenclatura está consistente?
- [ ] Código está documentado?
- [ ] Performance é adequada?
- [ ] Rollback está testado?

---

## 🚀 **PLANO DE APLICAÇÃO**

### **Opção A: Aplicar TUDO de uma vez** ⚡

**Passos:**
1. Backup completo (banco + código)
2. Executar 5 migrations SQL
3. Copiar 3 controllers
4. Copiar 4 views + 2 JS
5. Aplicar 2 patches (diff)
6. Testar fluxo completo

**Tempo:** 15-20 minutos  
**Risco:** Médio (rollback complexo se houver erro)

---

### **Opção B: Aplicar por etapas** 🎯

**Passos:**
1. M2 (Banco) → Testar
2. M3 (Backend) → Testar
3. M4 (Frontend) → Testar
4. M5 (RBAC) → Testar

**Tempo:** 30-40 minutos  
**Risco:** Baixo (rollback parcial fácil)

---

## 📝 **COMANDOS RÁPIDOS**

### **Backup:**
```bash
# Banco
pg_dump -U postgres -d acesso_control > backup_pre_v200.sql

# Código
tar -czf backup_code.tar.gz src/ views/ public/js/
```

---

### **Aplicar Banco (M2):**
```bash
cd feature_v200/drafts/sql/
psql -U postgres -d acesso_control < 001_create_pre_cadastros_tables.sql
psql -U postgres -d acesso_control < 002_create_pre_cadastros_views.sql
psql -U postgres -d acesso_control < 003_create_indexes.sql
psql -U postgres -d acesso_control < 004_create_foreign_keys.sql
psql -U postgres -d acesso_control < 005_create_rbac_permissions.sql
```

---

### **Aplicar Backend (M3):**
```bash
cp controllers/*.php ../../src/controllers/
# Editar manualmente: src/routes.php (adicionar rotas)
```

---

### **Aplicar Frontend (M4):**
```bash
cp -r views/pre-cadastros ../../views/
cp js/*.js ../../public/js/
patch ../../src/services/NavigationService.php < snippets/NavigationService.php.diff
```

---

### **Aplicar RBAC (M5):**
```bash
patch ../../src/services/AuthorizationService.php < snippets/AuthorizationService.php.diff
```

---

### **Rollback (Se necessário):**
```bash
psql -U postgres -d acesso_control < sql/999_rollback_all.sql
git checkout src/ views/ public/js/
```

---

## 🎯 **PRÓXIMOS PASSOS**

Você tem **3 opções**:

1. **Aplicar agora (Opção A ou B)** → Eu aplico tudo para você
2. **Revisar código específico** → Me diga o que quer ver
3. **Fazer perguntas** → Tire dúvidas sobre qualquer parte

**O que prefere fazer?** 🚀

---

## 📞 **SUPORTE**

Dúvidas sobre:
- **Banco:** Leia M2_database_summary.md
- **Backend:** Leia M3_backend_summary.md
- **Frontend:** Leia M4_views_summary.md
- **RBAC:** Leia M5_rbac_summary.md ou RBAC_permission_matrix.md
- **Geral:** Leia RESUMO_EXECUTIVO_v200.md

---

**TUDO PRONTO PARA APLICAÇÃO!** ✅

Nenhum código foi aplicado ainda. Aguardando sua decisão.
