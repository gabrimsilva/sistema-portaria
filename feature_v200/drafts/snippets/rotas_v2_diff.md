# DIFF: Novas Rotas para v2.0.0

## 📍 Arquivo: `public/index.php`

### 🔹 BLOCO 1: Adicionar após linha 561 (após `case 'api/professionals/search':`)

```php
        // ============================================
        // 🆕 V2.0.0 - RAMAIS
        // ============================================
        case 'ramais':
            require_once '../src/controllers/RamalController.php';
            $controller = new RamalController();
            $controller->index();
            break;
        
        case 'api/ramais/buscar':
            require_once '../src/controllers/RamalController.php';
            $controller = new RamalController();
            $controller->buscar();
            break;
        
        case 'api/ramais/setores':
            require_once '../src/controllers/RamalController.php';
            $controller = new RamalController();
            $controller->setores();
            break;
        
        case 'api/ramais/adicionar':
            require_once '../src/controllers/RamalController.php';
            $controller = new RamalController();
            $controller->adicionar();
            break;
        
        case 'api/ramais/export':
            require_once '../src/controllers/RamalController.php';
            $controller = new RamalController();
            $controller->exportar();
            break;
```

### 🔹 BLOCO 2: Adicionar no `default:` section (antes de `else if (preg_match('/^entradas$/', $path))`)

Localizar **linha ~592** onde está:

```php
// Handle new API endpoints
else if (preg_match('/^entradas$/', $path)) {
```

**ADICIONAR ANTES:**

```php
// ============================================
// 🆕 V2.0.0 - DOCUMENTOS INTERNACIONAIS
// ============================================
else if (preg_match('/^api\/documentos\/tipos$/', $path)) {
    require_once '../src/controllers/DocumentoController.php';
    $controller = new DocumentoController();
    $controller->getTipos();
}
else if (preg_match('/^api\/documentos\/paises$/', $path)) {
    require_once '../src/controllers/DocumentoController.php';
    $controller = new DocumentoController();
    $controller->getPaises();
}
else if (preg_match('/^api\/documentos\/validar$/', $path)) {
    require_once '../src/controllers/DocumentoController.php';
    $controller = new DocumentoController();
    $controller->validar();
}
else if (preg_match('/^api\/documentos\/buscar$/', $path)) {
    require_once '../src/controllers/DocumentoController.php';
    $controller = new DocumentoController();
    $controller->buscar();
}

// ============================================
// 🆕 V2.0.0 - ENTRADA RETROATIVA
// ============================================
else if (preg_match('/^api\/profissionais\/entrada-retroativa$/', $path)) {
    require_once '../src/controllers/EntradaRetroativaController.php';
    $controller = new EntradaRetroativaController();
    $controller->registrar();
}
else if (preg_match('/^api\/entradas-retroativas$/', $path)) {
    require_once '../src/controllers/EntradaRetroativaController.php';
    $controller = new EntradaRetroativaController();
    $controller->listar();
}
else if (preg_match('/^api\/entradas-retroativas\/stats$/', $path)) {
    require_once '../src/controllers/EntradaRetroativaController.php';
    $controller = new EntradaRetroativaController();
    $controller->estatisticas();
}
else if (preg_match('/^api\/entradas-retroativas\/(\d+)\/aprovar$/', $path, $matches)) {
    require_once '../src/controllers/EntradaRetroativaController.php';
    $controller = new EntradaRetroativaController();
    $controller->aprovar($matches[1]);
}

// ============================================
// 🆕 V2.0.0 - VALIDADE DE CADASTROS
// ============================================
else if (preg_match('/^api\/cadastros\/validade\/expirando$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    $controller->expirando();
}
else if (preg_match('/^api\/cadastros\/validade\/expirados$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    $controller->expirados();
}
else if (preg_match('/^api\/cadastros\/validade\/renovar$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    $controller->renovar();
}
else if (preg_match('/^api\/cadastros\/validade\/bloquear$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    $controller->bloquear();
}
else if (preg_match('/^api\/cadastros\/validade\/desbloquear$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    $controller->desbloquear();
}
else if (preg_match('/^api\/cadastros\/validade\/configuracoes$/', $path)) {
    require_once '../src/controllers/ValidadeController.php';
    $controller = new ValidadeController();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller->configuracoes();
    } else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $controller->atualizarConfiguracoes();
    }
}

// ============================================
// 🆕 V2.0.0 - RAMAIS (rotas dinâmicas)
// ============================================
else if (preg_match('/^api\/ramais\/(\d+)$/', $path, $matches)) {
    require_once '../src/controllers/RamalController.php';
    $controller = new RamalController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $controller->atualizar($matches[1]);
    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $controller->remover($matches[1]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    }
}

// ============================================
// ROTAS ANTIGAS (manter)
// ============================================
```

---

## 📋 RESUMO DAS ROTAS ADICIONADAS

### 🌍 Documentos Internacionais (4 rotas)
- `GET /api/documentos/tipos` - Lista tipos de documentos
- `GET /api/documentos/paises` - Lista países
- `POST /api/documentos/validar` - Valida documento
- `GET /api/documentos/buscar` - Busca por documento

### 📅 Entrada Retroativa (4 rotas)
- `POST /api/profissionais/entrada-retroativa` - Registra entrada retroativa
- `GET /api/entradas-retroativas` - Lista entradas retroativas
- `GET /api/entradas-retroativas/stats` - Estatísticas
- `POST /api/entradas-retroativas/{id}/aprovar` - Aprovar entrada

### ⏰ Validade de Cadastros (6 rotas)
- `GET /api/cadastros/validade/expirando` - Cadastros expirando
- `GET /api/cadastros/validade/expirados` - Cadastros expirados
- `POST /api/cadastros/validade/renovar` - Renovar cadastro
- `POST /api/cadastros/validade/bloquear` - Bloquear cadastro
- `POST /api/cadastros/validade/desbloquear` - Desbloquear cadastro
- `GET/PUT /api/cadastros/validade/configuracoes` - Configurações

### 📞 Ramais (8 rotas)
- `GET /ramais` - Página de consulta
- `GET /api/ramais/buscar` - Buscar ramais
- `GET /api/ramais/setores` - Listar setores
- `POST /api/ramais/adicionar` - Adicionar ramal
- `PUT /api/ramais/{id}` - Atualizar ramal
- `DELETE /api/ramais/{id}` - Remover ramal
- `GET /api/ramais/export` - Exportar CSV

---

## ⚠️ IMPORTANTE

1. **NÃO APLICAR** este diff sem:
   - ✅ Migrations M2 executadas
   - ✅ Controllers copiados para `src/controllers/`
   - ✅ Services copiados para `src/services/`
   - ✅ Testes de integração realizados

2. **Ordem de aplicação:**
   - Primeiro: Migrations SQL
   - Segundo: Controllers e Services
   - Terceiro: Rotas (este diff)
   - Quarto: Views e JS (M4)

3. **Backup obrigatório** antes de aplicar

4. **Verificar permissões** RBAC para novas rotas

---

**Versão:** 2.0.0  
**Data:** 15/10/2025  
**Status:** DRAFT - Não aplicado
