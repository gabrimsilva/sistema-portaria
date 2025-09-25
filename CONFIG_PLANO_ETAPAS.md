# 📋 CONFIG_PLANO_ETAPAS.md

**Data:** 25/09/2025  
**Sistema:** Controle de Acesso (PHP + PostgreSQL + AdminLTE)  
**Objetivo:** Completar funcionalidades das 5 abas de Configurações

---

## 🎯 **ESTRATÉGIA GERAL**

**Stack Mantida:** PHP + PostgreSQL + AdminLTE  
**Abordagem:** Completar frontends para usar APIs existentes  
**Metodologia:** Etapas incrementais com confirmação e testes

**Foco das Etapas:**
1. ✅ Backend já completo → Implementar frontends
2. 🧪 Adicionar testes automáticos
3. ⚡ Otimizar performance (<250ms)
4. 🛡️ Reforçar validações e segurança

---

## 📊 **BACKLOG DE ETAPAS**

### 🏢 **ETAPA 1 - ORGANIZAÇÃO COMPLETA** 
**Status:** Backend ✅ | Frontend ⚠️ | Testes ❌  
**Estimativa:** 2-3 horas

#### **Objetivos:**
- Completar formulário de organização
- Melhorar upload de logo
- Implementar testes básicos

#### **Arquivos a Alterar:**
```diff
# views/config/index.php
+ Implementar formulário completo de organização
+ Validações JavaScript em tempo real
+ Máscaras CNPJ, feedback visual

# Novos arquivos de teste:
+ tests/OrganizationTest.php (unit)
+ tests/ConfigAPITest.php (integração)
```

#### **Critérios de Aceite:**
- [x] Nome empresa: 2-120 chars, validação tempo real
- [x] CNPJ: Validação DV + formatação automática
- [x] Logo: Upload PNG/JPG ≤2MB com preview
- [x] Fuso/Idioma: Dropdowns funcionais
- [x] Salvar/Carregar: Feedback sucesso/erro
- [x] Testes: Cobertura ≥80% funções críticas

#### **Testes Planejados:**
```bash
# Unit Tests
php tests/unit/CnpjValidatorTest.php
php tests/unit/OrganizationServiceTest.php

# Integration Tests  
php tests/integration/ConfigAPITest.php

# E2E Tests (manual)
- Preencher formulário → Salvar → Recarregar
- Upload logo → Verificar preview
- CNPJ inválido → Ver erro
```

#### **Métricas de Eficiência:**
- GET /config/organization: < 200ms
- PUT /config/organization: < 250ms  
- Upload logo: < 500ms (2MB)

---

### 📍 **ETAPA 2 - LOCAIS/SITES COMPLETO**
**Status:** Backend ✅ | Frontend ❌ | Testes ❌  
**Estimativa:** 4-5 horas

#### **Objetivos:**
- Interface completa gerenciamento sites
- CRUD setores com hierarquia
- Horários funcionamento + exceções
- Gestão feriados

#### **Arquivos a Alterar:**
```diff
# views/config/index.php
+ Seção Sites com lista, botões CRUD
+ Modal "Novo Local" com validações
+ Interface setores por site
+ Configuração horários/exceções
+ Interface feriados/calendário

# src/controllers/ConfigController.php
+ getBusinessHours(), updateBusinessHours()
+ getHolidays(), createHoliday(), deleteHoliday()

# Novos:
+ public/js/config-sites.js
+ tests/SitesManagementTest.php
```

#### **Funcionalidades Frontend:**
- ✅ Lista sites com ações (Editar, Excluir, Ver Setores)
- ✅ Modal "Novo Local": nome, endereço, capacidade ≥0
- ✅ Setores: CRUD vinculado a site
- ✅ Horários: seg-dom, abertura/fechamento
- ✅ Exceções: datas específicas, feriados
- ✅ Validações: capacidade numérica, horários válidos

#### **Schema Verificado:**
- ✅ `sites` (id, name, address, capacity)
- ✅ `sectors` (id, site_id, name, capacity)
- ✅ `business_hours` (site_id, day_week, open, close)
- ✅ `business_hour_exceptions` (site_id, date, open, close, is_closed)
- ✅ `holidays` (id, name, date, site_id, scope)

#### **Testes:**
```bash
# CRUD completo
- Criar site → Verificar lista
- Editar site → Confirmar alteração  
- Excluir site → Verificar remoção
- Setores: adicionar, editar, remover
- Horários: configurar, exceções
```

#### **Eficiência:**
- Listagem sites: < 200ms
- CRUD operações: < 250ms
- Sem consultas N+1

---

### 🛡️ **ETAPA 3 - RBAC MATRIZ INTERATIVA**
**Status:** Backend ✅ | Frontend ❌ | Testes ❌  
**Estimativa:** 3-4 horas

#### **Objetivos:**
- Matriz visual com checkboxes
- Modal "Ver usuários por perfil"
- Gestão roles personalizadas
- Validação CPF mascarado

#### **Arquivos a Alterar:**
```diff
# views/config/index.php
+ Matriz RBAC com roles x permissions
+ Checkboxes interativos
+ Modal usuários por perfil
+ Botão "Salvar Alterações"

# src/controllers/ConfigController.php  
+ getUsersByRole()
+ validateRolePermissions()

# Novos:
+ public/js/config-rbac.js
+ tests/RbacMatrixTest.php
```

#### **Interface RBAC:**
- ✅ Matriz: 5 roles x N permissions
- ✅ Checkboxes: check/uncheck interativo
- ✅ Agrupamento: por módulo (config, reports, access, audit, users, privacy)
- ✅ Modal usuários: lista por perfil selecionado
- ✅ Salvar: apenas alterações (delta)
- ✅ Validação: Admin mantém config.*, person.cpf.view_unmasked

#### **Roles Definidas:**
- 👑 **Admin**: config.*, audit.*, users.*, person.cpf.view_unmasked
- 🛡️ **Segurança**: reports.*, access.update, audit.read
- 📞 **Recepção**: access.create, access.read, access.update_basic
- 👥 **RH**: users.read, reports.read, person.cpf.view_unmasked
- 🚪 **Porteiro**: access.checkin, access.checkout, access.read

#### **Testes:**
```bash
# Funcionalidade
- Carregar matriz → Verificar estado atual
- Alterar permissions → Salvar → Recarregar
- Negar rota admin → Confirmar bloqueio
- CPF mascarado → Verificar por perfil

# Segurança  
- Admin não pode perder config.*
- Roles não podem escalar privilégios
```

#### **Eficiência:**
- Carregar matriz: < 200ms
- Salvar alterações: < 250ms
- Cache/state local isolado

---

### 🔐 **ETAPA 4 - POLÍTICAS AUTENTICAÇÃO**
**Status:** Backend ✅ | Frontend ❌ | Testes ❌  
**Estimativa:** 2-3 horas  

#### **Objetivos:**
- Formulário políticas senha
- Configuração 2FA/SSO (placeholders)
- Aplicar políticas no login
- Validações tempo real

#### **Arquivos a Alterar:**
```diff
# views/config/index.php
+ Formulário auth policies
+ Toggles 2FA/SSO com campos condicionais
+ Validações JavaScript políticas

# src/controllers/AuthController.php
+ Aplicar políticas no login
+ Verificar senha expirada
+ Enforce session timeout

# Schema verificado:
✅ auth_policies (password_min_length, password_expiry_days, 
                  session_timeout_minutes, two_factor_enabled, sso_enabled)
```

#### **Políticas Configuráveis:**
- 🔑 **Senha mínima**: 6-20 caracteres
- ⏰ **Expiração**: 30-365 dias  
- 🕐 **Sessão**: 15-480 minutos
- 📱 **2FA**: toggle (placeholder UI)
- 🔗 **SSO**: toggle + campos provedor (placeholder)

#### **Enforcement:**
- ✅ Login: verificar política senha
- ✅ Sessão: auto-logout por timeout
- ✅ Alteração senha: aplicar regras
- ⚠️ 2FA/SSO: apenas UI (implementação futura)

#### **Testes:**
```bash
# Políticas
- Configurar min_length=8 → Testar login senha curta
- Expiração=60 dias → Verificar alerta renovação  
- Timeout=30min → Confirmar auto-logout
```

#### **Eficiência:**
- GET/PUT policies: < 200ms
- Validação login: < 100ms adicional

---

### 📊 **ETAPA 5 - AUDITORIA AVANÇADA**
**Status:** Backend ✅ | Frontend ❌ | Testes ❌  
**Estimativa:** 3-4 horas

#### **Objetivos:**
- Interface filtros avançados
- Paginação eficiente
- Export CSV funcional
- Visualização diff dados

#### **Arquivos a Alterar:**
```diff
# views/config/index.php
+ Filtros: usuário, ação, entidade, período
+ Paginação: prev/next com info registros
+ Botão Export CSV
+ Tabela: dados_antes/dados_depois diff visual

# src/controllers/ConfigController.php
+ Otimizar paginação SQL
+ Headers CSV export
+ Filtros SQL seguros

# Novos:
+ public/js/config-audit.js  
+ public/css/audit-diff.css
```

#### **Filtros Implementados:**
- 👤 **Usuário**: dropdown com usuários ativos
- 🎬 **Ação**: create, update, delete
- 📁 **Entidade**: organization_settings, sites, sectors, usuarios
- 📅 **Período**: data início/fim (date pickers)
- 🔍 **Busca**: texto livre em dados

#### **Visualização:**
- ✅ Tabela: timestamp, usuário, ação, entidade, IP
- ✅ Diff: antes/depois lado a lado
- ✅ Paginação: 20 registros/página
- ✅ Export: CSV com filtros aplicados
- ✅ Performance: índices eficientes

#### **Testes:**
```bash
# Funcionalidade
- Gerar logs → Filtrar → Verificar resultado
- Export CSV → Confirmar conteúdo  
- Paginação → Navegar páginas
- Período específico → Validar filtro
```

#### **Eficiência:**
- Consulta filtrada: < 300ms
- Export CSV: < 500ms (1000 registros)
- Índices: user_id, timestamp, entity

---

### 🧹 **ETAPA 6 - HIGIENE E NAVEGAÇÃO**
**Status:** ❌ Falta | **Estimativa:** 2 horas

#### **Objetivos:**
- Cancelar requests ao trocar aba
- Limpar timers/debounces
- Estados isolados por aba
- Prevenção memory leaks

#### **Implementações:**
```javascript
// AbortController para requests
const abortController = new AbortController();

// Cleanup ao trocar aba
function cleanupTab() {
    abortController.abort();
    clearTimeout(debounceTimer);
    closeModals();
}

// Cache isolado por aba
const tabStates = {
    organization: {},
    sites: {},
    rbac: {},
    auth: {},
    audit: {}
};
```

#### **Testes:**
- Navegar rápido entre abas → Sem erros console
- Requests pendentes → Verificar cancelamento
- Memory usage → Sem vazamentos

---

### 🔐 **ETAPA 7 - SEGURANÇA E PERFORMANCE**
**Status:** ⚠️ Básica | **Estimativa:** 2-3 horas

#### **Objetivos:**
- Rate limiting endpoints config
- Sanitização/escape campos
- Validações adicionais upload
- Métricas performance

#### **Implementações:**
```php
// Rate limiting
class RateLimiter {
    public static function check($action, $limit = 10, $window = 60) {
        // Implementar controle por IP/usuário
    }
}

// Sanitização  
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
```

#### **Validações Upload:**
- Verificação MIME real
- Scan antivirus básico  
- Limit por usuário/hora
- Backup automático logos

---

## 🎯 **RESUMO DAS PRIORIDADES**

### **🔥 Críticas (Fazer Primeiro):**
1. **ETAPA 2** - Sites/Locais (funcionalidade essencial)
2. **ETAPA 3** - RBAC (segurança crítica)  
3. **ETAPA 1** - Organização (completar)

### **📈 Importantes:**
4. **ETAPA 5** - Auditoria (compliance)
5. **ETAPA 4** - Autenticação (policies)

### **🔧 Melhorias:**  
6. **ETAPA 6** - Higiene UX
7. **ETAPA 7** - Segurança adicional

---

## 📊 **MÉTRICAS DE SUCESSO**

### **Performance Mínima:**
- GET endpoints: < 200ms
- PUT/POST: < 250ms  
- Upload logo: < 500ms
- Export CSV: < 500ms

### **Qualidade:**
- Cobertura testes: ≥ 70%
- Zero memory leaks
- RBAC 100% funcional
- Auditoria completa

### **UX:**
- Validações tempo real
- Feedback visual claro
- Navegação fluida
- Estados persistentes

---

**📋 Plano Total: 7 etapas | 18-24 horas | Stack PHP mantida**

**Próximo:** Execução guiada com confirmação por etapa