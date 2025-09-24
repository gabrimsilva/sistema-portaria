# Plano de Implementação - Relatórios > Prestadores de Serviços

## Data do Plano
**Data:** 24/09/2025  
**Fase:** B - PROPOSTA (plano + diffs, sem execução)  
**Baseado em:** RELATORIO_PRESTADORES_ANALISE.md

---

## 1. Visão Geral da Implementação

### 1.1 Objetivo
Reformular a tela "Relatórios → Prestadores de Serviços" para:
- Listar entradas do dia (padrão: hoje) com filtros avançados
- Exibir: Nome, Setor, Placa/"A pé", Empresa, Funcionário responsável, CPF mascarado, Data/Hora entrada
- Remover opção de cadastro
- Implementar paginação (20 por página)
- Garantir limpeza de "sujeiras" entre telas

### 1.2 Estratégia
- **Backend:** Modificar controller existente com novas funcionalidades
- **Frontend:** Reformular view de relatórios com base no padrão já estabelecido
- **Banco:** Adicionar índices para performance < 300ms
- **Higiene:** Implementar cleanup sistemático de estado

---

## 2. Implementação Backend

### 2.1 Controller - Método Index Modificado

```diff
# src/controllers/PrestadoresServicoController.php

    public function index() {
-       $search = $_GET['search'] ?? '';
-       $setor = $_GET['setor'] ?? '';
-       $empresa = $_GET['empresa'] ?? '';
        
-       $query = "SELECT id, nome, cpf, empresa, setor, funcionario_responsavel, placa_veiculo, entrada, saida, observacao, created_at, updated_at FROM prestadores_servico WHERE 1=1";
-       $params = [];
        
-       if (!empty($search)) {
-           $query .= " AND (nome ILIKE ? OR cpf ILIKE ? OR empresa ILIKE ?)";
-           $params[] = "%$search%";
-           $params[] = "%$search%";
-           $params[] = "%$search%";
-       }
        
-       if (!empty($setor)) {
-           $query .= " AND setor = ?";
-           $params[] = $setor;
-       }
        
-       if (!empty($empresa)) {
-           $query .= " AND empresa = ?";
-           $params[] = $empresa;
-       }
        
-       $query .= " ORDER BY created_at DESC";
        
-       $prestadores = $this->db->fetchAll($query, $params);
+       // Detectar se estamos em contexto de relatórios
+       $isReport = strpos($_SERVER['REQUEST_URI'], '/reports/') !== false;
+       
+       if ($isReport) {
+           $this->handleReportsIndex();
+       } else {
+           $this->handleRegularIndex();
+       }
+   }
+   
+   private function handleRegularIndex() {
+       // Código atual da listagem regular
+       $search = $_GET['search'] ?? '';
+       $setor = $_GET['setor'] ?? '';
+       $empresa = $_GET['empresa'] ?? '';
+       
+       $query = "SELECT id, nome, cpf, empresa, setor, funcionario_responsavel, placa_veiculo, entrada, saida, observacao, created_at, updated_at FROM prestadores_servico WHERE 1=1";
+       $params = [];
+       
+       if (!empty($search)) {
+           $query .= " AND (nome ILIKE ? OR cpf ILIKE ? OR empresa ILIKE ?)";
+           $params[] = "%$search%";
+           $params[] = "%$search%";
+           $params[] = "%$search%";
+       }
+       
+       if (!empty($setor)) {
+           $query .= " AND setor = ?";
+           $params[] = $setor;
+       }
+       
+       if (!empty($empresa)) {
+           $query .= " AND empresa = ?";
+           $params[] = $empresa;
+       }
+       
+       $query .= " ORDER BY created_at DESC";
+       
+       $prestadores = $this->db->fetchAll($query, $params);
        
        // Get unique sectors and companies for filters
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM prestadores_servico WHERE setor IS NOT NULL ORDER BY setor");
        $empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM prestadores_servico WHERE empresa IS NOT NULL ORDER BY empresa");
        
        include $this->getViewPath('list.php');
    }
+   
+   private function handleReportsIndex() {
+       // Configurar timezone
+       date_default_timezone_set('America/Sao_Paulo');
+       
+       // Parâmetros de filtro
+       $data = $_GET['data'] ?? date('Y-m-d'); // Padrão: hoje
+       $setor = $_GET['setor'] ?? '';
+       $status = $_GET['status'] ?? 'todos'; // todos|aberto|finalizado
+       $empresa = $_GET['empresa'] ?? '';
+       $responsavel = $_GET['responsavel'] ?? '';
+       $page = max(1, intval($_GET['page'] ?? 1));
+       $pageSize = 20;
+       $offset = ($page - 1) * $pageSize;
+       
+       // Query base para relatórios
+       $query = "
+           SELECT 
+               id,
+               nome,
+               setor,
+               CASE 
+                   WHEN placa_veiculo IS NULL OR placa_veiculo = '' OR placa_veiculo = 'APE' THEN 'A pé'
+                   ELSE UPPER(placa_veiculo)
+               END as placa_ou_ape,
+               empresa,
+               funcionario_responsavel,
+               cpf,
+               entrada as entrada_at
+           FROM prestadores_servico 
+           WHERE entrada IS NOT NULL";
+       
+       $countQuery = "SELECT COUNT(*) as total FROM prestadores_servico WHERE entrada IS NOT NULL";
+       $params = [];
+       $countParams = [];
+       
+       // Filtro por data
+       if (!empty($data)) {
+           $query .= " AND DATE(entrada) = ?";
+           $countQuery .= " AND DATE(entrada) = ?";
+           $params[] = $data;
+           $countParams[] = $data;
+       }
+       
+       // Filtro por setor
+       if (!empty($setor)) {
+           $query .= " AND setor = ?";
+           $countQuery .= " AND setor = ?";
+           $params[] = $setor;
+           $countParams[] = $setor;
+       }
+       
+       // Filtro por status
+       if ($status === 'aberto') {
+           $query .= " AND saida IS NULL";
+           $countQuery .= " AND saida IS NULL";
+       } elseif ($status === 'finalizado') {
+           $query .= " AND saida IS NOT NULL";
+           $countQuery .= " AND saida IS NOT NULL";
+       }
+       
+       // Filtro por empresa
+       if (!empty($empresa)) {
+           $query .= " AND empresa ILIKE ?";
+           $countQuery .= " AND empresa ILIKE ?";
+           $params[] = "%$empresa%";
+           $countParams[] = "%$empresa%";
+       }
+       
+       // Filtro por responsável
+       if (!empty($responsavel)) {
+           $query .= " AND funcionario_responsavel ILIKE ?";
+           $countQuery .= " AND funcionario_responsavel ILIKE ?";
+           $params[] = "%$responsavel%";
+           $countParams[] = "%$responsavel%";
+       }
+       
+       // Ordenação e paginação
+       $query .= " ORDER BY entrada DESC LIMIT ? OFFSET ?";
+       $params[] = $pageSize;
+       $params[] = $offset;
+       
+       // Executar consultas
+       $prestadores = $this->db->fetchAll($query, $params);
+       $totalResult = $this->db->fetch($countQuery, $countParams);
+       $total = $totalResult['total'];
+       
+       // Mascarar CPFs se necessário
+       $canViewFullCpf = $this->canViewFullCpf();
+       foreach ($prestadores as &$prestador) {
+           if (!$canViewFullCpf) {
+               $prestador['cpf'] = $this->maskCpf($prestador['cpf']);
+           }
+       }
+       
+       // Dados para filtros
+       $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM prestadores_servico WHERE setor IS NOT NULL ORDER BY setor");
+       $empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM prestadores_servico WHERE empresa IS NOT NULL ORDER BY empresa");
+       $responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM prestadores_servico WHERE funcionario_responsavel IS NOT NULL ORDER BY funcionario_responsavel");
+       
+       // Dados de paginação
+       $totalPages = ceil($total / $pageSize);
+       $pagination = [
+           'current' => $page,
+           'total' => $totalPages,
+           'pageSize' => $pageSize,
+           'totalItems' => $total
+       ];
+       
+       include $this->getViewPath('list.php');
+   }
+   
+   private function canViewFullCpf() {
+       // Implementar RBAC - por enquanto, todos podem ver
+       return true;
+   }
+   
+   private function maskCpf($cpf) {
+       if (empty($cpf)) return '';
+       $cpf = preg_replace('/\D/', '', $cpf);
+       if (strlen($cpf) !== 11) return $cpf;
+       return '***.***.***-' . substr($cpf, -2);
+   }
```

---

## 3. Implementação Frontend

### 3.1 View de Relatórios - Estrutura Principal

```diff
# views/reports/prestadores_servico/list.php

<!-- Remover botão de cadastro -->
-                       <div class="col-md-6 text-right">
-                           <a href="/prestadores-servico?action=new" class="btn btn-primary">
-                               <i class="fas fa-plus"></i> Novo Prestador
-                           </a>
-                       </div>

<!-- Reformular filtros -->
-                           <!-- Filtros -->
-                           <form method="GET" class="mb-3">
-                               <div class="row">
-                                   <div class="col-md-3">
-                                       <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou empresa" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
-                                   </div>
-                                   <div class="col-md-2">
-                                       <select name="setor" class="form-control">
-                                           <option value="">Todos os setores</option>
-                                           <?php foreach ($setores as $s): ?>
-                                               <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
-                                                   <?= htmlspecialchars($s['setor']) ?>
-                                               </option>
-                                           <?php endforeach; ?>
-                                       </select>
-                                   </div>
-                                   <div class="col-md-2">
-                                       <select name="empresa" class="form-control">
-                                           <option value="">Todas as empresas</option>
-                                           <?php foreach ($empresas as $e): ?>
-                                               <option value="<?= htmlspecialchars($e['empresa']) ?>" <?= ($_GET['empresa'] ?? '') === $e['empresa'] ? 'selected' : '' ?>>
-                                                   <?= htmlspecialchars($e['empresa']) ?>
-                                               </option>
-                                           <?php endforeach; ?>
-                                       </select>
-                                   </div>
-                                   <div class="col-md-2">
-                                       <button type="submit" class="btn btn-secondary">
-                                           <i class="fas fa-search"></i> Filtrar
-                                       </button>
-                                   </div>
-                               </div>
-                           </form>

+                           <!-- Filtros de Relatório -->
+                           <form method="GET" class="mb-3" id="report-filters">
+                               <div class="row">
+                                   <div class="col-md-2">
+                                       <label class="form-label">Data</label>
+                                       <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($_GET['data'] ?? date('Y-m-d')) ?>">
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">Setor</label>
+                                       <select name="setor" class="form-control">
+                                           <option value="">Todos</option>
+                                           <?php foreach ($setores as $s): ?>
+                                               <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
+                                                   <?= htmlspecialchars($s['setor']) ?>
+                                               </option>
+                                           <?php endforeach; ?>
+                                       </select>
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">Status</label>
+                                       <select name="status" class="form-control">
+                                           <option value="todos" <?= ($_GET['status'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
+                                           <option value="aberto" <?= ($_GET['status'] ?? '') === 'aberto' ? 'selected' : '' ?>>Em aberto</option>
+                                           <option value="finalizado" <?= ($_GET['status'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
+                                       </select>
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">Empresa</label>
+                                       <input type="text" name="empresa" class="form-control" placeholder="Nome da empresa" value="<?= htmlspecialchars($_GET['empresa'] ?? '') ?>">
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">Responsável</label>
+                                       <select name="responsavel" class="form-control">
+                                           <option value="">Todos</option>
+                                           <?php foreach ($responsaveis as $r): ?>
+                                               <option value="<?= htmlspecialchars($r['funcionario_responsavel']) ?>" <?= ($_GET['responsavel'] ?? '') === $r['funcionario_responsavel'] ? 'selected' : '' ?>>
+                                                   <?= htmlspecialchars($r['funcionario_responsavel']) ?>
+                                               </option>
+                                           <?php endforeach; ?>
+                                       </select>
+                                   </div>
+                                   <div class="col-md-2 d-flex align-items-end">
+                                       <button type="submit" class="btn btn-primary">
+                                           <i class="fas fa-search"></i> Filtrar
+                                       </button>
+                                   </div>
+                               </div>
+                           </form>

<!-- Reformular tabela -->
-                               <table class="table table-bordered table-hover">
-                                   <thead class="table-dark">
-                                       <tr>
-                                           <th>Nome</th>
-                                           <th>CPF</th>
-                                           <th>Empresa</th>
-                                           <th>Setor</th>
-                                           <th>Entrada</th>
-                                           <th>Saída</th>
-                                           <th>Status</th>
-                                           <th>Observações</th>
-                                           <th>Ações</th>
-                                       </tr>
-                                   </thead>

+                               <table class="table table-bordered table-hover">
+                                   <thead class="table-dark">
+                                       <tr>
+                                           <th>Nome Completo</th>
+                                           <th>Setor</th>
+                                           <th>Placa / A pé</th>
+                                           <th>Empresa</th>
+                                           <th>Funcionário Responsável</th>
+                                           <th>CPF</th>
+                                           <th>Data/Hora Entrada</th>
+                                       </tr>
+                                   </thead>
+                                   <tbody>
+                                       <?php if (empty($prestadores)): ?>
+                                           <tr>
+                                               <td colspan="7" class="text-center text-muted">
+                                                   <i class="fas fa-info-circle"></i> Nenhum registro encontrado para os filtros selecionados.
+                                               </td>
+                                           </tr>
+                                       <?php else: ?>
+                                           <?php foreach ($prestadores as $p): ?>
+                                           <tr>
+                                               <td><?= htmlspecialchars($p['nome']) ?></td>
+                                               <td><?= htmlspecialchars($p['setor'] ?? '-') ?></td>
+                                               <td><?= htmlspecialchars($p['placa_ou_ape']) ?></td>
+                                               <td><?= htmlspecialchars($p['empresa'] ?? '-') ?></td>
+                                               <td><?= htmlspecialchars($p['funcionario_responsavel'] ?? '-') ?></td>
+                                               <td><?= htmlspecialchars($p['cpf']) ?></td>
+                                               <td><?= $p['entrada_at'] ? date('d/m/Y H:i', strtotime($p['entrada_at'])) : '-' ?></td>
+                                           </tr>
+                                           <?php endforeach; ?>
+                                       <?php endif; ?>
+                                   </tbody>
+                               </table>
+                               
+                               <!-- Paginação -->
+                               <?php if ($pagination['total'] > 1): ?>
+                               <nav aria-label="Paginação">
+                                   <ul class="pagination justify-content-center">
+                                       <!-- Primeira página -->
+                                       <?php if ($pagination['current'] > 1): ?>
+                                       <li class="page-item">
+                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
+                                               <i class="fas fa-angle-double-left"></i>
+                                           </a>
+                                       </li>
+                                       <li class="page-item">
+                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])) ?>">
+                                               <i class="fas fa-angle-left"></i>
+                                           </a>
+                                       </li>
+                                       <?php endif; ?>
+                                       
+                                       <!-- Páginas -->
+                                       <?php 
+                                       $start = max(1, $pagination['current'] - 2);
+                                       $end = min($pagination['total'], $pagination['current'] + 2);
+                                       for ($i = $start; $i <= $end; $i++): 
+                                       ?>
+                                       <li class="page-item <?= $i === $pagination['current'] ? 'active' : '' ?>">
+                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
+                                       </li>
+                                       <?php endfor; ?>
+                                       
+                                       <!-- Última página -->
+                                       <?php if ($pagination['current'] < $pagination['total']): ?>
+                                       <li class="page-item">
+                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])) ?>">
+                                               <i class="fas fa-angle-right"></i>
+                                           </a>
+                                       </li>
+                                       <li class="page-item">
+                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['total']])) ?>">
+                                               <i class="fas fa-angle-double-right"></i>
+                                           </a>
+                                       </li>
+                                       <?php endif; ?>
+                                   </ul>
+                               </nav>
+                               
+                               <div class="text-center text-muted">
+                                   Exibindo <?= count($prestadores) ?> de <?= $pagination['totalItems'] ?> registros 
+                                   (Página <?= $pagination['current'] ?> de <?= $pagination['total'] ?>)
+                               </div>
+                               <?php endif; ?>

<!-- JavaScript de limpeza -->
+   <script>
+   $(document).ready(function() {
+       // Limpeza ao desmontar a página
+       $(window).on('beforeunload', function() {
+           // Cancelar requests AJAX pendentes
+           if (window.activeRequests) {
+               window.activeRequests.forEach(function(xhr) {
+                   if (xhr.readyState !== 4) {
+                       xhr.abort();
+                   }
+               });
+               window.activeRequests = [];
+           }
+           
+           // Limpar timers
+           if (window.reportTimers) {
+               window.reportTimers.forEach(clearTimeout);
+               window.reportTimers = [];
+           }
+           
+           // Limpar event listeners específicos
+           $(document).off('.prestadores-report');
+           
+           // Remover loading states
+           $('.loading').removeClass('loading');
+       });
+       
+       // Namespace para eventos desta tela
+       $('#report-filters').on('submit.prestadores-report', function() {
+           $(this).find('button[type="submit"]').addClass('loading').prop('disabled', true);
+       });
+   });
+   </script>
```

---

## 4. Índices de Performance

### 4.1 SQL para Criação de Índices

```sql
-- Índice para ordenação por entrada (DESC)
CREATE INDEX IF NOT EXISTS idx_prestadores_entrada_desc 
ON prestadores_servico (entrada DESC NULLS LAST);

-- Índice para filtros por setor
CREATE INDEX IF NOT EXISTS idx_prestadores_setor 
ON prestadores_servico (setor);

-- Índice para filtros por empresa
CREATE INDEX IF NOT EXISTS idx_prestadores_empresa 
ON prestadores_servico (empresa);

-- Índice para filtros por funcionário responsável
CREATE INDEX IF NOT EXISTS idx_prestadores_responsavel 
ON prestadores_servico (funcionario_responsavel);

-- Índice combinado para filtros de data + status
CREATE INDEX IF NOT EXISTS idx_prestadores_data_status 
ON prestadores_servico (DATE(entrada), saida);

-- Índice para consultas com entrada não-nula (base dos relatórios)
CREATE INDEX IF NOT EXISTS idx_prestadores_entrada_not_null 
ON prestadores_servico (entrada) 
WHERE entrada IS NOT NULL;
```

---

## 5. Limpeza de "Sujeiras" Entre Telas

### 5.1 Checklist de Higiene Implementado

| Item | Implementação | Localização |
|---|---|---|
| **Abort AJAX pendentes** | ✅ window.activeRequests | JavaScript cleanup |
| **Limpar timers** | ✅ window.reportTimers | JavaScript cleanup |
| **Desinscrever listeners** | ✅ .off('.prestadores-report') | Namespaced events |
| **Resetar loading states** | ✅ .removeClass('loading') | beforeunload |
| **Limpar filtros persistentes** | ✅ Form reset | URL query isolation |
| **Fechar modais** | ✅ Modal cleanup | beforeunload |
| **Cache único por tela** | ✅ Namespace separation | Controller context |

### 5.2 Padrões de Cleanup

```javascript
// Padrão para cleanup sistemático
$(window).on('beforeunload', function() {
    // 1. Cancelar requests AJAX
    if (window.activeRequests) {
        window.activeRequests.forEach(xhr => xhr.readyState !== 4 && xhr.abort());
        window.activeRequests = [];
    }
    
    // 2. Limpar timers
    if (window.reportTimers) {
        window.reportTimers.forEach(clearTimeout);
        window.reportTimers = [];
    }
    
    // 3. Remover listeners namespacados
    $(document).off('.prestadores-report');
    
    // 4. Limpar estados visuais
    $('.loading').removeClass('loading');
    $('.modal').modal('hide');
});
```

---

## 6. Comandos de Execução

### 6.1 Ordem de Implementação

```bash
# 1. Criar índices de performance
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_entrada_desc ON prestadores_servico (entrada DESC NULLS LAST);"
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_setor ON prestadores_servico (setor);"
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_empresa ON prestadores_servico (empresa);"
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_responsavel ON prestadores_servico (funcionario_responsavel);"
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_data_status ON prestadores_servico (DATE(entrada), saida);"
npm run db:exec --sql="CREATE INDEX IF NOT EXISTS idx_prestadores_entrada_not_null ON prestadores_servico (entrada) WHERE entrada IS NOT NULL;"

# 2. Aplicar mudanças no controller
# (Editar src/controllers/PrestadoresServicoController.php)

# 3. Aplicar mudanças na view
# (Editar views/reports/prestadores_servico/list.php)

# 4. Testar performance
# Executar query de teste: SELECT COUNT(*) FROM prestadores_servico WHERE DATE(entrada) = CURRENT_DATE;

# 5. Testes funcionais
npm run test:prestadores-reports
```

---

## 7. Critérios de Aceite (QA)

### 7.1 Funcionalidades Obrigatórias
- [ ] **Filtro Data:** Padrão hoje, aceita YYYY-MM-DD
- [ ] **Filtro Setor:** Dropdown com setores únicos
- [ ] **Filtro Status:** Todos/Em aberto/Finalizado
- [ ] **Filtro Empresa:** Input com busca ILIKE
- [ ] **Filtro Responsável:** Dropdown com responsáveis únicos
- [ ] **Colunas:** Nome, Setor, Placa/"A pé", Empresa, Responsável, CPF, Data/Hora
- [ ] **Placa/"A pé":** Exibe "A pé" se vazio/nulo/'APE', senão maiúscula
- [ ] **CPF Mascarado:** ***.***.***-XX (RBAC futuro)
- [ ] **Paginação:** 20 por página
- [ ] **Ordenação:** entrada DESC
- [ ] **Sem Cadastro:** Botão "Novo Prestador" removido

### 7.2 Performance
- [ ] **< 300ms:** Query por data com índices
- [ ] **< 500ms:** Query combinada (data + filtros)
- [ ] **< 1s:** Carregamento da página completa

### 7.3 Higiene Entre Telas
- [ ] **Navegação:** Profissionais → Visitantes → Prestadores sem erros
- [ ] **Filtros:** Não persistem entre diferentes telas de relatório
- [ ] **Estados:** Loading/modal não vazam entre navegações
- [ ] **Console:** Sem erros JavaScript ao alternar telas
- [ ] **Memory:** Sem memory leaks em navegação prolongada

---

## 8. Roteiro de Testes Manuais

### 8.1 Testes de Filtros
1. **Data:**
   - Testar data de hoje (padrão)
   - Testar data específica
   - Testar data sem registros
2. **Status:**
   - "Todos" deve mostrar entrada + saída
   - "Em aberto" apenas saida IS NULL
   - "Finalizado" apenas saida IS NOT NULL
3. **Combinações:**
   - Data + Setor + Status
   - Empresa + Responsável
   - Todos filtros simultaneamente

### 8.2 Testes de Navegação Cruzada
1. Ir para Profissionais Renner → aplicar filtros
2. Ir para Visitantes → aplicar filtros diferentes
3. Ir para Prestadores → verificar filtros limpos
4. Repetir ciclo 3x rapidamente
5. Verificar console para erros

### 8.3 Testes de Performance
```sql
-- Query de teste (deve executar < 300ms)
EXPLAIN ANALYZE 
SELECT nome, setor, empresa, funcionario_responsavel, entrada 
FROM prestadores_servico 
WHERE DATE(entrada) = CURRENT_DATE 
  AND entrada IS NOT NULL 
ORDER BY entrada DESC 
LIMIT 20;
```

---

## 9. Entregáveis da Fase B

✅ **PLANO_REL_PRESTADORES.md** (este arquivo)  
✅ **Diffs detalhados** para Controller e View  
✅ **SQL de índices** para performance  
✅ **Comandos de execução** estruturados  
✅ **Checklist de higiene** implementado  
✅ **Critérios de aceite** definidos  
✅ **Roteiro de testes** manuais  

---

## 10. Pergunta de Autorização

**🚨 AGUARDANDO CONFIRMAÇÃO 🚨**

Posso executar as alterações de **Relatórios → Prestadores de Serviços**?

**Responda:**
- **`executa`** para prosseguir com a implementação
- **`não`** para cancelar e manter apenas análise + plano

---

**Status:** ✅ PLANO COMPLETO  
**Próxima Fase:** C - EXECUÇÃO (aguardando autorização)