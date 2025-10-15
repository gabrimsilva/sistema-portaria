# 🟡 DIFF: VisitantesNovoController.php

## ✨ NOVA FUNCIONALIDADE: Suporte a Documentos Internacionais

**Objetivo:** Adicionar suporte a documentos estrangeiros (Passaporte, RNE, DNI, etc.) e filtros de validade.

---

## 📍 MUDANÇA 1: Query Principal (linhas 154-156)

### ❌ ANTES:
```php
$query = "
    SELECT id, nome, cpf, empresa, funcionario_responsavel, setor, 
           placa_veiculo, hora_entrada, hora_saida
    FROM visitantes_novo 
    WHERE $whereClause 
    ORDER BY hora_entrada DESC 
    LIMIT ? OFFSET ?
";
```

### ✅ DEPOIS:
```php
$query = "
    SELECT id, nome, cpf, doc_type, doc_number, doc_country, 
           empresa, funcionario_responsavel, setor, 
           placa_veiculo, hora_entrada, hora_saida, validity_status
    FROM visitantes_novo 
    WHERE $whereClause 
    ORDER BY hora_entrada DESC 
    LIMIT ? OFFSET ?
";
```

**Mudanças:**
- ✅ Adicionados: `doc_type`, `doc_number`, `doc_country`
- ✅ Adicionado: `validity_status`

---

## 📍 MUDANÇA 2: Busca Geral (linhas 139-144)

### ❌ ANTES:
```php
// Busca geral
if (!empty($search)) {
    $whereConditions[] = "(nome ILIKE ? OR cpf ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
```

### ✅ DEPOIS:
```php
// Busca geral (incluindo documentos internacionais - v2.0.0)
if (!empty($search)) {
    $whereConditions[] = "(nome ILIKE ? OR cpf ILIKE ? OR doc_number ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
```

**Mudanças:**
- ✅ Busca agora inclui `doc_number`

---

## 📍 MUDANÇA 3: Novos Filtros (ADICIONAR após linha 144)

### ✅ ADICIONAR:
```php
// Filtro por tipo de documento (v2.0.0)
$docType = $_GET['doc_type'] ?? '';
if (!empty($docType)) {
    $whereConditions[] = "doc_type = ?";
    $params[] = $docType;
}

// Filtro por país do documento (v2.0.0)
$docCountry = $_GET['doc_country'] ?? '';
if (!empty($docCountry)) {
    $whereConditions[] = "doc_country = ?";
    $params[] = $docCountry;
}

// Filtro por status de validade (v2.0.0)
$validityStatus = $_GET['validity_status'] ?? '';
if (!empty($validityStatus)) {
    $whereConditions[] = "validity_status = ?";
    $params[] = $validityStatus;
}
```

---

## 📍 MUDANÇA 4: Processar Dados (ADICIONAR após linha 178, antes de "Lógica A pé")

### ✅ ADICIONAR:
```php
// Determinar documento a exibir (v2.0.0)
if (!empty($visitante['doc_type']) && $visitante['doc_type'] !== 'CPF') {
    // Documento internacional
    $visitante['documento_tipo'] = $visitante['doc_type'];
    $visitante['documento_numero'] = $visitante['doc_number'];
    $visitante['documento_pais'] = $visitante['doc_country'];
    
    // Mascarar se necessário
    if (!$canViewFullCpf && !empty($visitante['doc_number']) && strlen($visitante['doc_number']) > 4) {
        $visitante['documento_numero_masked'] = str_repeat('*', strlen($visitante['doc_number']) - 4) . substr($visitante['doc_number'], -4);
    } else {
        $visitante['documento_numero_masked'] = $visitante['doc_number'];
    }
} else {
    // CPF brasileiro (fallback)
    $visitante['documento_tipo'] = 'CPF';
    $visitante['documento_numero'] = $visitante['cpf'];
    $visitante['documento_pais'] = 'BR';
    $visitante['documento_numero_masked'] = $visitante['cpf_masked'];
}
```

---

## 📍 MUDANÇA 5: Dados para Filtros (linhas 212-214)

### ❌ ANTES:
```php
$setores = $this->db->fetchAll("SELECT DISTINCT setor FROM visitantes_novo WHERE setor IS NOT NULL ORDER BY setor");
$empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM visitantes_novo WHERE empresa IS NOT NULL AND empresa != '' ORDER BY empresa LIMIT 50");
$responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM visitantes_novo WHERE funcionario_responsavel IS NOT NULL AND funcionario_responsavel != '' ORDER BY funcionario_responsavel LIMIT 50");
```

### ✅ DEPOIS:
```php
$setores = $this->db->fetchAll("SELECT DISTINCT setor FROM visitantes_novo WHERE setor IS NOT NULL ORDER BY setor");
$empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM visitantes_novo WHERE empresa IS NOT NULL AND empresa != '' ORDER BY empresa LIMIT 50");
$responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM visitantes_novo WHERE funcionario_responsavel IS NOT NULL AND funcionario_responsavel != '' ORDER BY funcionario_responsavel LIMIT 50");

// Novos filtros v2.0.0
$docTypes = $this->db->fetchAll("SELECT DISTINCT doc_type FROM visitantes_novo WHERE doc_type IS NOT NULL ORDER BY doc_type");
$docCountries = $this->db->fetchAll("SELECT DISTINCT doc_country FROM visitantes_novo WHERE doc_country IS NOT NULL ORDER BY doc_country");
$validityStatuses = [
    ['value' => 'ativo', 'label' => 'Ativo'],
    ['value' => 'expirando', 'label' => 'Expirando'],
    ['value' => 'expirado', 'label' => 'Expirado'],
    ['value' => 'bloqueado', 'label' => 'Bloqueado']
];
```

---

## 📍 MUDANÇA 6: Export CSV - Query (linhas 1055-1057)

### ❌ ANTES:
```php
$query = "
    SELECT id, nome, cpf, empresa, funcionario_responsavel, setor, 
           placa_veiculo, hora_entrada, hora_saida
    FROM visitantes_novo 
    WHERE $whereClause 
    ORDER BY hora_entrada DESC
";
```

### ✅ DEPOIS:
```php
$query = "
    SELECT id, nome, cpf, doc_type, doc_number, doc_country,
           empresa, funcionario_responsavel, setor, 
           placa_veiculo, hora_entrada, hora_saida, validity_status
    FROM visitantes_novo 
    WHERE $whereClause 
    ORDER BY hora_entrada DESC
";
```

---

## 📍 MUDANÇA 7: Export CSV - Headers (localizar linha após 1067)

Procurar onde tem:
```php
echo "Data;Hora Entrada;Hora Saída;Nome;CPF;Empresa;Responsável;Setor;Placa/Veículo\n";
```

### ✅ SUBSTITUIR POR:
```php
echo "Data;Hora Entrada;Hora Saída;Nome;Tipo Doc;Número Doc;País;CPF;Empresa;Responsável;Setor;Placa/Veículo;Status Validade\n";
```

---

## 📍 MUDANÇA 8: Export CSV - Dados (localizar loop foreach após headers)

Procurar onde processa cada visitante para CSV e ADICIONAR:

### ✅ ADICIONAR:
```php
// Determinar documento (v2.0.0)
$tipoDoc = $visitante['doc_type'] ?? 'CPF';
$numeroDoc = $visitante['doc_number'] ?? $visitante['cpf'];
$paisDoc = $visitante['doc_country'] ?? 'BR';
$statusValidade = $visitante['validity_status'] ?? '-';

// Mascarar se necessário
if (!$canViewFullCpf && !empty($numeroDoc)) {
    if ($tipoDoc === 'CPF') {
        $numeroDoc = $this->maskCpf($numeroDoc);
    } elseif (strlen($numeroDoc) > 4) {
        $numeroDoc = str_repeat('*', strlen($numeroDoc) - 4) . substr($numeroDoc, -4);
    }
}

// Na linha do CSV, adicionar os novos campos:
// ... $tipoDoc; $numeroDoc; $paisDoc; ... $statusValidade
```

---

## 📊 RESUMO DAS MUDANÇAS

### 🟡 Novas Funcionalidades:
- ✅ Campos de documentos internacionais na query
- ✅ Busca por número de documento
- ✅ Filtros por tipo/país de documento
- ✅ Filtro por status de validade
- ✅ Exportação CSV com novos campos
- ✅ Máscara de documentos não-CPF

### 📁 Arquivos Afetados:
- `src/controllers/VisitantesNovoController.php`

### 🎯 Compatibilidade:
- ✅ CPF continua funcionando (fallback)
- ✅ Não quebra registros antigos
- ✅ LGPD mantido (máscaras)

---

## 🧪 TESTES RECOMENDADOS

### Funcionalidade:
1. **Buscar por Passaporte** → deve encontrar
2. **Filtrar por doc_type** = "Passaporte" → OK
3. **Filtrar por país** = "ARG" → OK
4. **Exportar CSV** → colunas novas aparecem

### UI/UX:
5. **Exibir tipo de documento** na lista
6. **Mostrar país** para docs internacionais
7. **Badge de status** de validade

### Segurança:
8. **Máscara funciona** para não-CPF
9. **LGPD respeitado** em export

---

**Versão:** 2.0.0  
**Prioridade:** 🟡 MÉDIA (Nova Funcionalidade)  
**Status:** DRAFT - Não aplicado
