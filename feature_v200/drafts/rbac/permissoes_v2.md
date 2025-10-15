# 🔐 PERMISSÕES v2.0.0

## 📊 SISTEMA ATUAL

### Roles Existentes (5):
1. **Administrador** - Acesso total
2. **Segurança** - Relatórios e fiscalização
3. **Recepção** - Cadastros e acessos
4. **RH** - Recursos humanos
5. **Porteiro** - Portaria (entrada/saída)

### Módulos de Permissões Existentes:
- `config` - Configurações do sistema
- `reports` - Relatórios
- `access` - Controle de acesso
- `audit` - Auditoria
- `users` - Usuários
- `privacy` - Privacidade

---

## 🆕 NOVAS PERMISSÕES v2.0.0

### 1. **documentos.manage** 🌍
- **Descrição:** Gerenciar documentos internacionais
- **Permite:**
  - Cadastrar visitantes/prestadores com Passaporte
  - Cadastrar com RNE, DNI, CI
  - Validar documentos estrangeiros
  - Usar seletor de tipos de documento
- **Módulo:** `documentos`
- **Quem tem:** Administrador, Recepção, RH

---

### 2. **entrada.retroativa** 📅
- **Descrição:** Registrar entradas retroativas
- **Permite:**
  - Abrir modal de entrada retroativa
  - Registrar entrada em data/hora passada
  - Informar motivo (auditoria)
  - Visualizar conflitos temporais
- **Módulo:** `acesso`
- **Quem tem:** Administrador, Segurança (somente)
- **⚠️ SENSÍVEL:** Pode alterar histórico

---

### 3. **validade.manage** ⏰
- **Descrição:** Gerenciar validade de cadastros
- **Permite:**
  - Definir período de validade
  - Renovar cadastros (7-365 dias)
  - Bloquear/desbloquear cadastros
  - Ver widget de expirando
  - Ações em lote
- **Módulo:** `validade`
- **Quem tem:** Administrador, Recepção, RH

---

### 4. **ramais.manage** 📞
- **Descrição:** Gerenciar ramais corporativos
- **Permite:**
  - CRUD completo de ramais
  - Associar ramais a setores
  - Definir ramais de brigadistas
  - Importar/exportar ramais
- **Módulo:** `ramais`
- **Quem tem:** Administrador, RH
- **OBS:** Consulta pública (/ramais) não precisa permissão

---

### 5. **reports.advanced_filters** 🔍
- **Descrição:** Usar filtros avançados em relatórios
- **Permite:**
  - Filtrar por tipo de documento
  - Filtrar por país de origem
  - Filtrar por status de validade
  - Filtrar por data de vencimento
  - Export CSV com filtros
- **Módulo:** `reports`
- **Quem tem:** Administrador, Segurança, RH

---

## 📋 MATRIZ DE PERMISSÕES v2.0.0

| Permissão | Administrador | Segurança | Recepção | RH | Porteiro |
|-----------|:-------------:|:---------:|:--------:|:--:|:--------:|
| **documentos.manage** | ✅ | ❌ | ✅ | ✅ | ❌ |
| **entrada.retroativa** | ✅ | ✅ | ❌ | ❌ | ❌ |
| **validade.manage** | ✅ | ❌ | ✅ | ✅ | ❌ |
| **ramais.manage** | ✅ | ❌ | ❌ | ✅ | ❌ |
| **reports.advanced_filters** | ✅ | ✅ | ❌ | ✅ | ❌ |

---

## 🎯 JUSTIFICATIVAS

### **Por que cada role tem essas permissões?**

#### **Administrador** (5/5)
- Tem TODAS as permissões
- Acesso total ao sistema
- Pode fazer qualquer operação

#### **Segurança** (2/5)
- ✅ **entrada.retroativa** - Pode corrigir registros de acesso
- ✅ **reports.advanced_filters** - Precisa de relatórios detalhados
- ❌ Não gerencia cadastros ou ramais

#### **Recepção** (2/5)
- ✅ **documentos.manage** - Cadastra visitantes/prestadores
- ✅ **validade.manage** - Renova cadastros diários
- ❌ Não pode alterar histórico (entrada retroativa)
- ❌ Não gerencia ramais

#### **RH** (4/5)
- ✅ **documentos.manage** - Cadastra profissionais expatriados
- ✅ **validade.manage** - Controla validade de contratados
- ✅ **ramais.manage** - Gerencia lista de ramais
- ✅ **reports.advanced_filters** - Relatórios de RH
- ❌ Não pode alterar histórico de acesso

#### **Porteiro** (0/5)
- ❌ Não tem nenhuma permissão nova
- Foco em entrada/saída apenas
- Não gerencia cadastros

---

## 🔒 REGRAS DE SEGURANÇA

### **Permissões Sensíveis:**

1. **entrada.retroativa** ⚠️
   - Pode alterar histórico
   - Auditoria obrigatória (motivo)
   - Apenas Admin e Segurança

2. **validade.manage** (bloqueio)
   - Bloquear cadastro precisa motivo
   - Log de auditoria automático
   - Não pode bloquear admin

3. **ramais.manage**
   - Não pode excluir ramais de brigadistas ativos
   - Mudanças são auditadas

---

## 📊 ESTATÍSTICAS

| Métrica | Valor |
|---------|-------|
| **Permissões novas** | 5 |
| **Módulos novos** | 3 (documentos, validade, ramais) |
| **Módulos atualizados** | 2 (acesso, reports) |
| **Roles com mudanças** | 4/5 (exceto Porteiro) |
| **Permissões sensíveis** | 1 (entrada.retroativa) |

---

## 🔄 COMPATIBILIDADE

### **Permissões Antigas (Mantidas):**
- `config.*` - Mantido
- `audit.*` - Mantido
- `users.*` - Mantido
- `reports.read` - Mantido
- `reports.export` - Mantido
- `registro_acesso.*` - Mantido
- `person.cpf.view_unmasked` - Mantido

### **Novas Permissões (v2.0.0):**
- `documentos.manage` - NOVO
- `entrada.retroativa` - NOVO
- `validade.manage` - NOVO
- `ramais.manage` - NOVO
- `reports.advanced_filters` - NOVO

**Total:** 5 novas, 15+ mantidas

---

## 🎯 USO NOS CONTROLLERS

### **DocumentoController.php**
```php
// Requer: documentos.manage
if (!$rbac->hasPermission('documentos.manage')) {
    throw new UnauthorizedException();
}
```

### **EntradaRetroativaController.php**
```php
// Requer: entrada.retroativa
if (!$rbac->hasPermission('entrada.retroativa')) {
    throw new UnauthorizedException();
}
```

### **ValidadeController.php**
```php
// Requer: validade.manage
if (!$rbac->hasPermission('validade.manage')) {
    throw new UnauthorizedException();
}
```

### **RamalController.php**
```php
// GET /ramais - Público (sem permissão)
// POST/PUT/DELETE - Requer: ramais.manage
if (!$rbac->hasPermission('ramais.manage')) {
    throw new UnauthorizedException();
}
```

### **Relatórios (Visitantes/Prestadores/Profissionais)**
```php
// Filtros avançados requerem: reports.advanced_filters
if ($hasAdvancedFilters && !$rbac->hasPermission('reports.advanced_filters')) {
    // Desabilitar filtros avançados
}
```

---

**Status:** ✅ MAPEAMENTO CONCLUÍDO  
**Próximo:** SQL de inserção
