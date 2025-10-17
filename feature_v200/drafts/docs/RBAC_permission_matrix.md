# Matriz de Permissões - Pré-Cadastros

## 📊 **TABELA COMPLETA**

| Ação | Admin | Porteiro | Segurança | Recepção |
|------|-------|----------|-----------|----------|
| **Visualizar Lista** | ✅ | ✅ | ❌ | ❌ |
| **Ver Estatísticas** | ✅ | ✅ | ❌ | ❌ |
| **Criar Pré-Cadastro** | ✅ | ✅ | ❌ | ❌ |
| **Editar Pré-Cadastro** | ✅ | ✅ | ❌ | ❌ |
| **Excluir Pré-Cadastro** | ✅ | ❌ | ❌ | ❌ |
| **Renovar Validade** | ✅ | ✅ | ❌ | ❌ |
| **Buscar (Autocomplete)** | ✅ | ✅ | ❌ | ❌ |
| **Ver Menu "Pré-Cadastros"** | ✅ | ✅ | ❌ | ❌ |

---

## 🎯 **CASOS DE USO POR PERFIL**

### **👔 Administrador**

**Pode tudo:**
- Gerenciar pré-cadastros (CRUD completo)
- Fazer limpeza de dados (excluir cadastros sem uso)
- Renovar cadastros expirados
- Visualizar estatísticas

**Cenário típico:**
```
1. Admin acessa "Pré-Cadastros" → Visitantes
2. Vê lista com 150 cadastros
3. Filtra "Expirados" (5 cadastros)
4. Verifica que 2 não têm entradas há 1 ano
5. Exclui esses 2 cadastros (limpeza)
6. Renova os outros 3 (ainda em uso)
```

---

### **🚪 Porteiro**

**Pode quase tudo (exceto excluir):**
- Criar pré-cadastros de visitantes recorrentes
- Editar dados desatualizados (ex: nova placa)
- Renovar cadastros expirados na hora da entrada
- Buscar cadastros para agilizar registro

**Cenário típico:**
```
1. Visitante recorrente chega na portaria
2. Porteiro busca "João Silva" no dashboard
3. Sistema encontra cadastro (expirado há 5 dias)
4. Porteiro clica "Renovar" (+1 ano)
5. Preenche funcionário responsável e setor
6. Registra entrada em 5 segundos ⚡
```

**NÃO PODE:**
- ❌ Excluir cadastros (mesmo sem entradas)
- **Motivo:** Evitar perda acidental de dados históricos

---

### **🛡️ Segurança / Recepção**

**Sem acesso ao módulo:**
- ❌ Não vê menu "Pré-Cadastros"
- ❌ Não pode criar/editar/excluir
- ❌ Não pode buscar pré-cadastros

**Motivo:** 
- Esses perfis trabalham apenas com registros de acesso pontuais
- Pré-cadastros são responsabilidade de Admin e Porteiro

---

## 🔒 **JUSTIFICATIVAS DE DESIGN**

### **Por que Porteiro não pode excluir?**

1. **Evitar perda de dados:** Porteiro pode excluir acidentalmente cadastros ainda em uso
2. **Auditoria:** Admin revisa e limpa dados periodicamente
3. **Segurança:** Exclusão deve ser ação administrativa, não operacional

**Alternativa para Porteiro:**
Se precisar "remover" um cadastro, pode **desativá-lo** (flag `ativo = false`), sem excluir.

---

### **Por que Segurança/Recepção não têm acesso?**

1. **Separação de responsabilidades:** Segurança cuida de eventos de acesso, não de cadastros
2. **Simplicidade:** Menu mais limpo para esses perfis
3. **Segurança:** Menos perfis com acesso = menor risco

**Se precisarem futuramente:**
Basta adicionar permissões no AuthorizationService (fácil de expandir).

---

## 📋 **PERMISSÕES DETALHADAS**

### **1. `pre_cadastros.read`**

**Descrição:** Visualizar lista de pré-cadastros e estatísticas

**Permite:**
- Acessar `/pre-cadastros/visitantes`
- Ver lista de cadastros
- Ver cards de estatísticas
- Filtrar por status
- Buscar por nome/documento

**Perfis:** Admin, Porteiro

---

### **2. `pre_cadastros.create`**

**Descrição:** Criar novos pré-cadastros

**Permite:**
- Acessar `/pre-cadastros/visitantes?action=new`
- Preencher formulário
- Salvar novo cadastro

**Perfis:** Admin, Porteiro

---

### **3. `pre_cadastros.update`**

**Descrição:** Editar pré-cadastros existentes

**Permite:**
- Acessar `/pre-cadastros/visitantes?action=edit&id=123`
- Modificar nome, empresa, documento, placa
- Alterar período de validade
- Atualizar observações

**Perfis:** Admin, Porteiro

---

### **4. `pre_cadastros.delete`**

**Descrição:** Excluir pré-cadastros (soft delete)

**Permite:**
- Clicar em botão "Excluir"
- Marcar cadastro como excluído (`deleted_at`)

**Restrições:**
- ❌ Não pode excluir se tiver registros vinculados
- ✅ Soft delete (dados preservados para auditoria)

**Perfis:** Admin **APENAS**

---

### **5. `pre_cadastros.renovar`**

**Descrição:** Renovar validade de cadastros expirados

**Permite:**
- Clicar em botão "Renovar"
- Estender validade por +1 ano
- Reativar cadastro (`ativo = true`)

**Uso típico:**
- Visitante recorrente com cadastro expirado
- Porteiro renova na hora e registra entrada

**Perfis:** Admin, Porteiro

---

## 🧪 **TESTES DE AUTORIZAÇÃO**

### **Teste 1: Admin - Full Access**

```bash
# Login como Admin
curl -X POST /auth/login -d '{"email":"admin@...","password":"..."}'

# Criar cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=save -d '{...}'

# Editar cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=update -d '{...}'

# Excluir cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=delete&id=123

# Renovar cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=renovar&id=123
```

**Resultado esperado:** Todas as ações **permitidas** ✅

---

### **Teste 2: Porteiro - Limited Access**

```bash
# Login como Porteiro
curl -X POST /auth/login -d '{"email":"porteiro@...","password":"..."}'

# Criar cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=save -d '{...}'

# Renovar cadastro ✅
curl -X POST /pre-cadastros/visitantes?action=renovar&id=123

# Excluir cadastro ❌
curl -X POST /pre-cadastros/visitantes?action=delete&id=123
```

**Resultado esperado:**
- Criar/Renovar: **Permitido** ✅
- Excluir: **HTTP 403 Forbidden** ❌

---

### **Teste 3: Segurança - No Access**

```bash
# Login como Segurança
curl -X POST /auth/login -d '{"email":"seguranca@...","password":"..."}'

# Tentar acessar lista ❌
curl -X GET /pre-cadastros/visitantes
```

**Resultado esperado:** **HTTP 403 Forbidden** ❌

---

## 📝 **RESUMO EXECUTIVO**

| Aspecto | Decisão |
|---------|---------|
| **Perfis com acesso** | Admin + Porteiro |
| **Perfis sem acesso** | Segurança, Recepção |
| **Permissão exclusiva Admin** | Excluir cadastros |
| **Motivo da restrição** | Evitar perda de dados + auditoria |
| **Expansibilidade** | Fácil adicionar novos perfis |
| **Auditoria** | Todas as ações logadas |

---

**Sistema pronto para aprovação e implementação!** ✅
