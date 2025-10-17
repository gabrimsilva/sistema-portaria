# M1 - DESCOBERTA: Sistema de Pré-Cadastros com Validade

**Data:** 17 de outubro de 2025  
**Objetivo:** Identificar estrutura atual, pontos de integração e mapa de impacto

---

## 📊 ESTRUTURA ATUAL DO BANCO DE DADOS

### Tabela `visitantes_novo`
**Campos Existentes:**
```sql
- id (serial, PK)
- hora_entrada (timestamp)
- hora_saida (timestamp)
- nome (varchar 255)
- cpf (varchar 14)
- empresa (varchar 255)
- funcionario_responsavel (varchar 255)
- setor (varchar 255)
- placa_veiculo (varchar 20)
- doc_type (ENUM) - Sistema multi-documentos
- doc_number (varchar 50)
- doc_country (varchar 100)
- valid_from (timestamp) ⚠️ JÁ EXISTE
- valid_until (timestamp) ⚠️ JÁ EXISTE
- validity_status (varchar 20) ⚠️ JÁ EXISTE
- data_validade (timestamp) ⚠️ JÁ EXISTE
- created_at, updated_at
- deleted_at, deletion_reason (soft delete)
- anonymized_at (LGPD)
```

### Tabela `prestadores_servico`
**Campos Existentes:**
```sql
- id (serial, PK)
- entrada (timestamp)
- saida (timestamp)
- nome (varchar 255)
- cpf (varchar 14)
- observacao (text)
- empresa (varchar 255)
- setor (varchar 255)
- placa_veiculo (varchar 20)
- funcionario_responsavel (varchar 255)
- doc_type (ENUM) - Sistema multi-documentos
- doc_number (varchar 50)
- doc_country (varchar 100)
- valid_from (timestamp) ⚠️ JÁ EXISTE
- valid_until (timestamp) ⚠️ JÁ EXISTE
- validity_status (varchar 20) ⚠️ JÁ EXISTE
- data_validade (timestamp) ⚠️ JÁ EXISTE
- created_at, updated_at
- deleted_at, deletion_reason (soft delete)
- anonymized_at (LGPD)
```

---

## ⚠️ **PROBLEMA IDENTIFICADO: Estrutura Inadequada**

### **Issue #1: Mistura de Pré-Cadastro com Registro de Acesso**

As tabelas atuais **MISTURAM** duas responsabilidades diferentes:

1. **Pré-Cadastro** (dados pessoais que duram 1 ano)
   - Nome, documento, empresa, placa
   - Validade de 1 ano
   - Pode ser reutilizado em múltiplas entradas

2. **Registro de Acesso** (evento pontual de entrada/saída)
   - Entrada: 17/10/2025 14:30
   - Saída: 17/10/2025 18:00
   - Observações específicas daquela visita

**Problema:** Um registro na tabela atual representa UM acesso (com entrada/saída), mas os campos `valid_from/valid_until` sugerem validade de 1 ano. Isso cria conflito conceitual.

### **Issue #2: Campos de Validade Inutilizados**

Os campos `valid_from`, `valid_until`, `validity_status`, `data_validade` **já existem** mas:
- ❌ Não são preenchidos no cadastro
- ❌ Não são validados na entrada
- ❌ Não impedem entrada de cadastros expirados
- ❌ Não aparecem nas telas

---

## 🎯 **SOLUÇÃO PROPOSTA: Separação de Responsabilidades**

### **Arquitetura Nova:**

```
┌─────────────────────────────┐
│ visitantes_cadastro         │  ← NOVO (Pré-Cadastro)
├─────────────────────────────┤
│ - id                        │
│ - nome                      │
│ - empresa                   │
│ - doc_type/doc_number/país  │
│ - placa_veiculo             │
│ - valid_from (1 ano padrão) │
│ - valid_until               │
│ - ativo (boolean)           │
│ - created_at/updated_at     │
└──────────┬──────────────────┘
           │
           │ 1:N (Um cadastro, múltiplas entradas)
           │
           ▼
┌─────────────────────────────┐
│ visitantes_registros        │  ← NOVO (Eventos de Acesso)
├─────────────────────────────┤
│ - id                        │
│ - cadastro_id (FK)          │ ← LINK!
│ - entrada (timestamp)       │
│ - saida (timestamp)         │
│ - observacoes_entrada       │
│ - funcionario_responsavel   │
│ - created_at                │
└─────────────────────────────┘
```

**Mesma estrutura para Prestadores:**
- `prestadores_cadastro` (pré-cadastro)
- `prestadores_registros` (eventos de acesso)

---

## 🔍 **MAPA DE IMPACTO**

### **1. MENU LATERAL (NavigationService.php)**

**Arquivo:** `src/services/NavigationService.php`

**Alteração Necessária:** Adicionar novo item de menu "Pré-Cadastros"

```php
// Linha 15-91: array $navigationStructure
[
    'id' => 'pre-cadastros',
    'label' => 'Pré-Cadastros',
    'url' => '#',
    'icon' => 'fas fa-address-card',
    'permission' => ['administrador', 'porteiro'],  // RBAC
    'children' => [
        [
            'id' => 'pre-cadastros-visitantes',
            'label' => 'Visitantes',
            'url' => '/pre-cadastros/visitantes',
            'icon' => 'fas fa-users'
        ],
        [
            'id' => 'pre-cadastros-prestadores',
            'label' => 'Prestadores',
            'url' => '/pre-cadastros/prestadores',
            'icon' => 'fas fa-tools'
        ]
    ]
]
```

---

### **2. ROTAS (public/index.php)**

**Arquivo:** `public/index.php`

**Padrão Atual:** Rotas definidas com `switch ($path)` (linha 96+)

**Rotas Novas a Adicionar:**

```php
case 'pre-cadastros/visitantes':
    require_once '../src/controllers/PreCadastrosVisitantesController.php';
    $controller = new PreCadastrosVisitantesController();
    $action = $_GET['action'] ?? 'index';
    // Ações: index, new, save, edit, update, delete
    break;

case 'pre-cadastros/prestadores':
    require_once '../src/controllers/PreCadastrosPrestadoresController.php';
    $controller = new PreCadastrosPrestadoresController();
    $action = $_GET['action'] ?? 'index';
    break;
```

**APIs de Busca (para dashboard):**

```php
case 'api/pre-cadastros/buscar':
    // Busca por CPF/nome para autocomplete no dashboard
    require_once '../src/controllers/ApiPreCadastrosController.php';
    $controller = new ApiPreCadastrosController();
    $controller->buscar();
    break;
```

---

### **3. CONTROLLERS**

**Controllers Novos a Criar:**

1. **`src/controllers/PreCadastrosVisitantesController.php`**
   - `index()` → Lista pré-cadastros
   - `create()` → Formulário de novo pré-cadastro
   - `save()` → Salva pré-cadastro (validade padrão 1 ano)
   - `edit($id)` → Formulário de edição
   - `update($id)` → Atualiza pré-cadastro
   - `delete($id)` → Desativa pré-cadastro (soft delete)

2. **`src/controllers/PreCadastrosPrestadoresController.php`**
   - Mesma estrutura do VisitantesController

3. **`src/controllers/ApiPreCadastrosController.php`**
   - `buscar()` → API para autocomplete no dashboard
   - `verificarValidade($id)` → Verifica se cadastro está válido

**Controllers Existentes a MODIFICAR:**

4. **`src/controllers/DashboardController.php`**
   - Adicionar busca de pré-cadastros válidos
   - Detectar cadastros expirados e solicitar recadastro
   - Preencher formulário com dados do pré-cadastro

---

### **4. VIEWS**

**Views Novas a Criar:**

```
views/
├─ pre-cadastros/
│  ├─ visitantes/
│  │  ├─ index.php          (lista com status de validade)
│  │  ├─ form.php           (cadastro SIMPLIFICADO - sem foto, sem entrada/saída)
│  │  └─ edit.php           (edição)
│  └─ prestadores/
│     ├─ index.php
│     ├─ form.php
│     └─ edit.php
```

**Views Existentes a MODIFICAR:**

```
views/dashboard/index.php
  → Adicionar campo de busca "CPF/Nome" com autocomplete
  → Mostrar resultado da busca (cadastro válido/expirado)
  → Preencher formulário existente com dados do pré-cadastro
```

---

### **5. JAVASCRIPT**

**Arquivos Novos:**

```
public/assets/js/
├─ pre_cadastros_visitantes.js   (validação, máscara, cálculo de validade)
├─ pre_cadastros_prestadores.js
└─ dashboard_busca_cadastro.js   (autocomplete para busca)
```

**Funcionalidades:**

1. **Cálculo Automático de Validade:**
   ```javascript
   // Quando data início muda, auto-preencher data fim (+1 ano)
   $('#valid_from').on('change', function() {
       const dataInicio = new Date($(this).val());
       const dataFim = new Date(dataInicio);
       dataFim.setFullYear(dataFim.getFullYear() + 1);
       $('#valid_until').val(dataFim.toISOString().split('T')[0]);
   });
   ```

2. **Autocomplete no Dashboard:**
   ```javascript
   $('#busca_cadastro').autocomplete({
       source: '/api/pre-cadastros/buscar',
       select: function(event, ui) {
           // Preencher formulário com dados do pré-cadastro
           $('#nome').val(ui.item.nome);
           $('#empresa').val(ui.item.empresa);
           // ...
       }
   });
   ```

---

### **6. RBAC (AuthorizationService.php)**

**Arquivo:** `src/services/AuthorizationService.php`

**Permissões a Adicionar:**

```php
// Linha 14-43: const PERMISSIONS
'administrador' => [
    // ... permissões existentes
    'pre_cadastros.create',
    'pre_cadastros.read',
    'pre_cadastros.update',
    'pre_cadastros.delete',
],
'porteiro' => [
    // ... permissões existentes
    'pre_cadastros.create',
    'pre_cadastros.read',
    'pre_cadastros.update',
    // NÃO TEM: delete (só admin pode)
]
```

---

## 📋 **CAMPOS DO FORMULÁRIO DE PRÉ-CADASTRO**

### **Visitantes (Simplificado - SEM foto)**

```
┌─────────────────────────────────────┐
│ 👤 Dados Pessoais                   │
├─────────────────────────────────────┤
│ Nome Completo: [.................]  │
│ Empresa: [........................] │
├─────────────────────────────────────┤
│ 📄 Documento                        │
├─────────────────────────────────────┤
│ Tipo: [CPF ▼]                       │
│ Número: [123.456.789-00..........]  │
│ País: [Brasil ▼]                    │
├─────────────────────────────────────┤
│ 🚗 Veículo (opcional)               │
├─────────────────────────────────────┤
│ Placa: [ABC1234..] □ A pé           │
├─────────────────────────────────────┤
│ ⏰ Período de Validade              │
├─────────────────────────────────────┤
│ Data Início: [17/10/2025 📅]        │
│ Data Fim: [17/10/2026 📅]           │
│   ℹ️ Padrão: 1 ano                  │
├─────────────────────────────────────┤
│ 📝 Observações (opcional)           │
├─────────────────────────────────────┤
│ [Visitante recorrente para...]      │
│                                     │
├─────────────────────────────────────┤
│ [✅ Salvar] [❌ Cancelar]           │
└─────────────────────────────────────┘
```

**Campos NÃO incluídos (só na entrada):**
- ❌ Foto (capturada na primeira entrada)
- ❌ Funcionário Responsável (informado na hora da entrada)
- ❌ Setor (informado na hora da entrada)
- ❌ Data/Hora de Entrada
- ❌ Data/Hora de Saída

---

## 🔄 **FLUXO DE INTEGRAÇÃO COM DASHBOARD**

### **Cenário 1: Entrada com Pré-Cadastro Válido**

```
1. Porteiro acessa Dashboard
2. Digita "João Silva" ou CPF no campo de busca
3. Sistema busca em visitantes_cadastro
4. Encontra: João Silva (válido até 17/10/2026)
5. Exibe: [✅ Cadastro Válido - Clique para usar]
6. Porteiro clica
7. Sistema PREENCHE o formulário existente com:
   - Nome: João Silva
   - Empresa: Fornecedor XYZ
   - Documento: CPF 123.456.789-00
   - Placa: ABC1234
   - Campo hidden: cadastro_id = 123
8. Porteiro apenas preenche:
   - Funcionário Responsável
   - Setor (opcional)
   - Observações da entrada
9. Clica "Registrar Entrada"
10. Sistema cria registro em visitantes_registros com cadastro_id
```

### **Cenário 2: Entrada com Cadastro Expirado**

```
1. Busca retorna: Maria Santos (❌ Expirado em 01/10/2025)
2. Sistema exibe alerta:
   ┌────────────────────────────────────┐
   │ ⚠️ Cadastro Expirado               │
   ├────────────────────────────────────┤
   │ Este cadastro expirou há 16 dias.  │
   │                                    │
   │ [🔄 Renovar (+1 ano)]              │
   │ [✏️ Atualizar Dados]               │
   │ [❌ Cancelar]                      │
   └────────────────────────────────────┘
3. Se clicar "Renovar":
   - valid_until = hoje + 1 ano
   - Prossegue com entrada normalmente
```

---

## 📊 **ESTATÍSTICAS DA ANÁLISE**

| Item | Quantidade |
|------|------------|
| **Tabelas a Criar** | 4 (visitantes_cadastro, visitantes_registros, prestadores_cadastro, prestadores_registros) |
| **Controllers a Criar** | 3 (PreCadastrosVisitantes, PreCadastrosPrestadores, ApiPreCadastros) |
| **Controllers a Modificar** | 1 (DashboardController) |
| **Views a Criar** | 6 (3 para visitantes + 3 para prestadores) |
| **Views a Modificar** | 1 (dashboard/index.php) |
| **JS a Criar** | 3 arquivos |
| **Itens de Menu a Adicionar** | 1 (com 2 subitens) |
| **Permissões RBAC** | 4 (create, read, update, delete) |
| **Rotas a Adicionar** | 3 rotas principais |

---

## ⚙️ **ESTRATÉGIA DE MIGRAÇÃO**

### **Opção A: Criar Novas Tabelas (RECOMENDADO)**

✅ **Vantagens:**
- Não altera estrutura existente
- Separação clara de responsabilidades
- Facilita testes A/B
- Rollback simples

❌ **Desvantagens:**
- Dados históricos ficam na tabela antiga
- Precisa migrar dados antigos (opcional)

### **Opção B: Reaproveitar Campos Existentes**

❌ **NÃO RECOMENDADO** - Campos valid_from/valid_until nas tabelas atuais causam confusão conceitual (um registro = um acesso, mas validade de 1 ano?)

---

## ✅ **DECISÕES TÉCNICAS**

| Decisão | Escolha | Justificativa |
|---------|---------|---------------|
| **Arquitetura** | Separar pré-cadastro de registros | Clareza conceitual, 1:N |
| **Validade Padrão** | 1 ano a partir de hoje | Automatiza gestão |
| **Status** | Derivado via VIEW | Sempre atualizado |
| **Foto** | Só na primeira entrada | Simplifica pré-cadastro |
| **RBAC** | Admin + Porteiro | Portaria gerencia visitantes recorrentes |
| **Expiração** | Silenciosa (sem notificação) | Detecta na tentativa de entrada |

---

## 🎯 **PRÓXIMOS PASSOS (M2)**

1. Criar migrations para 4 tabelas novas
2. Criar VIEW para status derivado
3. Criar índices de performance
4. Definir constraints e foreign keys

---

**Status:** ✅ Descoberta Concluída  
**Próximo:** M2 - Modelagem & Migrations (DRAFT)
