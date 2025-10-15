# 🔧 CORREÇÕES DE PERMISSÕES - Controllers M3

## ⚠️ PROBLEMAS ENCONTRADOS

Após validação, os controllers M3 têm **erros de permissões**:

| Controller | Problema | Permissão Atual | Permissão Correta |
|------------|----------|-----------------|-------------------|
| **DocumentoController** | ❌ Sem verificação | Nenhuma | `documentos.manage` |
| **EntradaRetroativaController** | ❌ Permissão errada | `acesso.retroativo` | `entrada.retroativa` |
| **ValidadeController** | ❌ Sem verificação | Nenhuma | `validade.manage` |
| **RamalController** | ❌ Permissão errada | `brigada.manage` | `ramais.manage` |

---

## 🔧 DIFF 1: DocumentoController.php

### Problema:
Método `validar()` não verifica permissão `documentos.manage`

### Correção:
```php
// LINHA 111 - Adicionar após método validar() {
    public function validar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
+       // Verificar permissão documentos.manage
+       require_once __DIR__ . '/../../src/services/AuthorizationService.php';
+       $authService = new AuthorizationService();
+       
+       if (!$authService->hasPermission('documentos.manage')) {
+           http_response_code(403);
+           echo json_encode([
+               'success' => false,
+               'message' => 'Você não tem permissão para gerenciar documentos internacionais'
+           ]);
+           return;
+       }
        
        try {
            require_once __DIR__ . '/../../src/services/CSRFProtection.php';
            CSRFProtection::verifyRequest();
            // ... resto do código
```

### Métodos afetados:
- ✅ `getTipos()` - Não precisa (público)
- ✅ `getPaises()` - Não precisa (público)
- ⚠️ `validar()` - ADICIONAR permissão
- ✅ `buscar()` - Não precisa (busca apenas)

---

## 🔧 DIFF 2: EntradaRetroativaController.php

### Problema:
Usa permissão `acesso.retroativo` em vez de `entrada.retroativa`

### Correção:
```php
// LINHA 33 - Trocar nome da permissão
    private function checkPermission() {
-       if (!$this->authService->hasPermission('acesso.retroativo')) {
+       if (!$this->authService->hasPermission('entrada.retroativa')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para registrar entradas retroativas'
            ]);
            exit;
        }
    }
```

### Também corrigir:
```php
// LINHA 227 - Permissão de aprovação (opcional, comentar se não existir)
-       if (!$this->authService->hasPermission('acesso.aprovar_retroativo')) {
+       // TODO: Criar permissão entrada.aprovar_retroativa (v2.1.0)
+       if (!$this->authService->hasPermission('entrada.retroativa')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para aprovar entradas retroativas'
            ]);
            return;
        }
```

---

## 🔧 DIFF 3: ValidadeController.php

### Problema:
Métodos sensíveis (renovar, bloquear) não verificam permissão

### Correção 1 - Método renovar():
```php
// LINHA 134 - Adicionar após método renovar() {
    public function renovar() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
+       // Verificar permissão validade.manage
+       require_once __DIR__ . '/../../src/services/AuthorizationService.php';
+       $authService = new AuthorizationService();
+       
+       if (!$authService->hasPermission('validade.manage')) {
+           http_response_code(403);
+           echo json_encode([
+               'success' => false,
+               'message' => 'Você não tem permissão para gerenciar validade de cadastros'
+           ]);
+           return;
+       }
        
        try {
            // ... resto do código
```

### Correção 2 - Método bloquear():
```php
// LINHA 208 - Adicionar após método bloquear() {
    public function bloquear() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
+       // Verificar permissão validade.manage
+       require_once __DIR__ . '/../../src/services/AuthorizationService.php';
+       $authService = new AuthorizationService();
+       
+       if (!$authService->hasPermission('validade.manage')) {
+           http_response_code(403);
+           echo json_encode([
+               'success' => false,
+               'message' => 'Você não tem permissão para bloquear cadastros'
+           ]);
+           return;
+       }
        
        try {
            // ... resto do código
```

### Correção 3 - Método desbloquear():
```php
// LINHA 277 - Adicionar após método desbloquear() {
    public function desbloquear() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
+       // Verificar permissão validade.manage
+       require_once __DIR__ . '/../../src/services/AuthorizationService.php';
+       $authService = new AuthorizationService();
+       
+       if (!$authService->hasPermission('validade.manage')) {
+           http_response_code(403);
+           echo json_encode([
+               'success' => false,
+               'message' => 'Você não tem permissão para desbloquear cadastros'
+           ]);
+           return;
+       }
        
        try {
            // ... resto do código
```

### Métodos afetados:
- ✅ `expirando()` - Não precisa (leitura)
- ✅ `expirados()` - Não precisa (leitura)
- ⚠️ `renovar()` - ADICIONAR permissão
- ⚠️ `bloquear()` - ADICIONAR permissão
- ⚠️ `desbloquear()` - ADICIONAR permissão
- ✅ `configuracoes()` - Não precisa (leitura)
- ✅ `atualizarConfiguracoes()` - Já tem `config.manage`

---

## 🔧 DIFF 4: RamalController.php

### Problema:
Usa `brigada.manage` em vez de `ramais.manage`

### Correção (3 lugares):
```php
// LINHA 164, 236, 292 - Trocar nome da permissão

// Local 1 - Método adicionar()
-       if (!$authService->hasPermission('brigada.manage')) {
+       if (!$authService->hasPermission('ramais.manage')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para gerenciar ramais'
            ]);
            return;
        }

// Local 2 - Método atualizar()
-       if (!$authService->hasPermission('brigada.manage')) {
+       if (!$authService->hasPermission('ramais.manage')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para gerenciar ramais'
            ]);
            return;
        }

// Local 3 - Método remover()
-       if (!$authService->hasPermission('brigada.manage')) {
+       if (!$authService->hasPermission('ramais.manage')) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Você não tem permissão para gerenciar ramais'
            ]);
            return;
        }
```

### Também corrigir:
```php
// LINHA 330 - Método exportar()
-       if (!$authService->hasPermission('relatorios.exportar')) {
+       if (!$authService->hasPermission('reports.export')) {
            http_response_code(403);
            echo 'Sem permissão para exportar';
            return;
        }
```

---

## 📊 RESUMO DE CORREÇÕES

| Controller | Correções | Permissão | Métodos Afetados |
|------------|-----------|-----------|------------------|
| **DocumentoController** | 1 | `documentos.manage` | `validar()` |
| **EntradaRetroativaController** | 2 | `entrada.retroativa` | `checkPermission()`, `aprovar()` |
| **ValidadeController** | 3 | `validade.manage` | `renovar()`, `bloquear()`, `desbloquear()` |
| **RamalController** | 4 | `ramais.manage` + `reports.export` | `adicionar()`, `atualizar()`, `remover()`, `exportar()` |

**Total:** 10 correções em 4 controllers

---

## ✅ CHECKLIST DE APLICAÇÃO

Para aplicar essas correções:

1. [ ] Abrir `feature_v200/drafts/controllers/DocumentoController.php`
2. [ ] Aplicar DIFF 1 (adicionar verificação em `validar()`)
3. [ ] Abrir `feature_v200/drafts/controllers/EntradaRetroativaController.php`
4. [ ] Aplicar DIFF 2 (trocar `acesso.retroativo` → `entrada.retroativa`)
5. [ ] Abrir `feature_v200/drafts/controllers/ValidadeController.php`
6. [ ] Aplicar DIFF 3 (adicionar verificações em 3 métodos)
7. [ ] Abrir `feature_v200/drafts/controllers/RamalController.php`
8. [ ] Aplicar DIFF 4 (trocar permissões em 4 métodos)
9. [ ] Testar todos os endpoints com diferentes roles
10. [ ] Validar mensagens de erro 403

---

## 🧪 TESTES DE VALIDAÇÃO

Após aplicar correções, testar:

### Teste 1: documentos.manage
```bash
# Login como Recepção (TEM permissão)
curl -X POST /api/documentos/validar \
  -d '{"doc_type":"PASSAPORTE","doc_number":"AB123456"}'
# Esperado: 200 OK

# Login como Porteiro (NÃO TEM)
# Esperado: 403 Forbidden
```

### Teste 2: entrada.retroativa
```bash
# Login como Segurança (TEM permissão)
curl -X POST /api/profissionais/entrada-retroativa \
  -d '{"profissional_id":1,"data_entrada":"2025-10-10 08:00","motivo":"teste"}'
# Esperado: 200 OK

# Login como Recepção (NÃO TEM)
# Esperado: 403 Forbidden
```

### Teste 3: validade.manage
```bash
# Login como RH (TEM permissão)
curl -X POST /api/cadastros/validade/renovar \
  -d '{"tipo":"visitante","id":1,"dias":30}'
# Esperado: 200 OK

# Login como Segurança (NÃO TEM)
# Esperado: 403 Forbidden
```

### Teste 4: ramais.manage
```bash
# Login como Admin (TEM permissão)
curl -X POST /api/ramais/adicionar \
  -d '{"profissional_id":1,"ramal":"1234"}'
# Esperado: 200 OK

# Login como Porteiro (NÃO TEM)
# Esperado: 403 Forbidden
```

---

**Status:** ⚠️ CORREÇÕES PENDENTES  
**Próximo:** Aplicar diffs nos controllers draft
