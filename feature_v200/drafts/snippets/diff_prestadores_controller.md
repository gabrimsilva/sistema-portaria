# 🔴 DIFF CRÍTICO: PrestadoresServicoController.php

## ⚠️ BUG CORRIGIDO: Saídas não registradas corretamente

**Problema:** Relatórios de prestadores mostram saídas incorretas porque buscam apenas da tabela `prestadores_servico`, ignorando dados de `registro_acesso_placas`.

**Solução:** Usar view `vw_prestadores_consolidado` que consolida dados de ambas as tabelas.

---

## 📍 MUDANÇA 1: handleReportsIndex() - Query Principal

### ❌ ANTES (linhas 104-118):
```php
// Query base para relatórios
$query = "
    SELECT 
        id,
        nome,
        setor,
        CASE 
            WHEN placa_veiculo IS NULL OR placa_veiculo = '' OR placa_veiculo = 'APE' THEN 'A pé'
            ELSE UPPER(placa_veiculo)
        END as placa_ou_ape,
        empresa,
        funcionario_responsavel,
        cpf,
        entrada as entrada_at
    FROM prestadores_servico 
    WHERE entrada IS NOT NULL";

$countQuery = "SELECT COUNT(*) as total FROM prestadores_servico WHERE entrada IS NOT NULL";
```

### ✅ DEPOIS:
```php
// Query base para relatórios (usando view consolidada - BUG FIX)
$query = "
    SELECT 
        id,
        nome,
        setor,
        CASE 
            WHEN placa_veiculo IS NULL OR placa_veiculo = '' OR placa_veiculo = 'APE' THEN 'A pé'
            ELSE UPPER(placa_veiculo)
        END as placa_ou_ape,
        empresa,
        funcionario_responsavel,
        doc_type,
        doc_number,
        doc_country,
        cpf,
        entrada_at,
        saida_consolidada,
        validity_status
    FROM vw_prestadores_consolidado 
    WHERE entrada_at IS NOT NULL";

$countQuery = "SELECT COUNT(*) as total FROM vw_prestadores_consolidado WHERE entrada_at IS NOT NULL";
```

**Mudanças:**
- ✅ Tabela: `prestadores_servico` → `vw_prestadores_consolidado`
- ✅ Campo: `entrada` → `entrada_at` (view usa este nome)
- ✅ Novos campos: `doc_type`, `doc_number`, `doc_country`
- ✅ Novo campo: `saida_consolidada` (saída correta!)
- ✅ Novo campo: `validity_status`

---

## 📍 MUDANÇA 2: Filtro de Status (linhas 155-162)

### ❌ ANTES:
```php
// Filtro por status
if ($status === 'aberto') {
    $query .= " AND saida IS NULL";
    $countQuery .= " AND saida IS NULL";
} elseif ($status === 'finalizado') {
    $query .= " AND saida IS NOT NULL";
    $countQuery .= " AND saida IS NOT NULL";
}
```

### ✅ DEPOIS:
```php
// Filtro por status (usando saida_consolidada - BUG FIX)
if ($status === 'aberto') {
    $query .= " AND saida_consolidada IS NULL";
    $countQuery .= " AND saida_consolidada IS NULL";
} elseif ($status === 'finalizado') {
    $query .= " AND saida_consolidada IS NOT NULL";
    $countQuery .= " AND saida_consolidada IS NOT NULL";
}
```

**Mudanças:**
- ✅ Campo: `saida` → `saida_consolidada`

---

## 📍 MUDANÇA 3: Filtros de Documentos (ADICIONAR após linha 178)

### ✅ ADICIONAR:
```php
// Filtro por tipo de documento (v2.0.0)
$docType = $_GET['doc_type'] ?? '';
if (!empty($docType)) {
    $query .= " AND doc_type = ?";
    $countQuery .= " AND doc_type = ?";
    $params[] = $docType;
    $countParams[] = $docType;
}

// Filtro por país do documento (v2.0.0)
$docCountry = $_GET['doc_country'] ?? '';
if (!empty($docCountry)) {
    $query .= " AND doc_country = ?";
    $countQuery .= " AND doc_country = ?";
    $params[] = $docCountry;
    $countParams[] = $docCountry;
}

// Filtro por status de validade (v2.0.0)
$validityStatus = $_GET['validity_status'] ?? '';
if (!empty($validityStatus)) {
    $query .= " AND validity_status = ?";
    $countQuery .= " AND validity_status = ?";
    $params[] = $validityStatus;
    $countParams[] = $validityStatus;
}
```

---

## 📍 MUDANÇA 4: Dados para Filtros (linha 199-201)

### ❌ ANTES:
```php
// Dados para filtros
$setores = $this->db->fetchAll("SELECT DISTINCT setor FROM prestadores_servico WHERE setor IS NOT NULL ORDER BY setor");
$empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM prestadores_servico WHERE empresa IS NOT NULL ORDER BY empresa");
$responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM prestadores_servico WHERE funcionario_responsavel IS NOT NULL ORDER BY funcionario_responsavel");
```

### ✅ DEPOIS:
```php
// Dados para filtros (usando view consolidada)
$setores = $this->db->fetchAll("SELECT DISTINCT setor FROM vw_prestadores_consolidado WHERE setor IS NOT NULL ORDER BY setor");
$empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM vw_prestadores_consolidado WHERE empresa IS NOT NULL ORDER BY empresa");
$responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM vw_prestadores_consolidado WHERE funcionario_responsavel IS NOT NULL ORDER BY funcionario_responsavel");

// Novos filtros v2.0.0
$docTypes = $this->db->fetchAll("SELECT DISTINCT doc_type FROM vw_prestadores_consolidado WHERE doc_type IS NOT NULL ORDER BY doc_type");
$docCountries = $this->db->fetchAll("SELECT DISTINCT doc_country FROM vw_prestadores_consolidado WHERE doc_country IS NOT NULL ORDER BY doc_country");
```

---

## 📍 MUDANÇA 5: Export CSV (localizar método export())

Buscar no código onde está a exportação CSV e aplicar as mesmas mudanças:

### Mudanças necessárias:
1. Trocar `prestadores_servico` → `vw_prestadores_consolidado`
2. Trocar `entrada` → `entrada_at`
3. Trocar `saida` → `saida_consolidada`
4. Adicionar colunas: `doc_type`, `doc_number`, `doc_country`
5. Adicionar coluna: `validity_status`

---

## 📍 MUDANÇA 6: Máscara de Documento (linha 190-196)

### ❌ ANTES:
```php
// Mascarar CPFs se necessário
$canViewFullCpf = $this->canViewFullCpf();
foreach ($prestadores as &$prestador) {
    if (!$canViewFullCpf) {
        $prestador['cpf'] = $this->maskCpf($prestador['cpf']);
    }
}
```

### ✅ DEPOIS:
```php
// Mascarar documentos se necessário (v2.0.0)
$canViewFullCpf = $this->canViewFullCpf();
foreach ($prestadores as &$prestador) {
    if (!$canViewFullCpf) {
        // Mascarar conforme tipo de documento
        if ($prestador['doc_type'] === 'CPF') {
            $prestador['doc_number'] = $this->maskCpf($prestador['doc_number']);
        } elseif (!empty($prestador['doc_number']) && strlen($prestador['doc_number']) > 4) {
            // Outros documentos: mostrar apenas últimos 4 caracteres
            $prestador['doc_number'] = str_repeat('*', strlen($prestador['doc_number']) - 4) . substr($prestador['doc_number'], -4);
        }
        
        // Manter CPF mascarado para compatibilidade
        if (!empty($prestador['cpf'])) {
            $prestador['cpf'] = $this->maskCpf($prestador['cpf']);
        }
    }
}
```

---

## 📊 RESUMO DAS MUDANÇAS

### 🔴 Críticas (Bug Fix):
- ✅ View: `prestadores_servico` → `vw_prestadores_consolidado`
- ✅ Saída: `saida` → `saida_consolidada`
- ✅ Entrada: `entrada` → `entrada_at`

### 🟡 Novas Funcionalidades:
- ✅ Campos de documentos internacionais
- ✅ Filtros por tipo/país de documento
- ✅ Filtro por status de validade
- ✅ Máscara de documentos não-CPF

### 📁 Arquivos Afetados:
- `src/controllers/PrestadoresServicoController.php`

### ⚠️ IMPORTANTE:
1. **Testar relatórios** após aplicar mudanças
2. **Verificar se saídas** aparecem corretamente
3. **Testar filtros novos** (doc_type, validity_status)
4. **Validar exportação CSV** com novos campos

---

## 🧪 TESTES RECOMENDADOS

### Antes vs Depois:
1. **Registrar entrada** de prestador
2. **Registrar saída** via placa
3. **Verificar relatório** mostra saída ✅
4. **Filtrar por status** "finalizado" ✅
5. **Exportar CSV** com saída ✅

### Novos Recursos:
6. **Filtrar por tipo** de documento
7. **Filtrar por país** do documento
8. **Filtrar por validade** (ativo/expirado/bloqueado)

---

**Versão:** 2.0.0  
**Prioridade:** 🔴 CRÍTICA (Bug Fix)  
**Status:** DRAFT - Não aplicado
