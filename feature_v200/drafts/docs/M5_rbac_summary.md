# M5 - RBAC (DRAFT)

**Data:** 17 de outubro de 2025  
**Status:** ⚠️ DRAFT - AGUARDANDO APROVAÇÃO  
**Ação:** Nenhum código aplicado ao projeto

---

## 📋 **ARQUIVOS CRIADOS**

| Arquivo | Objetivo | Tipo |
|---------|----------|------|
| **AuthorizationService.php.diff** | Adicionar permissões no sistema legado | Snippet |
| **005_create_rbac_permissions.sql** | Criar permissões no RBAC moderno | SQL |
| **M5_rbac_summary.md** | Documentação completa | Docs |

---

## 🔒 **PERMISSÕES CRIADAS**

### **Módulo: `pre_cadastros`**

| Permissão | Descrição | Admin | Porteiro |
|-----------|-----------|-------|----------|
| `pre_cadastros.read` | Visualizar lista e estatísticas | ✅ | ✅ |
| `pre_cadastros.create` | Criar novos pré-cadastros | ✅ | ✅ |
| `pre_cadastros.update` | Editar cadastros existentes | ✅ | ✅ |
| `pre_cadastros.delete` | Excluir cadastros (soft delete) | ✅ | ❌ |
| `pre_cadastros.renovar` | Renovar validade (+1 ano) | ✅ | ✅ |

---

## 👥 **PERFIS DE ACESSO**

### **1. Administrador**

**Permissões:** TODAS (5/5)

```php
'administrador' => [
    // ... permissões existentes
    'pre_cadastros.read',
    'pre_cadastros.create',
    'pre_cadastros.update',
    'pre_cadastros.delete',    // ✅ Pode excluir
    'pre_cadastros.renovar'
]
```

**Pode fazer:**
- ✅ Ver lista de pré-cadastros
- ✅ Criar novos cadastros
- ✅ Editar cadastros existentes
- ✅ Excluir cadastros (se sem registros vinculados)
- ✅ Renovar cadastros expirados

---

### **2. Porteiro**

**Permissões:** 4 de 5 (sem delete)

```php
'porteiro' => [
    // ... permissões existentes
    'pre_cadastros.read',
    'pre_cadastros.create',
    'pre_cadastros.update',
    'pre_cadastros.renovar'
    // ❌ NÃO tem pre_cadastros.delete
]
```

**Pode fazer:**
- ✅ Ver lista de pré-cadastros
- ✅ Criar novos cadastros
- ✅ Editar cadastros existentes
- ✅ Renovar cadastros expirados
- ❌ **NÃO PODE** excluir cadastros

**Justificativa:** Porteiro não deve excluir dados históricos. Apenas Admin pode fazer limpeza de dados.

---

### **3. Segurança, Recepção**

**Permissões:** NENHUMA (0/5)

Esses perfis **NÃO** têm acesso ao módulo de pré-cadastros.

**Justificativa:** 
- Segurança e Recepção trabalham com registros de acesso pontuais
- Pré-cadastros são gerenciados apenas por Admin e Porteiro

---

## 🔐 **SISTEMA HÍBRIDO**

O sistema suporta **2 métodos de RBAC**:

### **Método 1: Sistema Legado (Array)**

```php
// src/services/AuthorizationService.php
private const PERMISSIONS = [
    'administrador' => ['pre_cadastros.read', ...],
    'porteiro' => ['pre_cadastros.read', ...]
];
```

**Vantagens:**
- ✅ Simples e direto
- ✅ Sem dependência de banco
- ✅ Performance alta

**Uso:** Compatibilidade com sistema existente

---

### **Método 2: RBAC Moderno (Banco de Dados)**

```sql
-- Tabelas:
rbac_modules
rbac_permissions
rbac_roles
rbac_role_permissions
```

**Vantagens:**
- ✅ Gerenciamento via interface
- ✅ Permissões dinâmicas
- ✅ Auditoria completa

**Uso:** Sistema futuro (se implementado)

---

## 📊 **FLUXO DE AUTORIZAÇÃO**

```
Usuário tenta acessar /pre-cadastros/visitantes
   ↓
Controller verifica:
$this->authService->requirePermission('pre_cadastros.read');
   ↓
AuthorizationService verifica:
   ├─ 1️⃣ Sistema Legado (PERMISSIONS array)
   │  └─ Se encontrou → Permitir ✅
   │
   └─ 2️⃣ RBAC Moderno (banco de dados)
      └─ Se encontrou → Permitir ✅
   ↓
Se nenhum encontrou → Negar ❌ (HTTP 403)
```

---

## 🛡️ **VALIDAÇÕES NOS CONTROLLERS**

### **PreCadastrosVisitantesController:**

```php
public function index() {
    // Verificar permissão de leitura
    $this->authService->requirePermission('pre_cadastros.read');
    // ... código
}

public function create() {
    // Verificar permissão de criação
    $this->authService->requirePermission('pre_cadastros.create');
    // ... código
}

public function update() {
    // Verificar permissão de atualização
    $this->authService->requirePermission('pre_cadastros.update');
    // ... código
}

public function delete($id) {
    // Verificar permissão de exclusão
    $this->authService->requirePermission('pre_cadastros.delete');
    // ... código
}

public function renovar($id) {
    // Verificar permissão de renovação
    $this->authService->requirePermission('pre_cadastros.renovar');
    // ... código
}
```

---

### **ApiPreCadastrosController:**

```php
public function buscar() {
    // API de busca requer leitura
    $this->authService->requirePermission('pre_cadastros.read');
    // ... código
}

public function renovar() {
    // API de renovação requer permissão específica
    $this->authService->requirePermission('pre_cadastros.renovar');
    // ... código
}
```

---

## 🎯 **EXEMPLOS DE USO**

### **Exemplo 1: Admin cria pré-cadastro**

```
Admin acessa: /pre-cadastros/visitantes?action=new
   ↓
Controller verifica: pre_cadastros.create
   ↓
Admin TEM permissão ✅
   ↓
Formulário exibido
```

---

### **Exemplo 2: Porteiro tenta excluir**

```
Porteiro clica em "Excluir" na lista
   ↓
Controller verifica: pre_cadastros.delete
   ↓
Porteiro NÃO TEM permissão ❌
   ↓
HTTP 403 Forbidden
```

**Mensagem de erro:**
```json
{
  "success": false,
  "message": "Acesso negado. Permissão insuficiente.",
  "required_permission": "pre_cadastros.delete",
  "user_profile": "porteiro"
}
```

---

### **Exemplo 3: Segurança tenta acessar menu**

```
Segurança acessa: /pre-cadastros/visitantes
   ↓
Controller verifica: pre_cadastros.read
   ↓
Segurança NÃO TEM permissão ❌
   ↓
HTTP 403 Forbidden
```

**Comportamento no Menu:**
Menu "Pré-Cadastros" **NÃO APARECE** para Segurança/Recepção.

---

## 📱 **VISIBILIDADE DO MENU**

### **NavigationService (Filter)**

O menu já filtra automaticamente por perfil:

```php
// NavigationService.php
[
    'id' => 'pre-cadastros',
    'label' => 'Pré-Cadastros',
    'permission' => ['administrador', 'porteiro'], // ← Filtro
    'children' => [...]
]
```

**Resultado:**
- ✅ Admin → vê menu
- ✅ Porteiro → vê menu
- ❌ Segurança → **não vê** menu
- ❌ Recepção → **não vê** menu

---

## 🔍 **AUDITORIA**

Todas as ações em pré-cadastros são auditadas:

```php
// Exemplo de log de auditoria
$this->auditService->log(
    'pre_cadastros_visitantes',    // módulo
    'create',                        // ação
    $id,                             // ID do registro
    "Pré-cadastro criado: $nome"    // descrição
);
```

**Logs registrados:**
- ✅ Criação de cadastro
- ✅ Edição de cadastro
- ✅ Renovação de cadastro
- ✅ Exclusão de cadastro

---

## ✅ **CHECKLIST DE APROVAÇÃO**

- [ ] Permissões fazem sentido (5 tipos)?
- [ ] Admin tem todas as permissões?
- [ ] Porteiro não pode excluir (correto)?
- [ ] Segurança/Recepção sem acesso (correto)?
- [ ] Sistema híbrido (legado + moderno) está ok?
- [ ] Validações nos controllers estão completas?

---

## 🚀 **PRÓXIMOS PASSOS (M6)**

Após aprovação:
1. Integrar dashboard com pré-cadastros
2. Adicionar campo de busca com autocomplete
3. Atualizar formulários de entrada para aceitar cadastro_id
4. Criar vínculo entre pré-cadastro e registros de acesso

---

**AGUARDANDO APROVAÇÃO PARA PROSSEGUIR** ✋
