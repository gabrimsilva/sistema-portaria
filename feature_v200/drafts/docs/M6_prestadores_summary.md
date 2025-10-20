# M6 - CONTROLLER PRESTADORES (DRAFT)

**Data:** 17 de outubro de 2025  
**Status:** ⚠️ DRAFT - AGUARDANDO APROVAÇÃO  
**Ação:** Nenhum código aplicado ao projeto

---

## 📋 **ARQUIVOS CRIADOS**

| Arquivo | Objetivo | Linhas |
|---------|----------|--------|
| **PreCadastrosPrestadoresController.php** | Controller completo CRUD | 380 |
| **views/prestadores/index.php** | Lista de cadastros | 200 |
| **views/prestadores/form.php** | Formulário | 250 |
| **M6_prestadores_summary.md** | Documentação | - |

---

## 🎯 **FUNCIONALIDADES**

Idênticas ao controller de Visitantes, mas para **Prestadores de Serviço**:

### **CRUD Completo:**

1. ✅ **index()** - Lista de pré-cadastros
2. ✅ **create()** - Formulário de novo
3. ✅ **save()** - Salvar novo
4. ✅ **edit($id)** - Formulário de edição
5. ✅ **update($id)** - Atualizar existente
6. ✅ **delete($id)** - Excluir (soft delete)
7. ✅ **renovar($id)** - Renovar validade (+1 ano)

### **Helpers:**

- ✅ **getStats()** - Estatísticas (total, válidos, expirando, expirados)
- ✅ **validate()** - Validações de formulário
- ✅ **checkDuplicity()** - Verificar documento duplicado
- ✅ **normalizeDocument()** - Normalização type-aware

---

## 🔄 **DIFERENÇAS EM RELAÇÃO AO VISITANTES**

### **Tabelas:**
- Visitantes: `visitantes_cadastro`, `visitantes_registros`
- Prestadores: `prestadores_cadastro`, `prestadores_registros`

### **URLs:**
- Visitantes: `/pre-cadastros/visitantes`
- Prestadores: `/pre-cadastros/prestadores`

### **Cores:**
- Visitantes: Botão verde (btn-success)
- Prestadores: Botão amarelo (btn-warning) ← Match com dashboard

### **Campo Empresa:**
- Visitantes: Opcional
- Prestadores: **Obrigatório** (required)

**Justificativa:** Todo prestador tem vínculo com empresa contratada.

---

## 📊 **VIEWS CRIADAS**

### **1. index.php (Lista)**

**Elementos:**
```
┌──────────────────────────────────────────┐
│ 🔧 Pré-Cadastros de Prestadores          │
│ Cadastros reutilizáveis com validade 1 ano│
│                  [+ Novo Cadastro] (AMARELO)│
├──────────────────────────────────────────┤
│ [📊 Total] [✅ Válidos] [⚠️ Expirando] [❌ Expirados] │
├──────────────────────────────────────────┤
│ Filtros: Status, Busca                   │
├──────────────────────────────────────────┤
│ Tabela: Nome, Empresa*, Doc, Validade... │
└──────────────────────────────────────────┘
```

**Diferença:** Empresa sempre preenchida (obrigatório).

---

### **2. form.php (Formulário)**

**Seções:**
1. Dados Pessoais (Nome, **Empresa obrigatória**)
2. Documento (8 tipos)
3. Veículo (opcional)
4. Validade (+1 ano padrão)
5. Observações

**Campo obrigatório:**
```php
<input type="text" class="form-control" id="empresa" 
       name="empresa" required> <!-- ← OBRIGATÓRIO -->
```

**Validação backend:**
```php
if (empty($empresa)) {
    throw new Exception('Empresa é obrigatória para prestadores');
}
```

---

## 🎨 **IDENTIDADE VISUAL**

### **Cores do Dashboard (Match):**

| Tipo | Cor Botão | Cor Card | Motivo |
|------|-----------|----------|--------|
| Profissional Renner | Azul (primary) | bg-primary | Colaborador interno |
| Visitante | Verde (success) | bg-success | Acesso temporário |
| **Prestador** | **Amarelo (warning)** | **bg-warning** | Serviço externo |

**Consistência:** Botão amarelo no form = card amarelo no dashboard.

---

## 🔒 **PERMISSÕES (RBAC)**

Usa as **mesmas permissões** do módulo `pre_cadastros`:

- `pre_cadastros.read` (Admin, Porteiro)
- `pre_cadastros.create` (Admin, Porteiro)
- `pre_cadastros.update` (Admin, Porteiro)
- `pre_cadastros.delete` (Admin apenas)
- `pre_cadastros.renovar` (Admin, Porteiro)

**Sem necessidade de permissões separadas** - é o mesmo módulo.

---

## 📝 **JAVASCRIPT (Reutilização)**

O mesmo arquivo `pre-cadastros.js` funciona para **ambos**:

```javascript
// Visitantes
PreCadastros.init('visitante');

// Prestadores
PreCadastros.init('prestador');
```

**Automaticamente ajusta:**
- Endpoints de API (`/api/pre-cadastros/visitantes` vs `/prestadores`)
- Labels e textos
- URLs de redirecionamento

---

## 🔍 **VALIDAÇÕES ESPECÍFICAS**

### **1. Empresa Obrigatória**

```php
// No controller
if (empty($empresa)) {
    throw new Exception('Empresa é obrigatória para prestadores');
}
```

### **2. Duplicidade por Documento**

```php
// Verifica se já existe cadastro com mesmo doc_type + doc_number
$existing = $this->db->fetch(
    "SELECT id, nome FROM prestadores_cadastro 
     WHERE doc_type = ? AND doc_number = ? AND deleted_at IS NULL",
    [$doc_type, $doc_number]
);

if ($existing) {
    throw new Exception("Já existe cadastro: {$existing['nome']}");
}
```

### **3. Soft Delete com Verificação**

```php
// Não pode excluir se tiver registros vinculados
$count = $this->db->fetch(
    "SELECT COUNT(*) as total 
     FROM prestadores_registros 
     WHERE cadastro_id = ?",
    [$id]
);

if ($count['total'] > 0) {
    throw new Exception(
        "Não é possível excluir - existem {$count['total']} registros vinculados"
    );
}
```

---

## 📊 **ESTATÍSTICAS (Cards)**

```php
private function getStats() {
    return [
        'total' => COUNT(*) FROM prestadores_cadastro,
        'validos' => COUNT(*) WHERE status_validade = 'valido',
        'expirando' => COUNT(*) WHERE status_validade = 'expirando',
        'expirados' => COUNT(*) WHERE status_validade = 'expirado'
    ];
}
```

**Views utilizadas:**
- `vw_prestadores_cadastro_status` (cálculo derivado de validade)

---

## 🚀 **INTEGRAÇÃO COM DASHBOARD**

### **Autocomplete para Prestadores:**

```javascript
$('#busca_prestador_pre_cadastro').autocomplete({
    source: '/api/pre-cadastros/buscar?tipo=prestador',
    select: function(event, ui) {
        preencherFormularioPrestador(ui.item.data.id);
    }
});
```

**Campos preenchidos automaticamente:**
- ✅ Nome (readonly)
- ✅ Empresa (readonly)
- ✅ Documento (readonly)
- ✅ Placa
- 📝 Funcionário Responsável (foco aqui)
- 📝 Setor

---

## ✅ **CHECKLIST**

- [x] Controller criado (380 linhas)
- [x] CRUD completo (7 métodos)
- [x] Views (lista + formulário)
- [x] Empresa obrigatória
- [x] Botões amarelos (match dashboard)
- [x] Validações específicas
- [x] Soft delete com verificação
- [x] JavaScript reutilizado
- [x] Permissões RBAC aplicadas

---

## 📁 **ESTRUTURA FINAL**

```
feature_v200/drafts/
├─ controllers/
│  ├─ PreCadastrosVisitantesController.php    (443 linhas)
│  └─ PreCadastrosPrestadoresController.php   (380 linhas)
├─ views/pre-cadastros/
│  ├─ visitantes/
│  │  ├─ index.php
│  │  └─ form.php
│  └─ prestadores/
│     ├─ index.php
│     └─ form.php
└─ js/
   ├─ pre-cadastros.js          (funciona para ambos)
   └─ pre-cadastros-form.js     (funciona para ambos)
```

---

## 🎯 **PRÓXIMO PASSO: M7 (Resumo Executivo)**

Criar documento consolidado com:
- Todas as especificações (M2-M6)
- Checklist de aprovação
- Plano de aplicação

---

**M6 CONCLUÍDA - AGUARDANDO M7** ✅
