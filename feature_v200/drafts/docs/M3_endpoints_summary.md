# M3 - ENDPOINTS & ROTAS (DRAFT)

**Data:** 17 de outubro de 2025  
**Status:** ⚠️ DRAFT - AGUARDANDO APROVAÇÃO  
**Ação:** Nenhum código aplicado ao projeto

---

## 📋 **ARQUIVOS CRIADOS**

| Arquivo | Objetivo | Linhas |
|---------|----------|--------|
| **index.php.routes.diff** | Rotas principais (visitantes/prestadores) | 80 |
| **index.php.api.diff** | Rotas de API (busca, verificação, stats) | 75 |
| **PreCadastrosVisitantesController.php** | Controller completo | 450 |
| **ApiPreCadastrosController.php** | APIs de integração | 300 |

---

## 🛣️ **ROTAS PRINCIPAIS**

### **1. Pré-Cadastros - Visitantes**

```
GET  /pre-cadastros/visitantes                → index()
GET  /pre-cadastros/visitantes?action=new     → create()
POST /pre-cadastros/visitantes?action=save    → save()
GET  /pre-cadastros/visitantes?action=edit&id=123 → edit()
POST /pre-cadastros/visitantes?action=update  → update()
POST /pre-cadastros/visitantes?action=delete&id=123 → delete()
POST /pre-cadastros/visitantes?action=renovar&id=123 → renovar()
```

### **2. Pré-Cadastros - Prestadores**

```
GET  /pre-cadastros/prestadores                → index()
(Mesma estrutura de rotas do visitantes)
```

---

## 🔌 **ROTAS DE API**

### **1. Busca (Autocomplete Dashboard)**

**Endpoint:**
```
GET /api/pre-cadastros/buscar?q=joão&tipo=visitante
```

**Parâmetros:**
- `q` (string): Termo de busca (nome, documento ou empresa)
- `tipo` (string): 'visitante' ou 'prestador'

**Resposta:**
```json
{
  "success": true,
  "results": [
    {
      "id": 123,
      "label": "João Silva - CPF ***.***.*07 ✅",
      "value": "João Silva",
      "data": {
        "id": 123,
        "nome": "João Silva",
        "empresa": "Fornecedor XYZ",
        "doc_type": "CPF",
        "doc_number": "12345678900",
        "status_validade": "valido",
        "dias_restantes": 283
      }
    }
  ]
}
```

**Uso no Dashboard:**
```javascript
$('#busca_cadastro').autocomplete({
    source: function(request, response) {
        $.get('/api/pre-cadastros/buscar', {
            q: request.term,
            tipo: 'visitante'
        }, function(data) {
            response(data.results);
        });
    },
    select: function(event, ui) {
        preencherFormulario(ui.item.data);
    }
});
```

---

### **2. Verificar Validade**

**Endpoint:**
```
GET /api/pre-cadastros/verificar-validade?id=123&tipo=visitante
```

**Resposta:**
```json
{
  "success": true,
  "valido": false,
  "status": "expirado",
  "dias_expirado": 16,
  "valid_until": "2025-10-01",
  "mensagem": "Expirado há 16 dias"
}
```

**Uso:**
```javascript
// Verificar antes de registrar entrada
$.get('/api/pre-cadastros/verificar-validade', {
    id: cadastroId,
    tipo: 'visitante'
}, function(data) {
    if (!data.valido) {
        mostrarAlertaExpir ado(data.mensagem);
    }
});
```

---

### **3. Obter Dados (Preencher Formulário)**

**Endpoint:**
```
GET /api/pre-cadastros/obter-dados?id=123&tipo=visitante
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "cadastro_id": 123,
    "nome": "João Silva",
    "empresa": "Fornecedor XYZ",
    "doc_type": "CPF",
    "doc_number": "12345678900",
    "doc_country": "Brasil",
    "placa_veiculo": "ABC1234",
    "observacoes": "Visitante recorrente"
  }
}
```

**Uso:**
```javascript
// Preencher formulário do dashboard
function preencherFormulario(cadastroId) {
    $.get('/api/pre-cadastros/obter-dados', {
        id: cadastroId,
        tipo: 'visitante'
    }, function(data) {
        $('#nome').val(data.data.nome);
        $('#empresa').val(data.data.empresa);
        $('#doc_type').val(data.data.doc_type);
        $('#doc_number').val(data.data.doc_number);
        $('#placa_veiculo').val(data.data.placa_veiculo);
        $('#cadastro_id').val(data.data.cadastro_id); // hidden
    });
}
```

---

### **4. Renovar Cadastro**

**Endpoint:**
```
POST /api/pre-cadastros/renovar
```

**Body:**
```json
{
  "id": 123,
  "tipo": "visitante"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Cadastro renovado até 17/10/2026",
  "valid_until": "2026-10-17"
}
```

**Uso:**
```javascript
// Renovar cadastro expirado
$('#btn-renovar').click(function() {
    $.ajax({
        url: '/api/pre-cadastros/renovar',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            id: cadastroId,
            tipo: 'visitante'
        }),
        success: function(data) {
            alert(data.message);
            prosseguirComEntrada();
        }
    });
});
```

---

### **5. Estatísticas**

**Endpoint:**
```
GET /api/pre-cadastros/stats
```

**Resposta:**
```json
{
  "success": true,
  "stats": {
    "visitantes": {
      "total": 150,
      "validos": 120,
      "expirando": 25,
      "expirados": 5
    },
    "prestadores": {
      "total": 80,
      "validos": 65,
      "expirando": 10,
      "expirados": 5
    }
  }
}
```

---

### **6. Listar (DataTables)**

**Endpoint:**
```
GET /api/pre-cadastros/visitantes/list?status=valido&search=joão
```

**Parâmetros:**
- `status`: 'all', 'valido', 'expirando', 'expirado'
- `search`: Termo de busca

**Resposta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "nome": "João Silva",
      "empresa": "Fornecedor XYZ",
      "doc_type": "CPF",
      "doc_number": "12345678900",
      "valid_until": "2026-10-17",
      "status_validade": "valido",
      "dias_restantes": 283,
      "total_entradas": 15
    }
  ]
}
```

---

## 🎯 **FLUXO COMPLETO: Dashboard → Pré-Cadastro → Entrada**

### **Passo 1: Porteiro busca visitante**
```javascript
// Dashboard: campo de busca
$('#busca_visitante').autocomplete({
    source: '/api/pre-cadastros/buscar?tipo=visitante',
    minLength: 2
});
```

### **Passo 2: Seleciona cadastro**
```javascript
// Ao selecionar:
select: function(event, ui) {
    var cadastro = ui.item.data;
    
    // Verificar validade
    if (cadastro.status_validade === 'expirado') {
        mostrarModalRenovacao(cadastro);
    } else {
        preencherFormularioEntrada(cadastro.id);
    }
}
```

### **Passo 3: Preencher formulário**
```javascript
function preencherFormularioEntrada(cadastroId) {
    $.get('/api/pre-cadastros/obter-dados', {
        id: cadastroId,
        tipo: 'visitante'
    }, function(data) {
        // Preencher campos
        $('#nome').val(data.data.nome);
        $('#empresa').val(data.data.empresa);
        // ...
        
        // Campo hidden para vincular
        $('#cadastro_id').val(cadastroId);
        
        // Focar no próximo campo (funcionário responsável)
        $('#funcionario_responsavel').focus();
    });
}
```

### **Passo 4: Registrar entrada**
```javascript
$('#form_entrada').submit(function(e) {
    e.preventDefault();
    
    var cadastroId = $('#cadastro_id').val();
    var entrada = $('#entrada').val();
    var funcionarioResp = $('#funcionario_responsavel').val();
    var setor = $('#setor').val();
    
    // Salvar em visitantes_registros (não em visitantes_novo)
    $.post('/api/visitantes/registrar-entrada', {
        cadastro_id: cadastroId,
        entrada: entrada,
        funcionario_responsavel: funcionarioResp,
        setor: setor
    }, function(response) {
        alert('Entrada registrada!');
        limparFormulario();
    });
});
```

---

## 🎨 **CONTROLLER: PreCadastrosVisitantesController**

### **Métodos Implementados:**

| Método | Ação | Descrição |
|--------|------|-----------|
| `index()` | Listar | Exibe lista de pré-cadastros com filtros |
| `create()` | Novo | Formulário de criação |
| `save()` | Salvar | Cria novo pré-cadastro (validade 1 ano) |
| `edit($id)` | Editar | Formulário de edição |
| `update()` | Atualizar | Atualiza dados do pré-cadastro |
| `delete($id)` | Excluir | Soft delete (se sem registros) |
| `renovar($id)` | Renovar | Estende validade +1 ano |
| `listJson()` | API Lista | JSON para DataTables |

### **Validações Incluídas:**

1. ✅ **Normalização de Documento:**
   - CPF/RG/CNH: apenas dígitos
   - Passaporte/RNE/DNI: alfanuméricos uppercase

2. ✅ **Validação de Duplicidade:**
   - Não permite 2 cadastros ativos com mesmo documento
   - Permite recadastrar após desativar (histórico preservado)

3. ✅ **Validação de Validade:**
   - `valid_until` deve ser > `valid_from`
   - Padrão automático: +1 ano se não informado

4. ✅ **Soft Delete:**
   - Não permite excluir se tiver registros vinculados
   - Marca `deleted_at` ao invés de DELETE físico

5. ✅ **Auditoria:**
   - Todos os CUD (create, update, delete) registrados em audit_log

---

## 🔒 **PERMISSÕES RBAC**

Todas as rotas verificam permissões:

```php
$this->authService->requirePermission('pre_cadastros.read');    // Listar
$this->authService->requirePermission('pre_cadastros.create');  // Criar
$this->authService->requirePermission('pre_cadastros.update');  // Editar/Renovar
$this->authService->requirePermission('pre_cadastros.delete');  // Excluir
```

**Perfis com Acesso:**
- ✅ Administrador (todas as permissões)
- ✅ Porteiro (todas exceto delete)

---

## ✅ **CHECKLIST DE APROVAÇÃO**

Antes de aplicar, verifique:

- [ ] Rotas em `index.php` fazem sentido?
- [ ] Endpoints de API cobrem todos os casos de uso?
- [ ] Controller tem todas as validações necessárias?
- [ ] Auditoria está completa?
- [ ] RBAC está correto (Admin + Porteiro)?
- [ ] Fluxo Dashboard → Busca → Preencher está claro?

---

## 🚀 **PRÓXIMOS PASSOS (M4)**

Após aprovação:
1. Criar views (forms, listas, modals)
2. Criar JavaScript (autocomplete, validação)
3. Atualizar menu lateral (NavigationService)
4. Atualizar dashboard (busca + integração)

---

**AGUARDANDO APROVAÇÃO PARA PROSSEGUIR** ✋
