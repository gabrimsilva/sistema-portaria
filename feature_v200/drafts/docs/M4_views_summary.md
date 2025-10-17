# M4 - VIEWS & JS (DRAFT)

**Data:** 17 de outubro de 2025  
**Status:** ⚠️ DRAFT - AGUARDANDO APROVAÇÃO  
**Ação:** Nenhum código aplicado ao projeto

---

## 📋 **ARQUIVOS CRIADOS**

| Arquivo | Objetivo | Linhas |
|---------|----------|--------|
| **NavigationService.php.diff** | Adicionar menu "Pré-Cadastros" | 25 |
| **views/pre-cadastros/visitantes/index.php** | Lista de cadastros | 200 |
| **views/pre-cadastros/visitantes/form.php** | Formulário de novo cadastro | 250 |
| **js/pre-cadastros.js** | JavaScript da lista | 300 |
| **js/pre-cadastros-form.js** | JavaScript do formulário | 200 |
| **dashboard_autocomplete.js** | Integração com dashboard | 250 |

---

## 📱 **MENU LATERAL**

```
📋 Pré-Cadastros  (ícone: fas fa-address-card)
├─ 👥 Visitantes
└─ 🔧 Prestadores de Serviço
```

**Permissões:** Administrador + Porteiro

**Posição:** Entre "Importação" e "Configurações"

---

## 🎨 **VIEWS CRIADAS**

### **1. Lista de Pré-Cadastros (index.php)**

**Funcionalidades:**

1. ✅ **Cards de Estatísticas:**
   - Total (azul)
   - Válidos (verde)
   - Expirando (amarelo)
   - Expirados (vermelho)

2. ✅ **Filtros:**
   - Status (Todos, Válidos, Expirando, Expirados)
   - Busca (nome, documento ou empresa)

3. ✅ **Tabela:**
   - Nome, Empresa, Documento (mascarado), Placa
   - Validade, Status (badge colorido), Total de Entradas
   - Ações: Editar, Renovar, Excluir

4. ✅ **Modal de Renovação:**
   - Confirmação antes de renovar
   - Mostra nova data de validade

**Screenshot Conceitual:**
```
┌──────────────────────────────────────────┐
│ 📋 Pré-Cadastros de Visitantes           │
│ Cadastros reutilizáveis com validade 1 ano│
│                        [+ Novo Cadastro] │
├──────────────────────────────────────────┤
│ [📊 Total: 150] [✅ Válidos: 120]        │
│ [⚠️ Expirando: 25] [❌ Expirados: 5]    │
├──────────────────────────────────────────┤
│ Status: [Todos ▼] Busca: [_________]     │
├──────────────────────────────────────────┤
│ Nome      │Doc     │Validade │Ações      │
│ João Silva│CPF *07 │17/10/26 │[✏️][🔄][🗑️]│
└──────────────────────────────────────────┘
```

---

### **2. Formulário de Novo Cadastro (form.php)**

**Seções:**

1. ✅ **Dados Pessoais:**
   - Nome Completo (obrigatório)
   - Empresa (opcional)

2. ✅ **Documento:**
   - Tipo (select: 8 opções)
   - Número (com máscara automática)
   - País (visível apenas para docs internacionais)

3. ✅ **Veículo:**
   - Placa (opcional, uppercase automático)

4. ✅ **Período de Validade:**
   - Válido de (padrão: hoje)
   - Válido até (padrão: +1 ano)
   - Botão "Padrão (+1 ano)" para calcular

5. ✅ **Observações:**
   - Campo de texto livre

**Validações JavaScript:**
- ✅ Máscara automática por tipo de documento
- ✅ Mostrar/ocultar campo de país
- ✅ Validar data de fim > data de início
- ✅ Converter placa para uppercase

**Nota Informativa:**
```
ℹ️ O que é um Pré-Cadastro?
O pré-cadastro armazena os dados básicos do visitante 
(nome, documento, empresa) com validade de 1 ano. 
Quando o visitante chegar, o porteiro só precisa buscar 
o nome e adicionar funcionário responsável e setor, 
agilizando o atendimento.
```

---

## 💻 **JAVASCRIPT**

### **1. pre-cadastros.js (Lista)**

**Funcionalidades:**

```javascript
PreCadastros.init('visitante')
  ├─ loadCadastros()          // Carregar via API
  ├─ renderTable()            // Renderizar tabela
  ├─ showRenovarModal()       // Modal de renovação
  ├─ renovarCadastro()        // Renovar via API
  └─ confirmarExclusao()      // Confirm antes de excluir
```

**Filtros em Tempo Real:**
- Status: onChange → loadCadastros()
- Busca: keyup com debounce 500ms → loadCadastros()

**Badges de Status:**
- ✅ Válido (verde)
- ⚠️ Expirando (amarelo) + dias restantes
- ❌ Expirado (vermelho) + dias expirado

---

### **2. pre-cadastros-form.js (Formulário)**

**Funcionalidades:**

```javascript
Máscaras por Tipo:
├─ CPF:       000.000.000-00
├─ RG:        00.000.000-0
├─ CNH:       11 dígitos (sem máscara)
├─ Passaporte: Alfanumérico
└─ Outros:    Livre

Campo País:
├─ CPF/RG/CNH: Ocultar (Brasil automático)
└─ Outros:     Mostrar

Validade:
├─ Botão "Padrão": valid_from + 1 ano
└─ Auto-atualizar ao mudar valid_from
```

---

### **3. dashboard_autocomplete.js (Dashboard)**

**Fluxo Completo:**

```
1. Porteiro digita "joão" no campo de busca
   ↓
2. Autocomplete chama /api/pre-cadastros/buscar?q=joão
   ↓
3. Retorna:
   "João Silva - CPF ***.***.*07 ✅ Válido"
   "João Pedro - RG ***.***.*45 ⚠️ Expira em 15d"
   ↓
4. Porteiro seleciona
   ↓
5. Sistema verifica:
   ├─ Se EXPIRADO → Modal "Renovar?"
   ├─ Se EXPIRANDO → Confirm "Continuar?"
   └─ Se VÁLIDO → Preencher formulário
   ↓
6. Formulário PRÉ-PREENCHIDO:
   ✅ Nome: João Silva (readonly)
   ✅ Empresa: Fornecedor XYZ
   ✅ CPF: 123.456.789-00 (readonly)
   ✅ Placa: ABC1234
   📝 Funcionário Responsável: [_______] ← FOCO AQUI
   📝 Setor: [_______]
   ↓
7. Porteiro preenche apenas 2 campos
   ↓
8. Clica "Registrar Entrada"
   ↓
9. Sistema salva em visitantes_registros
   (vincula cadastro_id)
   ↓
10. PRONTO! ⚡ (5 segundos vs 2 minutos)
```

**Customização do Autocomplete:**
```javascript
// Aparência customizada com ícones de status
.autocomplete("instance")._renderItem = function(ul, item) {
    const statusIcon = {
        'valido': '✅',
        'expirando': '⚠️',
        'expirado': '❌'
    }[item.data.status_validade];
    
    return $("<li>")
        .append(`<div>${item.label} ${statusIcon}</div>`)
        .appendTo(ul);
};
```

---

## 🎨 **UX: BADGES E CORES**

### **Status de Validade:**

| Status | Badge | Cor | Ação Sugerida |
|--------|-------|-----|---------------|
| Válido | ✅ Válido | Verde (bg-success) | Usar normalmente |
| Expirando | ⚠️ Expira em Xd | Amarelo (bg-warning) | Alertar (confirm) |
| Expirado | ❌ Expirado há Xd | Vermelho (bg-danger) | Forçar renovação |

### **Cards de Estatísticas:**

```
┌─────────────────┐ ┌─────────────────┐
│ 📊 Total        │ │ ✅ Válidos      │
│    150          │ │    120          │
│ Cadastros ativos│ │ Prontos p/ uso  │
└─────────────────┘ └─────────────────┘
   (bg-primary)        (bg-success)

┌─────────────────┐ ┌─────────────────┐
│ ⚠️ Expirando    │ │ ❌ Expirados    │
│    25           │ │    5            │
│ Próximos 30 dias│ │ Prec. renovação │
└─────────────────┘ └─────────────────┘
   (bg-warning)        (bg-danger)
```

---

## 📱 **RESPONSIVIDADE**

Todas as views são responsivas com Bootstrap 5:

- ✅ Cards de stats: 4 colunas (desktop) → 2 (tablet) → 1 (mobile)
- ✅ Formulário: 2 colunas (desktop) → 1 (mobile)
- ✅ Tabela: Scroll horizontal em mobile
- ✅ Botões de ação: Compact em mobile

---

## ✅ **CHECKLIST DE APROVAÇÃO**

- [ ] Menu lateral faz sentido?
- [ ] Lista de cadastros tem todos os filtros necessários?
- [ ] Formulário é simples e claro?
- [ ] JavaScript de autocomplete está funcional?
- [ ] Integração com dashboard está completa?
- [ ] Badges e cores estão corretos?
- [ ] UX está intuitiva?

---

## 🚀 **PRÓXIMOS PASSOS (M5)**

Após aprovação:
1. Criar permissões RBAC (pre_cadastros.read/create/update/delete)
2. Atualizar AuthorizationService
3. Definir quais perfis têm acesso (Admin + Porteiro)

---

**AGUARDANDO APROVAÇÃO PARA PROSSEGUIR** ✋
