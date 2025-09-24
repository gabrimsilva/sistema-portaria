# PLANO - Reformulação Relatórios > Visitantes

**Data:** 24/09/2025  
**Objetivo:** Implementar relatório de visitantes com filtros avançados, paginação e interface moderna

## 🎯 RESUMO EXECUTIVO

### Problemas Identificados:
1. **View desatualizada:** `views/reports/visitantes/list.php` tem menu antigo + botão de cadastro
2. **Filtros limitados:** Falta data, empresa, funcionário responsável
3. **Performance:** Sem índices específicos para filtros
4. **Segurança:** CPF não mascarado
5. **UX:** Sem paginação, interface básica

### Solução:
1. **Back-end:** Reformular `VisitantesNovoController` para contexto de relatórios
2. **Front-end:** Reformular view com filtros avançados e paginação
3. **Performance:** Adicionar índices estratégicos
4. **Segurança:** Implementar mascaramento de CPF

---

## 🔧 FASE B - PROPOSTA DETALHADA

### 1. BACK-END

#### A. Modificar Controller Principal

**Arquivo:** `src/controllers/VisitantesNovoController.php`

```diff
    public function index() {
        $search = $_GET['search'] ?? '';
        $setor = $_GET['setor'] ?? '';
        $status = $_GET['status'] ?? '';
+       $data = $_GET['data'] ?? date('Y-m-d');
+       $empresa = $_GET['empresa'] ?? '';
+       $responsavel = $_GET['responsavel'] ?? '';
+       $page = max(1, (int)($_GET['page'] ?? 1));
+       $pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 20)));
+       $offset = ($page - 1) * $pageSize;

-       $query = "SELECT * FROM visitantes_novo WHERE 1=1";
+       // Para relatórios, filtrar por data de entrada
+       $isReportContext = strpos($_SERVER['REQUEST_URI'] ?? '', '/reports/') !== false;
+       
+       if ($isReportContext) {
+           $query = "SELECT 
+               nome,
+               setor,
+               CASE 
+                   WHEN placa_veiculo IS NULL OR placa_veiculo = '' THEN 'A pé'
+                   ELSE UPPER(placa_veiculo)
+               END as placa_ou_ape,
+               empresa,
+               funcionario_responsavel,
+               cpf,
+               hora_entrada,
+               CASE 
+                   WHEN hora_saida IS NULL THEN 'Em aberto'
+                   ELSE 'Finalizado'
+               END as status_entrada
+           FROM visitantes_novo 
+           WHERE DATE(hora_entrada) = ?";
+           $params = [$data];
+       } else {
+           $query = "SELECT * FROM visitantes_novo WHERE 1=1";
+           $params = [];
+       }

        if (!empty($search)) {
            $query .= " AND (nome ILIKE ? OR cpf ILIKE ? OR empresa ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($setor)) {
            $query .= " AND setor = ?";
            $params[] = $setor;
        }

+       if (!empty($empresa)) {
+           $query .= " AND empresa ILIKE ?";
+           $params[] = "%$empresa%";
+       }
+
+       if (!empty($responsavel)) {
+           $query .= " AND funcionario_responsavel ILIKE ?";
+           $params[] = "%$responsavel%";
+       }

        if ($status === 'ativo') {
            $query .= " AND hora_entrada IS NOT NULL AND hora_saida IS NULL";
        } elseif ($status === 'saiu') {
            $query .= " AND hora_saida IS NOT NULL";
        }

-       $query .= " ORDER BY created_at DESC";
+       // Consulta para contagem total
+       $countQuery = "SELECT COUNT(*) as total FROM visitantes_novo WHERE " . 
+           str_replace('SELECT nome, setor,', 'SELECT COUNT(*) as total', 
+           str_replace('FROM visitantes_novo', '', $query));
+       
+       $total = $this->db->fetch($countQuery, $params)['total'];
+       $totalPages = ceil($total / $pageSize);
+
+       $query .= " ORDER BY hora_entrada DESC LIMIT ? OFFSET ?";
+       $params[] = $pageSize;
+       $params[] = $offset;

        $visitantes = $this->db->fetchAll($query, $params);
        
+       // Mascarar CPF para relatórios
+       if ($isReportContext) {
+           foreach ($visitantes as &$visitante) {
+               $visitante['cpf'] = $this->maskCpf($visitante['cpf']);
+           }
+       }

        // Get unique sectors for filter
        $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM visitantes_novo WHERE setor IS NOT NULL ORDER BY setor");
+       
+       // Get unique companies for filter  
+       $empresas = $this->db->fetchAll("SELECT DISTINCT empresa FROM visitantes_novo WHERE empresa IS NOT NULL ORDER BY empresa");
+       
+       // Get unique responsibles for filter
+       $responsaveis = $this->db->fetchAll("SELECT DISTINCT funcionario_responsavel FROM visitantes_novo WHERE funcionario_responsavel IS NOT NULL ORDER BY funcionario_responsavel");
+       
+       // Pagination data
+       $pagination = [
+           'current' => $page,
+           'total' => $totalPages,
+           'pageSize' => $pageSize,
+           'totalRecords' => $total
+       ];

        include $this->getViewPath('list.php');
    }
+   
+   /**
+    * Mascara CPF baseado em permissões do usuário
+    */
+   private function maskCpf($cpf) {
+       if (empty($cpf)) {
+           return '';
+       }
+       
+       // TODO: Implementar verificação de permissão RBAC
+       // Por enquanto, sempre mascarar
+       return '***.***.***-**';
+   }
```

#### B. Adicionar Rota Específica para Relatórios

**Arquivo:** `public/index.php`

```diff
+       case 'reports/visitantes':
+           require_once '../src/controllers/VisitantesNovoController.php';
+           $controller = new VisitantesNovoController();
+           $controller->index();
+           break;
```

#### C. Criar Índices de Performance

**SQL a ser executado:**

```sql
-- Índice para filtro por data de entrada
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_entrada_data 
ON visitantes_novo (DATE(hora_entrada));

-- Índice para filtro por setor
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_setor 
ON visitantes_novo (setor);

-- Índice para filtro por empresa
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_empresa 
ON visitantes_novo (empresa);

-- Índice para filtro por funcionário responsável
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_responsavel 
ON visitantes_novo (funcionario_responsavel);

-- Índice combinado para ordenação otimizada
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_entrada_ordenacao 
ON visitantes_novo (hora_entrada DESC);
```

### 2. FRONT-END

#### A. Reformular View de Relatórios

**Arquivo:** `views/reports/visitantes/list.php`

```diff
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- ... navbar existente ... -->
        </nav>
        
        <!-- Main Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <img src="/logo.jpg" alt="Renner Logo" class="brand-image img-circle elevation-3" style="width: 40px; height: 40px; object-fit: contain;">
                <span class="brand-text font-weight-light">Controle Acesso</span>
            </a>
            
            <div class="sidebar">
                <nav class="mt-2">
-                   <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
-                       <li class="nav-item">
-                           <a href="/dashboard" class="nav-link">
-                               <i class="nav-icon fas fa-tachometer-alt"></i>
-                               <p>Dashboard</p>
-                           </a>
-                       </li>
-                       <li class="nav-item">
-                           <a href="/profissionais-renner" class="nav-link">
-                               <i class="nav-icon fas fa-user-tie"></i>
-                               <p>Profissionais Renner</p>
-                           </a>
-                       </li>
-                       <li class="nav-item">
-                           <a href="/visitantes" class="nav-link active">
-                               <i class="nav-icon fas fa-users"></i>
-                               <p>Visitantes</p>
-                           </a>
-                       </li>
-                       <li class="nav-item">
-                           <a href="/prestadores-servico" class="nav-link">
-                               <i class="nav-icon fas fa-tools"></i>
-                               <p>Prestador de Serviços</p>
-                           </a>
-                       </li>
-                   </ul>
+                   <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
+                       <li class="nav-item">
+                           <a href="/dashboard" class="nav-link">
+                               <i class="nav-icon fas fa-tachometer-alt"></i>
+                               <p>Dashboard</p>
+                           </a>
+                       </li>
+                       <li class="nav-item">
+                           <a href="/visitantes" class="nav-link">
+                               <i class="nav-icon fas fa-users"></i>
+                               <p>Visitantes</p>
+                           </a>
+                       </li>
+                       <li class="nav-item">
+                           <a href="/prestadores-servico" class="nav-link">
+                               <i class="nav-icon fas fa-tools"></i>
+                               <p>Prestador de Serviços</p>
+                           </a>
+                       </li>
+                       <li class="nav-item has-treeview menu-open">
+                           <a href="#" class="nav-link">
+                               <i class="nav-icon fas fa-chart-bar"></i>
+                               <p>
+                                   Relatórios
+                                   <i class="fas fa-angle-left right"></i>
+                               </p>
+                           </a>
+                           <ul class="nav nav-treeview">
+                               <li class="nav-item">
+                                   <a href="/reports/profissionais-renner" class="nav-link">
+                                       <i class="nav-icon fas fa-user-tie"></i>
+                                       <p>Profissionais Renner</p>
+                                   </a>
+                               </li>
+                               <li class="nav-item">
+                                   <a href="/reports/visitantes" class="nav-link active">
+                                       <i class="nav-icon fas fa-users"></i>
+                                       <p>Visitantes</p>
+                                   </a>
+                               </li>
+                               <li class="nav-item">
+                                   <a href="/reports/prestadores-servico" class="nav-link">
+                                       <i class="nav-icon fas fa-tools"></i>
+                                       <p>Prestador de Serviços</p>
+                                   </a>
+                               </li>
+                           </ul>
+                       </li>
+                   </ul>
                </nav>
            </div>
        </aside>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
-                           <h1 class="m-0">Visitantes</h1>
+                           <h1 class="m-0">Relatório de Entradas - Visitantes</h1>
+                           <p class="text-muted">Visualização de registros de entrada por data</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
-                   <?php if (isset($_GET['success'])): ?>
-                       <div class="alert alert-success alert-dismissible">
-                           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
-                           Visitante cadastrado com sucesso!
-                       </div>
-                   <?php endif; ?>
-                   
-                   <?php if (isset($_GET['updated'])): ?>
-                       <div class="alert alert-info alert-dismissible">
-                           <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
-                           Visitante atualizado com sucesso!
-                       </div>
-                   <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
-                               <div class="col-md-6">
-                                   <h3 class="card-title">Lista de Visitantes</h3>
-                               </div>
-                               <div class="col-md-6 text-right">
-                                   <a href="/visitantes?action=new" class="btn btn-primary">
-                                       <i class="fas fa-plus"></i> Novo Visitante
-                                   </a>
+                               <div class="col-md-12">
+                                   <h3 class="card-title"><i class="fas fa-chart-line"></i> Relatório de Entradas - Visitantes</h3>
+                                   <p class="text-muted mt-1">Visualização de registros de entrada por data</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <div class="row">
-                                   <div class="col-md-3">
-                                       <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou empresa" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
+                                   <div class="col-md-2">
+                                       <label class="form-label">Data:</label>
+                                       <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($_GET['data'] ?? date('Y-m-d')) ?>">
                                    </div>
                                    <div class="col-md-2">
+                                       <label class="form-label">Setor:</label>
                                        <select name="setor" class="form-control">
                                            <option value="">Todos os setores</option>
                                            <?php foreach ($setores as $s): ?>
                                                <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['setor']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
+                                       <label class="form-label">Status:</label>
                                        <select name="status" class="form-control">
-                                           <option value="">Todos os status</option>
-                                           <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo (na empresa)</option>
-                                           <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Saiu</option>
+                                           <option value="">Todos</option>
+                                           <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Em aberto</option>
+                                           <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Finalizado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
+                                       <label class="form-label">Empresa:</label>
+                                       <select name="empresa" class="form-control">
+                                           <option value="">Todas as empresas</option>
+                                           <?php foreach ($empresas as $e): ?>
+                                               <option value="<?= htmlspecialchars($e['empresa']) ?>" <?= ($_GET['empresa'] ?? '') === $e['empresa'] ? 'selected' : '' ?>>
+                                                   <?= htmlspecialchars($e['empresa']) ?>
+                                               </option>
+                                           <?php endforeach; ?>
+                                       </select>
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">Responsável:</label>
+                                       <select name="responsavel" class="form-control">
+                                           <option value="">Todos</option>
+                                           <?php foreach ($responsaveis as $r): ?>
+                                               <option value="<?= htmlspecialchars($r['funcionario_responsavel']) ?>" <?= ($_GET['responsavel'] ?? '') === $r['funcionario_responsavel'] ? 'selected' : '' ?>>
+                                                   <?= htmlspecialchars($r['funcionario_responsavel']) ?>
+                                               </option>
+                                           <?php endforeach; ?>
+                                       </select>
+                                   </div>
+                                   <div class="col-md-2">
+                                       <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-secondary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
+                           <!-- Informações do Relatório -->
+                           <div class="row mb-3">
+                               <div class="col-md-6">
+                                   <p class="text-muted mb-0">
+                                       <i class="fas fa-calendar"></i> Data: <?= date('d/m/Y', strtotime($_GET['data'] ?? date('Y-m-d'))) ?> |
+                                       <i class="fas fa-list"></i> Total: <?= $pagination['totalRecords'] ?> registros
+                                   </p>
+                               </div>
+                               <div class="col-md-6 text-right">
+                                   <p class="text-muted mb-0">
+                                       Página <?= $pagination['current'] ?> de <?= $pagination['total'] ?>
+                                   </p>
+                               </div>
+                           </div>
+                           
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
-                                           <th>Nome</th>
-                                           <th>CPF</th>
-                                           <th>Empresa</th>
+                                           <th>Nome Completo</th>
+                                           <th>Setor</th>
+                                           <th>Placa / A pé</th>
+                                           <th>Empresa</th>
                                            <th>Funcionário Responsável</th>
-                                           <th>Setor</th>
-                                           <th>Hora Entrada</th>
-                                           <th>Hora Saída</th>
-                                           <th>Status</th>
-                                           <th>Ações</th>
+                                           <th>CPF</th>
+                                           <th>Data/Hora Entrada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($visitantes)): ?>
                                            <tr>
-                                               <td colspan="9" class="text-center">Nenhum visitante encontrado</td>
+                                               <td colspan="7" class="text-center text-muted">
+                                                   <i class="fas fa-search"></i> Nenhum registro encontrado para os filtros selecionados
+                                               </td>
                                            </tr>
                                        <?php else: ?>
-                                           <?php foreach ($visitantes as $visitante): ?>
-                                               <tr>
-                                                   <td><?= htmlspecialchars($visitante['nome']) ?></td>
-                                                   <td><?= htmlspecialchars($visitante['cpf']) ?></td>
-                                                   <td><?= htmlspecialchars($visitante['empresa']) ?></td>
-                                                   <td><?= htmlspecialchars($visitante['funcionario_responsavel']) ?></td>
-                                                   <td><?= htmlspecialchars($visitante['setor']) ?></td>
-                                                   <td><?= $visitante['hora_entrada'] ? date('d/m/Y H:i', strtotime($visitante['hora_entrada'])) : '-' ?></td>
-                                                   <td><?= $visitante['hora_saida'] ? date('d/m/Y H:i', strtotime($visitante['hora_saida'])) : '-' ?></td>
-                                                   <td>
-                                                       <?php if ($visitante['hora_saida']): ?>
-                                                           <span class="badge bg-secondary">Saiu</span>
-                                                       <?php elseif ($visitante['hora_entrada']): ?>
-                                                           <span class="badge bg-success">Ativo</span>
-                                                       <?php else: ?>
-                                                           <span class="badge bg-warning">Cadastrado</span>
-                                                       <?php endif; ?>
-                                                   </td>
-                                                   <td>
-                                                       <a href="/visitantes?action=edit&id=<?= $visitante['id'] ?>" class="btn btn-sm btn-warning">
-                                                           <i class="fas fa-edit"></i>
-                                                       </a>
-                                                       <button class="btn btn-sm btn-danger" onclick="deleteVisitante(<?= $visitante['id'] ?>)">
-                                                           <i class="fas fa-trash"></i>
-                                                       </button>
-                                                   </td>
-                                               </tr>
-                                           <?php endforeach; ?>
+                                           <?php foreach ($visitantes as $v): ?>
+                                               <tr>
+                                                   <td><?= htmlspecialchars($v['nome']) ?></td>
+                                                   <td><?= htmlspecialchars($v['setor'] ?? '-') ?></td>
+                                                   <td><?= htmlspecialchars($v['placa_ou_ape']) ?></td>
+                                                   <td><?= htmlspecialchars($v['empresa'] ?? '-') ?></td>
+                                                   <td><?= htmlspecialchars($v['funcionario_responsavel'] ?? '-') ?></td>
+                                                   <td><?= htmlspecialchars($v['cpf']) ?></td>
+                                                   <td><?= $v['hora_entrada'] ? date('d/m/Y H:i', strtotime($v['hora_entrada'])) : '-' ?></td>
+                                               </tr>
+                                           <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
+                           
+                           <!-- Paginação -->
+                           <?php if ($pagination['total'] > 1): ?>
+                               <div class="row mt-3">
+                                   <div class="col-md-12">
+                                       <nav aria-label="Paginação">
+                                           <ul class="pagination justify-content-center">
+                                               <!-- Página anterior -->
+                                               <?php if ($pagination['current'] > 1): ?>
+                                                   <li class="page-item">
+                                                       <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])) ?>">
+                                                           <i class="fas fa-chevron-left"></i> Anterior
+                                                       </a>
+                                                   </li>
+                                               <?php else: ?>
+                                                   <li class="page-item disabled">
+                                                       <span class="page-link"><i class="fas fa-chevron-left"></i> Anterior</span>
+                                                   </li>
+                                               <?php endif; ?>
+                                               
+                                               <!-- Páginas numeradas -->
+                                               <?php 
+                                               $start = max(1, $pagination['current'] - 2);
+                                               $end = min($pagination['total'], $pagination['current'] + 2);
+                                               ?>
+                                               
+                                               <?php for ($i = $start; $i <= $end; $i++): ?>
+                                                   <?php if ($i == $pagination['current']): ?>
+                                                       <li class="page-item active">
+                                                           <span class="page-link"><?= $i ?></span>
+                                                       </li>
+                                                   <?php else: ?>
+                                                       <li class="page-item">
+                                                           <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
+                                                       </li>
+                                                   <?php endif; ?>
+                                               <?php endfor; ?>
+                                               
+                                               <!-- Próxima página -->
+                                               <?php if ($pagination['current'] < $pagination['total']): ?>
+                                                   <li class="page-item">
+                                                       <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])) ?>">
+                                                           Próxima <i class="fas fa-chevron-right"></i>
+                                                       </a>
+                                                   </li>
+                                               <?php else: ?>
+                                                   <li class="page-item disabled">
+                                                       <span class="page-link">Próxima <i class="fas fa-chevron-right"></i></span>
+                                                   </li>
+                                               <?php endif; ?>
+                                           </ul>
+                                       </nav>
+                                   </div>
+                               </div>
+                           <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
```

### 3. COMANDOS PLANEJADOS

```bash
# 1. Criar índices de performance
psql $DATABASE_URL -c "
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_entrada_data ON visitantes_novo (DATE(hora_entrada));
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_setor ON visitantes_novo (setor);
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_empresa ON visitantes_novo (empresa);
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_responsavel ON visitantes_novo (funcionario_responsavel);
CREATE INDEX IF NOT EXISTS idx_visitantes_novo_entrada_ordenacao ON visitantes_novo (hora_entrada DESC);
"

# 2. Testar queries de performance
echo "Testando performance das consultas..."

# 3. Validar interface com dados reais
echo "Validando interface..."
```

### 4. CRITÉRIOS DE ACEITE

**Funcionalidades:**
- [ ] Filtro por data única (padrão: hoje) ✅
- [ ] Filtros por setor, status, empresa, responsável ✅
- [ ] Colunas: Nome, Setor, Placa/"A pé", Empresa, Responsável, CPF, Data/Hora ✅
- [ ] Paginação (20 por página) ✅
- [ ] Remoção do botão "Cadastrar Visitante" ✅
- [ ] CPF mascarado para segurança ✅

**Performance:**
- [ ] Consulta por data < 300ms ⏱️
- [ ] Índices aplicados corretamente ⚡

**UX:**
- [ ] Interface moderna e responsiva 🎨
- [ ] Menu hierárquico atualizado 📋
- [ ] Estado vazio com mensagem amigável 💬

### 5. TESTES MANUAIS

**Checklist:**
1. **Filtros:**
   - [ ] Data padrão (hoje) carrega registros
   - [ ] Filtro por setor funciona
   - [ ] Filtro por status funciona
   - [ ] Filtro por empresa funciona
   - [ ] Filtro por responsável funciona
   - [ ] Combinação de filtros funciona

2. **Interface:**
   - [ ] Paginação funciona corretamente
   - [ ] Tabela responsiva em mobile
   - [ ] CPF sempre mascarado
   - [ ] Placa vazia exibe "A pé"
   - [ ] Menu lateral hierárquico correto

3. **Performance:**
   - [ ] Tempo de resposta < 300ms
   - [ ] Sem erros no console
   - [ ] Sem queries N+1

---

## 🚨 IMPORTANTE

**Esta é apenas a PROPOSTA (Fase B).** 

**Não aplicarei nenhuma alteração até receber sua autorização explícita.**

**Para executar, responda: "executa"**  
**Para cancelar, responda: "não"**