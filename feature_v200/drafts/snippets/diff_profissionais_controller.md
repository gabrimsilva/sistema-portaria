# 🟢 DIFF: ProfissionaisRennerController.php

## ✨ NOVA FUNCIONALIDADE: Suporte a Documentos Internacionais

**Objetivo:** Adicionar suporte a documentos estrangeiros para profissionais expatriados e filtros de validade.

---

## 📍 MUDANÇA 1: Query de Listagem (método index ou similar)

### Localizar query que busca profissionais:
```php
SELECT * FROM profissionais_renner
```

### ✅ ADICIONAR campos:
```php
SELECT 
    id, 
    nome, 
    cpf, 
    doc_type,           -- v2.0.0
    doc_number,         -- v2.0.0
    doc_country,        -- v2.0.0
    setor, 
    cargo, 
    ramal, 
    foto_url, 
    brigadista,
    validity_status,    -- v2.0.0
    created_at, 
    updated_at
FROM profissionais_renner
```

---

## 📍 MUDANÇA 2: Busca/Filtro

### Localizar condição de busca:
```php
WHERE nome ILIKE ? OR cpf ILIKE ?
```

### ✅ SUBSTITUIR POR:
```php
WHERE nome ILIKE ? OR cpf ILIKE ? OR doc_number ILIKE ?
```

**E adicionar parâmetro:**
```php
$params[] = "%$search%";  // Para doc_number
```

---

## 📍 MUDANÇA 3: Novos Filtros (ADICIONAR)

### ✅ ADICIONAR:
```php
// Filtro por tipo de documento (v2.0.0)
$docType = $_GET['doc_type'] ?? '';
if (!empty($docType)) {
    $whereConditions[] = "doc_type = ?";
    $params[] = $docType;
}

// Filtro por status de validade (v2.0.0)
$validityStatus = $_GET['validity_status'] ?? '';
if (!empty($validityStatus)) {
    $whereConditions[] = "validity_status = ?";
    $params[] = $validityStatus;
}
```

---

## 📍 MUDANÇA 4: Dados para Filtros

### ✅ ADICIONAR (após buscar setores):
```php
// Filtros de documento v2.0.0
$docTypes = $this->db->fetchAll("
    SELECT DISTINCT doc_type 
    FROM profissionais_renner 
    WHERE doc_type IS NOT NULL 
    ORDER BY doc_type
");

$validityStatuses = [
    ['value' => 'ativo', 'label' => 'Ativo'],
    ['value' => 'expirando', 'label' => 'Expirando'],
    ['value' => 'expirado', 'label' => 'Expirado'],
    ['value' => 'bloqueado', 'label' => 'Bloqueado']
];
```

---

## 📍 MUDANÇA 5: Processar Dados para Exibição

### ✅ ADICIONAR no loop de profissionais:
```php
foreach ($profissionais as &$profissional) {
    // Mascarar CPF (existente)
    if (!$canViewFullCpf && !empty($profissional['cpf'])) {
        $profissional['cpf_masked'] = $this->maskCpf($profissional['cpf']);
    }
    
    // NOVO: Determinar documento a exibir (v2.0.0)
    if (!empty($profissional['doc_type']) && $profissional['doc_type'] !== 'CPF') {
        // Documento internacional
        $profissional['documento_tipo'] = $profissional['doc_type'];
        $profissional['documento_numero'] = $profissional['doc_number'];
        $profissional['documento_pais'] = $profissional['doc_country'];
        
        // Mascarar se necessário
        if (!$canViewFullCpf && !empty($profissional['doc_number']) && strlen($profissional['doc_number']) > 4) {
            $profissional['documento_numero_masked'] = str_repeat('*', strlen($profissional['doc_number']) - 4) . substr($profissional['doc_number'], -4);
        } else {
            $profissional['documento_numero_masked'] = $profissional['doc_number'];
        }
    } else {
        // CPF brasileiro (fallback)
        $profissional['documento_tipo'] = 'CPF';
        $profissional['documento_numero'] = $profissional['cpf'];
        $profissional['documento_pais'] = 'BR';
        $profissional['documento_numero_masked'] = $profissional['cpf_masked'] ?? $profissional['cpf'];
    }
}
```

---

## 📍 MUDANÇA 6: Export CSV (se existir)

### Query de Export:
```php
SELECT 
    nome, 
    cpf, 
    doc_type,           -- v2.0.0
    doc_number,         -- v2.0.0
    doc_country,        -- v2.0.0
    setor, 
    cargo, 
    ramal, 
    brigadista,
    validity_status     -- v2.0.0
FROM profissionais_renner
WHERE ...
```

### Headers CSV:
```php
echo "Nome;CPF;Tipo Doc;Número Doc;País;Setor;Cargo;Ramal;Brigadista;Status Validade\n";
```

### Dados CSV:
```php
foreach ($profissionais as $p) {
    $tipoDoc = $p['doc_type'] ?? 'CPF';
    $numeroDoc = $p['doc_number'] ?? $p['cpf'];
    $paisDoc = $p['doc_country'] ?? 'BR';
    $statusValidade = $p['validity_status'] ?? '-';
    
    // Mascarar se necessário
    if (!$canViewFullCpf && !empty($numeroDoc)) {
        if ($tipoDoc === 'CPF') {
            $numeroDoc = $this->maskCpf($numeroDoc);
        } elseif (strlen($numeroDoc) > 4) {
            $numeroDoc = str_repeat('*', strlen($numeroDoc) - 4) . substr($numeroDoc, -4);
        }
    }
    
    $brigadista = $p['brigadista'] ? 'Sim' : 'Não';
    
    echo implode(';', [
        $this->sanitizeForCsv($p['nome']),
        $this->sanitizeForCsv($p['cpf']),
        $this->sanitizeForCsv($tipoDoc),
        $this->sanitizeForCsv($numeroDoc),
        $this->sanitizeForCsv($paisDoc),
        $this->sanitizeForCsv($p['setor']),
        $this->sanitizeForCsv($p['cargo']),
        $this->sanitizeForCsv($p['ramal']),
        $brigadista,
        $this->sanitizeForCsv($statusValidade)
    ]) . "\n";
}
```

---

## 📊 RESUMO DAS MUDANÇAS

### 🟢 Novas Funcionalidades:
- ✅ Campos de documentos internacionais
- ✅ Busca por número de documento
- ✅ Filtro por tipo de documento
- ✅ Filtro por status de validade
- ✅ Exportação CSV com novos campos

### 🎯 Casos de Uso:
- Profissionais expatriados (Passaporte)
- Trabalhadores estrangeiros (RNE)
- Contratos temporários com validade

### 📁 Arquivos Afetados:
- `src/controllers/ProfissionaisRennerController.php`

### 🎯 Compatibilidade:
- ✅ CPF continua sendo o padrão
- ✅ Não quebra registros existentes
- ✅ LGPD mantido

---

## 🧪 TESTES RECOMENDADOS

1. **Cadastrar profissional** com Passaporte
2. **Buscar por documento** estrangeiro
3. **Filtrar por tipo** de documento
4. **Filtrar por validade** "expirado"
5. **Exportar CSV** → novos campos aparecem
6. **Mascarar documentos** não-CPF

---

**Versão:** 2.0.0  
**Prioridade:** 🟢 BAIXA (Caso de uso raro)  
**Status:** DRAFT - Não aplicado
