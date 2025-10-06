# Módulo de Brigada de Incêndio - Documentação Completa

## 📋 Contexto do Projeto

**Sistema:** Sistema de Controle de Acesso (Portaria)  
**Stack:** PHP 8.2+ com PostgreSQL (via PDO)  
**Arquitetura:** MVC puro (sem namespaces, sem frameworks)  
**Ambiente:** Docker em VM (10.3.1.135)  
**Repositório:** GitHub privado (gabrimsilva/sistema-portaria)

## 🎯 Objetivo do Módulo

Gerenciar brigadistas de incêndio da empresa, permitindo:
- Cadastro de profissionais como brigadistas
- Definição de vigência (data início/fim)
- Identificação visual no dashboard (badge vermelho com extintor 🧯)
- Controle de status ativo/inativo

## 🗄️ Estrutura do Banco de Dados

### Tabela: `brigadistas`

```sql
CREATE TABLE brigadistas (
    id SERIAL PRIMARY KEY,
    professional_id INTEGER NOT NULL REFERENCES profissionais_renner(id) ON DELETE CASCADE,
    start_date DATE NOT NULL,
    end_date DATE,
    active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(professional_id)
);

CREATE INDEX idx_brigadistas_professional ON brigadistas(professional_id);
CREATE INDEX idx_brigadistas_active ON brigadistas(active);
```

**Campos:**
- `id`: Chave primária serial
- `professional_id`: FK para profissionais_renner (único, não permite duplicatas)
- `start_date`: Data de início da vigência
- `end_date`: Data de término (NULL = sem prazo)
- `active`: Status do brigadista (TRUE/FALSE)
- `notes`: Observações adicionais
- `created_at/updated_at`: Timestamps automáticos

**Relacionamentos:**
- 1:1 com `profissionais_renner` (um profissional = no máximo um registro de brigadista)
- CASCADE DELETE: ao deletar profissional, deleta registro de brigadista

## 📁 Estrutura de Arquivos

```
src/
├── controllers/
│   └── BrigadaController.php          # Controller principal do módulo
├── services/
│   ├── NavigationService.php          # Serviço de navegação (inclui menu Brigada)
│   └── AuthorizationService.php       # RBAC (permissão: brigada.view/edit)
└── models/
    └── Database.php                   # PDO wrapper

views/
└── brigada/
    └── index.php                      # View principal (listagem + form)

public/
├── router.php                         # Roteador (mapeia /api/brigada/*)
└── assets/
    └── js/
        └── brigada.js                 # JavaScript do módulo (CRUD, modais)

migrations/
└── 001_create_brigadistas.sql         # Migration da tabela
```

## 🔧 Controller: BrigadaController.php

**Localização:** `src/controllers/BrigadaController.php`

**Responsabilidades:**
- Renderizar página principal
- APIs REST para CRUD
- Listagem de brigadistas com JOIN de profissionais
- Validações de negócio

**Métodos:**

### 1. `index()` - Página principal
```php
public function index() {
    $this->checkPermission('brigada.view');
    require __DIR__ . '/../../views/brigada/index.php';
}
```

### 2. `list()` - API GET /api/brigada
Retorna todos os brigadistas com dados do profissional:
```php
$brigadistas = $this->db->fetchAll("
    SELECT b.*, p.nome, p.cpf, p.setor
    FROM brigadistas b
    JOIN profissionais_renner p ON p.id = b.professional_id
    ORDER BY b.active DESC, p.nome ASC
");
```

**Response JSON:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "professional_id": 107,
      "nome": "GABRIEL MARCELO DA SILVA",
      "cpf": "12345678901",
      "setor": "TI",
      "start_date": "2025-01-01",
      "end_date": null,
      "active": true,
      "notes": "Treinamento concluído em 2024"
    }
  ]
}
```

### 3. `create()` - API POST /api/brigada
Cadastra novo brigadista:

**Request Body:**
```json
{
  "professional_id": 107,
  "start_date": "2025-01-01",
  "end_date": "2026-01-01",
  "active": true,
  "notes": "Observações"
}
```

**Validações:**
- `professional_id` é obrigatório e existe em profissionais_renner
- `start_date` é obrigatório e formato DATE válido
- `end_date` (opcional) não pode ser anterior a start_date
- Verifica duplicata (constraint UNIQUE)

### 4. `update()` - API PUT /api/brigada/{id}
Atualiza brigadista existente (mesmas validações do create)

### 5. `delete()` - API DELETE /api/brigada/{id}
Deleta brigadista (soft delete possível se implementado)

### 6. `searchProfessionals()` - API GET /api/brigada/search-professionals
Busca profissionais para autocomplete:
```php
$professionals = $this->db->fetchAll("
    SELECT id, nome, cpf, setor
    FROM profissionais_renner
    WHERE nome ILIKE :term OR cpf LIKE :term
    ORDER BY nome ASC
    LIMIT 20
", ['term' => "%{$_GET['term']}%"]);
```

## 🎨 View: index.php

**Localização:** `views/brigada/index.php`

**Estrutura:**
1. Header com breadcrumb
2. Card Bootstrap com tabela de brigadistas
3. Botão "Adicionar Brigadista" (abre modal)
4. Modal de cadastro/edição
5. Modal de confirmação de exclusão

**Tabela de Brigadistas:**
```html
<table class="table table-striped">
  <thead>
    <tr>
      <th>Nome</th>
      <th>CPF</th>
      <th>Setor</th>
      <th>Vigência</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody id="brigadistasTableBody">
    <!-- Preenchido via JavaScript -->
  </tbody>
</table>
```

**Modal de Cadastro/Edição:**
- Campo autocomplete para buscar profissional (jQuery UI)
- Campo data de início (required)
- Campo data de término (opcional)
- Checkbox ativo/inativo
- Textarea para observações

## 🖥️ JavaScript: brigada.js

**Localização:** `public/assets/js/brigada.js`

**Funcionalidades:**

### 1. Carregamento da Tabela
```javascript
function loadBrigadistas() {
    fetch('/api/brigada')
        .then(response => response.json())
        .then(data => {
            const tbody = $('#brigadistasTableBody');
            tbody.empty();
            data.data.forEach(brigadista => {
                tbody.append(renderBrigadistaRow(brigadista));
            });
        });
}
```

### 2. Autocomplete de Profissionais
```javascript
$('#professionalSearch').autocomplete({
    source: function(request, response) {
        $.get('/api/brigada/search-professionals', {
            term: request.term
        }, function(data) {
            response(data.data.map(prof => ({
                label: `${prof.nome} - ${prof.cpf}`,
                value: prof.id
            })));
        });
    }
});
```

### 3. CRUD via AJAX
- `createBrigadista()` - POST com FormData
- `updateBrigadista(id)` - PUT com JSON
- `deleteBrigadista(id)` - DELETE com confirmação

## 🔗 Integração com Dashboard

**Arquivo:** `src/controllers/DashboardController.php`

### Query Modificada: getPessoasNaEmpresa()

```php
$profissionaisAtivos = $this->db->fetchAll("
    SELECT p.nome, r.cpf, r.empresa, p.setor, 
           COALESCE(r.retorno, r.entrada_at) as hora_entrada, 
           'Profissional Renner' as tipo, r.id, r.placa_veiculo,
           CASE WHEN b.id IS NOT NULL AND b.active = TRUE 
                THEN TRUE 
                ELSE FALSE 
           END as is_brigadista
    FROM registro_acesso r
    JOIN profissionais_renner p ON p.id = r.profissional_renner_id
    LEFT JOIN brigadistas b ON b.professional_id = p.id AND b.active = TRUE
    WHERE r.tipo = 'profissional_renner' 
      AND (r.entrada_at IS NOT NULL OR r.retorno IS NOT NULL) 
      AND r.saida_final IS NULL
    ORDER BY COALESCE(r.retorno, r.entrada_at) DESC
");

// CRÍTICO: Normalizar boolean PostgreSQL
foreach ($profissionaisAtivos as &$profissional) {
    $value = $profissional['is_brigadista'] ?? false;
    $profissional['is_brigadista'] = ($value === true || $value === 't' || $value === '1' || $value === 1);
}
unset($profissional);
```

### Badge Visual na View

**Arquivo:** `views/dashboard/index.php`

```php
<td>
    <?= htmlspecialchars($pessoa['nome']) ?>
    <?php if (isset($pessoa['is_brigadista']) && $pessoa['is_brigadista'] === true): ?>
        <span class="badge badge-danger ml-2" title="Brigadista de Incêndio">
            <i class="fas fa-fire-extinguisher"></i> Brigadista
        </span>
    <?php endif; ?>
</td>
```

## 🛡️ RBAC (Controle de Acesso)

**Arquivo:** `src/services/AuthorizationService.php`

**Permissões do Módulo:**
- `brigada.view` - Visualizar brigadistas
- `brigada.edit` - Criar/editar/deletar brigadistas

**Uso no Controller:**
```php
$this->checkPermission('brigada.view');  // No index()
$this->checkPermission('brigada.edit');  // No create/update/delete
```

**Configuração no Banco:**
A permissão `brigada.*` deve ser adicionada à tabela `roles` ou similar.

## 🔀 Roteamento

**Arquivo:** `public/router.php`

```php
// Brigada API routes
if (preg_match('#^/api/brigada/search-professionals#', $uri)) {
    require_once __DIR__ . '/../src/controllers/BrigadaController.php';
    $controller = new BrigadaController();
    $controller->searchProfessionals();
    exit;
}

if (preg_match('#^/api/brigada/(\d+)$#', $uri, $matches)) {
    require_once __DIR__ . '/../src/controllers/BrigadaController.php';
    $controller = new BrigadaController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $controller->update($matches[1]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $controller->delete($matches[1]);
    }
    exit;
}

if ($uri === '/api/brigada') {
    require_once __DIR__ . '/../src/controllers/BrigadaController.php';
    $controller = new BrigadaController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller->list();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->create();
    }
    exit;
}

// Brigada page
if ($uri === '/brigada') {
    require_once __DIR__ . '/../src/controllers/BrigadaController.php';
    $controller = new BrigadaController();
    $controller->index();
    exit;
}
```

## 📱 Menu de Navegação

**Arquivo:** `src/services/NavigationService.php`

```php
[
    'label' => 'Brigada de Incêndio',
    'url' => '/brigada',
    'icon' => 'fire-extinguisher',
    'permission' => 'brigada.view',
    'submenu' => []
]
```

## ⚠️ IMPORTANTE: Tratamento de Booleanos PostgreSQL

### Problema
PostgreSQL/PDO pode retornar valores booleanos em 3 formatos diferentes:
- Strings: `'t'` / `'f'`
- Booleanos: `true` / `false`
- Inteiros: `1` / `0`

### Solução
**Sempre normalizar antes de comparações strict (`===`):**

```php
// ❌ ERRADO - Pode falhar
if ($row['active'] === true) { ... }

// ✅ CORRETO - Robusto
$value = $row['active'] ?? false;
$isActive = ($value === true || $value === 't' || $value === '1' || $value === 1);
if ($isActive === true) { ... }
```

### Padrão do Projeto
Normalizar no controller, nunca na view:
```php
foreach ($results as &$row) {
    $row['active'] = ($row['active'] === true || $row['active'] === 't' || $row['active'] === '1' || $row['active'] === 1);
}
unset($row);
```

## 🔒 Segurança

### CSRF Protection
```php
use CSRFProtection;

// No formulário
echo CSRFProtection::getHiddenInput();

// Na API (POST/PUT/DELETE)
CSRFProtection::validate();
```

**Arquivo:** `config/csrf.php`

### SQL Injection Prevention
Sempre usar prepared statements:
```php
$stmt = $this->db->prepare("SELECT * FROM brigadistas WHERE id = :id");
$stmt->execute(['id' => $id]);
```

### XSS Prevention
Escape de output nas views:
```php
<?= htmlspecialchars($brigadista['nome']) ?>
```

## 📊 Padrões do Projeto

### 1. Controllers
- Criam própria instância de `Database`
- Usam `AuthorizationService` para RBAC
- Métodos públicos para rotas
- Response JSON com estrutura: `{ success, data, message }`

### 2. Views
- PHP puro com template includes
- Bootstrap 4.6.2 para UI
- Font Awesome 5 para ícones
- jQuery 3.6 + jQuery UI para interatividade

### 3. Database
- PDO com PostgreSQL
- Prepared statements obrigatórios
- Timestamps com timezone (TIMESTAMPTZ)
- Índices para performance

### 4. JavaScript
- jQuery para AJAX
- Fetch API para APIs REST
- Validação client-side + server-side
- Mensagens de erro/sucesso com toasts/alerts

## 🚀 Fluxo de Dados Completo

### Exemplo: Cadastrar Brigadista

1. **User Action:** Clica em "Adicionar Brigadista"
2. **Frontend:** Abre modal, carrega autocomplete de profissionais
3. **User Input:** Preenche formulário, seleciona profissional
4. **JavaScript:** `brigadaForm.submit()` → `createBrigadista()`
5. **AJAX:** `POST /api/brigada` com JSON body
6. **Router:** `router.php` identifica rota e instancia controller
7. **Controller:** `BrigadaController->create()`
   - Valida CSRF token
   - Valida permissão `brigada.edit`
   - Valida dados (required, formatos, lógica)
   - Verifica duplicata
   - INSERT no banco via prepared statement
   - Log de auditoria (se implementado)
8. **Response:** JSON com sucesso ou erro
9. **Frontend:** Atualiza tabela, fecha modal, mostra mensagem

## 🧪 Comandos Úteis

### Rodar Migration
```bash
docker exec -i controle-portaria-app psql -U postgres -d sistema_portaria < migrations/001_create_brigadistas.sql
```

### Verificar Dados
```sql
SELECT b.id, p.nome, b.start_date, b.end_date, b.active
FROM brigadistas b
JOIN profissionais_renner p ON p.id = b.professional_id;
```

### Testar API
```bash
# Listar brigadistas
curl http://10.3.1.135:5000/api/brigada

# Buscar profissionais
curl "http://10.3.1.135:5000/api/brigada/search-professionals?term=gabriel"
```

## 📝 Checklist de Implementação

Ao criar um novo módulo similar:

- [ ] Criar migration SQL com tabela + índices
- [ ] Criar Controller com métodos CRUD
- [ ] Criar View com formulário + tabela
- [ ] Criar JavaScript para interatividade
- [ ] Adicionar rotas no `router.php`
- [ ] Adicionar item no menu (`NavigationService.php`)
- [ ] Configurar permissões RBAC
- [ ] Implementar validações (client + server)
- [ ] Adicionar CSRF protection
- [ ] Testar todos os endpoints
- [ ] Normalizar booleanos PostgreSQL
- [ ] Documentar no `replit.md`

## 🐛 Troubleshooting

### Badge não aparece no dashboard
- Verificar se LEFT JOIN está correto
- Confirmar normalização de booleanos no controller
- Verificar condição `is_brigadista === true` na view
- Checar se profissional tem `active = TRUE` na tabela brigadistas

### Autocomplete não funciona
- Verificar rota `/api/brigada/search-professionals` no router.php
- Confirmar jQuery UI está carregado
- Checar console do browser para erros JavaScript

### Erro 404 nas APIs
- Verificar ordem das rotas no `router.php`
- Confirmar regex de matching
- Testar com curl/Postman

### Erro de permissão
- Verificar se usuário tem permissão `brigada.view` ou `brigada.edit`
- Confirmar chamada `checkPermission()` no controller
- Revisar configuração de roles no banco

## 📚 Referências

- **Bootstrap 4.6.2:** https://getbootstrap.com/docs/4.6/
- **Font Awesome 5:** https://fontawesome.com/v5/search
- **jQuery UI Autocomplete:** https://jqueryui.com/autocomplete/
- **PostgreSQL Boolean:** https://www.postgresql.org/docs/current/datatype-boolean.html
- **PHP PDO:** https://www.php.net/manual/en/book.pdo.php

---

**Documento gerado em:** 06/10/2025  
**Versão do Sistema:** 1.0.0  
**Última atualização do módulo:** 06/10/2025
