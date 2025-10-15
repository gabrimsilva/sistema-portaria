# 🔵 DIFF: DashboardController.php

## 🐛 BUG FIX + ✨ NOVA FUNCIONALIDADE

**Problemas:**
1. Prestadores ativos não usa view consolidada (bug de saídas)
2. Dashboard não tem widget de cadastros expirando

**Soluções:**
1. Usar `vw_prestadores_consolidado` com `saida_consolidada`
2. Incluir widget de cadastros expirando

---

## 📍 MUDANÇA 1: countAtivosAgora - Prestadores (linhas 28-33)

### ❌ ANTES:
```php
case 'prestador':
    return $this->db->fetch("
        SELECT COUNT(*) as total 
        FROM prestadores_servico 
        WHERE entrada IS NOT NULL AND saida IS NULL
    ")['total'] ?? 0;
```

### ✅ DEPOIS (BUG FIX):
```php
case 'prestador':
    return $this->db->fetch("
        SELECT COUNT(*) as total 
        FROM vw_prestadores_consolidado 
        WHERE entrada_at IS NOT NULL AND saida_consolidada IS NULL
    ")['total'] ?? 0;
```

**Mudanças:**
- ✅ Tabela: `prestadores_servico` → `vw_prestadores_consolidado`
- ✅ Campo entrada: `entrada` → `entrada_at`
- ✅ Campo saída: `saida` → `saida_consolidada` (BUG FIX!)

---

## 📍 MUDANÇA 2: getPessoasNaEmpresa - Prestadores (linhas 321-327)

### ❌ ANTES:
```php
// Prestadores trabalhando (entraram mas não saíram)
$prestadoresAtivos = $this->db->fetchAll("
    SELECT nome, cpf, empresa, setor, entrada as hora_entrada, 'Prestador' as tipo, id, placa_veiculo, funcionario_responsavel
    FROM prestadores_servico 
    WHERE entrada IS NOT NULL AND saida IS NULL
    ORDER BY entrada DESC
") ?? [];
```

### ✅ DEPOIS (BUG FIX):
```php
// Prestadores trabalhando (usando view consolidada - BUG FIX)
$prestadoresAtivos = $this->db->fetchAll("
    SELECT nome, doc_type, doc_number, cpf, empresa, setor, 
           entrada_at as hora_entrada, 'Prestador' as tipo, id, placa_veiculo, funcionario_responsavel
    FROM vw_prestadores_consolidado 
    WHERE entrada_at IS NOT NULL AND saida_consolidada IS NULL
    ORDER BY entrada_at DESC
") ?? [];
```

**Mudanças:**
- ✅ Tabela: `prestadores_servico` → `vw_prestadores_consolidado`
- ✅ Campos: `entrada` → `entrada_at`, `saida` → `saida_consolidada`
- ✅ Novos campos: `doc_type`, `doc_number`

---

## 📍 MUDANÇA 3: getPessoasNaEmpresa - Visitantes (linhas 314-319)

### ❌ ANTES:
```php
// Visitantes na empresa (entraram mas não saíram)
$visitantesAtivos = $this->db->fetchAll("
    SELECT nome, cpf, empresa, setor, hora_entrada, 'Visitante' as tipo, id, placa_veiculo, funcionario_responsavel
    FROM visitantes_novo 
    WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL
    ORDER BY hora_entrada DESC
") ?? [];
```

### ✅ DEPOIS:
```php
// Visitantes na empresa (entraram mas não saíram)
$visitantesAtivos = $this->db->fetchAll("
    SELECT nome, cpf, doc_type, doc_number, doc_country, empresa, setor, 
           hora_entrada, 'Visitante' as tipo, id, placa_veiculo, funcionario_responsavel
    FROM visitantes_novo 
    WHERE hora_entrada IS NOT NULL AND hora_saida IS NULL
    ORDER BY hora_entrada DESC
") ?? [];
```

**Mudanças:**
- ✅ Novos campos: `doc_type`, `doc_number`, `doc_country`

---

## 📍 MUDANÇA 4: ADICIONAR Widget de Cadastros Expirando

### ✅ ADICIONAR método novo (antes do final da classe):
```php
/**
 * Obter cadastros expirando (visitantes e prestadores)
 * Usado no widget do dashboard v2.0.0
 */
public function getCadastrosExpirando() {
    try {
        $data = [
            'visitantes' => [],
            'prestadores' => []
        ];
        
        // Visitantes expirando (próximos 30 dias)
        $data['visitantes'] = $this->db->fetchAll("
            SELECT id, nome, doc_type, doc_number, empresa, 
                   data_validade, validity_status,
                   DATE(data_validade) - CURRENT_DATE as dias_restantes
            FROM visitantes_novo
            WHERE data_validade IS NOT NULL 
              AND validity_status IN ('ativo', 'expirando')
              AND DATE(data_validade) BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL '30 days')
            ORDER BY data_validade ASC
            LIMIT 10
        ") ?? [];
        
        // Prestadores expirando (próximos 30 dias)
        $data['prestadores'] = $this->db->fetchAll("
            SELECT id, nome, doc_type, doc_number, empresa, 
                   data_validade, validity_status,
                   DATE(data_validade) - CURRENT_DATE as dias_restantes
            FROM vw_prestadores_consolidado
            WHERE data_validade IS NOT NULL 
              AND validity_status IN ('ativo', 'expirando')
              AND DATE(data_validade) BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL '30 days')
            ORDER BY data_validade ASC
            LIMIT 10
        ") ?? [];
        
        return $data;
    } catch (Exception $e) {
        error_log("Erro getCadastrosExpirando: " . $e->getMessage());
        return ['visitantes' => [], 'prestadores' => []];
    }
}
```

---

## 📍 MUDANÇA 5: index() - Incluir dados do widget

### Localizar método `index()` e ADICIONAR:
```php
public function index() {
    // ... código existente ...
    
    // NOVO: Dados do widget de cadastros expirando (v2.0.0)
    $cadastrosExpirando = $this->getCadastrosExpirando();
    
    // ... resto do código ...
    
    include $this->getViewPath('index.php');
}
```

---

## 📍 MUDANÇA 6: View dashboard/index.php

### No arquivo `views/dashboard/index.php`, ADICIONAR após cards de estatísticas:

```php
<!-- Widget Cadastros Expirando (v2.0.0) -->
<?php require_once __DIR__ . '/../components/widget_cadastros_expirando.php'; ?>
```

---

## 📊 RESUMO DAS MUDANÇAS

### 🔴 Críticas (Bug Fix):
- ✅ Prestadores ativos agora usam view consolidada
- ✅ Saída correta via `saida_consolidada`
- ✅ Campos corretos: `entrada_at` em vez de `entrada`

### 🟡 Novas Funcionalidades:
- ✅ Widget de cadastros expirando (30 dias)
- ✅ Suporte a documentos internacionais no dashboard
- ✅ Método `getCadastrosExpirando()` para widget

### 📁 Arquivos Afetados:
- `src/controllers/DashboardController.php`
- `views/dashboard/index.php` (incluir widget)

---

## 🧪 TESTES RECOMENDADOS

### Bug Fix:
1. **Registrar saída** de prestador via placa
2. **Verificar dashboard** → contador de ativos deve atualizar
3. **Verificar "Pessoas na Empresa"** → prestador deve sumir

### Widget:
4. **Criar visitante** com validade em 5 dias
5. **Verificar widget** → deve aparecer com badge amarelo
6. **Criar prestador** expirando em 2 dias
7. **Verificar widget** → badge vermelho (crítico)
8. **Renovar cadastro** → deve sumir do widget

### Documentos:
9. **Dashboard mostra** tipo de documento
10. **Visitante com Passaporte** aparece corretamente
11. **Prestador com RNE** exibe país

---

## 📋 CHECKLIST DE APLICAÇÃO

- [ ] Aplicar mudanças no DashboardController
- [ ] Testar contadores de ativos
- [ ] Incluir widget na view
- [ ] Testar widget auto-refresh
- [ ] Validar dados de documentos internacionais
- [ ] Testar renovação rápida do widget

---

**Versão:** 2.0.0  
**Prioridade:** 🔴 CRÍTICA (Bug Fix) + 🟡 MÉDIA (Widget)  
**Status:** DRAFT - Não aplicado
