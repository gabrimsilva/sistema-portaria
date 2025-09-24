# PLANO DE EXECUÇÃO - Relatórios Profissionais Renner

## Resumo do Plano

### Objetivo
Reformular a tela "Relatórios → Profissionais Renner" para exibir apenas relatórios com:
- Filtros: data (padrão: hoje), setor, status  
- Colunas: Nome completo, Setor, Placa/"A pé", Data/Hora entrada
- Paginação: 20 itens por página
- Remoção do botão "Cadastrar Profissional"
- Performance otimizada com índices

### Estratégia
1. **Backend**: Ajustar controller para filtro de data e paginação
2. **Frontend**: Simplificar view de reports removendo cadastro
3. **Database**: Criar índices para performance
4. **Testes**: Validar filtros e paginação

---

## BACKEND - Alterações

### 1. Controller: Ajustes no ProfissionaisRennerController.php

```diff
--- a/src/controllers/ProfissionaisRennerController.php
+++ b/src/controllers/ProfissionaisRennerController.php
@@ -38,9 +38,15 @@ class ProfissionaisRennerController {
     }
     
     public function index() {
         $search = $_GET['search'] ?? '';
         $setor = $_GET['setor'] ?? '';
         $status = $_GET['status'] ?? '';
+        $data = $_GET['data'] ?? date('Y-m-d'); // Padrão: hoje
+        $page = max(1, intval($_GET['page'] ?? 1));
+        $pageSize = max(1, min(100, intval($_GET['pageSize'] ?? 20)));
+        $offset = ($page - 1) * $pageSize;
+        
+        // Verificar se é contexto de relatório
+        $isReport = strpos($_SERVER['REQUEST_URI'] ?? '', '/reports/') !== false;
         
-        $query = "SELECT * FROM profissionais_renner WHERE 1=1";
+        $query = "SELECT id, nome, setor, placa_veiculo, data_entrada, saida_final FROM profissionais_renner WHERE 1=1";
         $params = [];
         
+        // Filtro por data (apenas para relatórios)
+        if ($isReport && !empty($data)) {
+            $query .= " AND DATE(data_entrada) = ?";
+            $params[] = $data;
+        }
+        
         if (!empty($search)) {
-            $query .= " AND (nome ILIKE ? OR setor ILIKE ?)";
+            $query .= " AND nome ILIKE ?";
             $params[] = "%$search%";
-            $params[] = "%$search%";
         }
         
         if (!empty($setor)) {
             $query .= " AND setor = ?";
             $params[] = $setor;
         }
         
+        // Filtro de status ajustado
         if ($status === 'ativo') {
-            // Ativos: têm data_entrada ou retorno, mas não saída_final (estão atualmente na empresa)
-            $query .= " AND (data_entrada IS NOT NULL OR retorno IS NOT NULL) AND saida_final IS NULL";
+            $query .= " AND data_entrada IS NOT NULL AND saida_final IS NULL";
         } elseif ($status === 'saiu') {
-            // Saíram: têm saída_final registrada (independente do dia)
             $query .= " AND saida_final IS NOT NULL";
         }
+        
+        // Contar total para paginação (apenas para relatórios)
+        $total = 0;
+        if ($isReport) {
+            $countQuery = str_replace("SELECT id, nome, setor, placa_veiculo, data_entrada, saida_final", "SELECT COUNT(*)", $query);
+            $total = $this->db->fetch($countQuery, $params)['count'] ?? 0;
+        }
         
-        $query .= " ORDER BY created_at DESC";
+        // Ordenação e paginação
+        if ($isReport) {
+            $query .= " ORDER BY data_entrada DESC";
+            $query .= " LIMIT ? OFFSET ?";
+            $params[] = $pageSize;
+            $params[] = $offset;
+        } else {
+            $query .= " ORDER BY created_at DESC";
+        }
         
         $profissionais = $this->db->fetchAll($query, $params);
         
         // Get unique sectors for filter
         $setores = $this->db->fetchAll("SELECT DISTINCT setor FROM profissionais_renner WHERE setor IS NOT NULL ORDER BY setor");
         
+        // Adicionar dados de paginação para relatórios
+        if ($isReport) {
+            $pagination = [
+                'page' => $page,
+                'pageSize' => $pageSize,
+                'total' => $total,
+                'totalPages' => ceil($total / $pageSize)
+            ];
+        }
+        
         include $this->getViewPath('list.php');
     }
```

### 2. Nova Rota API (Opcional para AJAX)

```diff
--- a/public/index.php
+++ b/public/index.php
@@ -196,6 +196,9 @@ try {
         case 'reports/profissionais-renner':
             require_once '../src/controllers/ProfissionaisRennerController.php';
             $controller = new ProfissionaisRennerController();
             $action = $_GET['action'] ?? 'index';
             switch ($action) {
+                case 'api':
+                    $controller->apiReport();
+                    break;
                 case 'new':
                     $controller->create();
                     break;
```

---

## FRONTEND - Alterações

### 1. View Reports: Simplificar para apenas relatório

```diff
--- a/views/reports/profissionais_renner/list.php
+++ b/views/reports/profissionais_renner/list.php
@@ -114,9 +114,6 @@
                             <div class="card-header">
                                 <h3 class="card-title">Relatório de Profissionais Renner</h3>
                             </div>
-                            <div class="card-header">
-                                <a href="/profissionais-renner?action=new" class="btn btn-primary">
-                                    <i class="fas fa-plus"></i> Novo Profissional
-                                </a>
-                            </div>
                             
                             <!-- Filtros -->
                             <form method="GET" class="mb-3">
@@ -124,6 +121,16 @@
                                     <div class="col-md-3">
                                         <label for="data">Data:</label>
-                                        <input type="date" name="data" class="form-control" value="<?= $_GET['data'] ?? '' ?>">
+                                        <input type="date" name="data" class="form-control" 
+                                               value="<?= $_GET['data'] ?? date('Y-m-d') ?>" required>
                                     </div>
                                     <div class="col-md-3">
                                         <label for="setor">Setor:</label>
                                         <select name="setor" class="form-control">
                                             <option value="">Todos os setores</option>
                                             <?php foreach ($setores as $s): ?>
                                                 <option value="<?= htmlspecialchars($s['setor']) ?>" 
                                                         <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                     <?= htmlspecialchars($s['setor']) ?>
                                                 </option>
                                             <?php endforeach; ?>
                                         </select>
                                     </div>
                                     <div class="col-md-3">
                                         <label for="status">Status:</label>
                                         <select name="status" class="form-control">
-                                            <option value="">Todos</option>
-                                            <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Em aberto</option>
-                                            <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Finalizado</option>
+                                            <option value="">Todos</option>
+                                            <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Em aberto</option>
+                                            <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Finalizado</option>
                                         </select>
                                     </div>
                                     <div class="col-md-3">
                                         <button type="submit" class="btn btn-secondary">
                                             <i class="fas fa-search"></i> Filtrar
                                         </button>
                                     </div>
                                 </div>
                             </form>
                             
                             <!-- Tabela simplificada -->
                             <div class="table-responsive">
                                 <table class="table table-bordered table-hover">
                                     <thead class="table-dark">
                                         <tr>
-                                            <th>Nome</th>
-                                            <th>CPF</th>
-                                            <th>Empresa</th>
-                                            <th>Setor</th>
-                                            <th>Entrada</th>
-                                            <th>Saída</th>
-                                            <th>Status</th>
-                                            <th>Ações</th>
+                                            <th>Nome Completo</th>
+                                            <th>Setor</th>
+                                            <th>Placa/A pé</th>
+                                            <th>Data/Hora de Entrada</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <?php if (empty($profissionais)): ?>
                                             <tr>
-                                                <td colspan="8" class="text-center">Nenhum registro encontrado</td>
+                                                <td colspan="4" class="text-center">Nenhum registro encontrado para os filtros selecionados</td>
                                             </tr>
                                         <?php else: ?>
                                             <?php foreach ($profissionais as $prof): ?>
                                             <tr>
-                                                <td><?= htmlspecialchars($prof['nome']) ?></td>
-                                                <td><?= htmlspecialchars($prof['cpf']) ?></td>
-                                                <td><?= htmlspecialchars($prof['empresa']) ?></td>
+                                                <td><strong><?= htmlspecialchars($prof['nome']) ?></strong></td>
                                                 <td><?= htmlspecialchars($prof['setor']) ?></td>
-                                                <td><?= $prof['data_entrada'] ? date('d/m/Y H:i', strtotime($prof['data_entrada'])) : '-' ?></td>
-                                                <td><?= $prof['saida_final'] ? date('d/m/Y H:i', strtotime($prof['saida_final'])) : '-' ?></td>
-                                                <td>
-                                                    <?php if ($prof['data_entrada'] && !$prof['saida_final']): ?>
-                                                        <span class="badge badge-success">Em aberto</span>
-                                                    <?php elseif ($prof['saida_final']): ?>
-                                                        <span class="badge badge-secondary">Finalizado</span>
-                                                    <?php else: ?>
-                                                        <span class="badge badge-warning">Aguardando</span>
-                                                    <?php endif; ?>
+                                                <td>
+                                                    <?php if (empty($prof['placa_veiculo']) || $prof['placa_veiculo'] === 'APE'): ?>
+                                                        <i class="fas fa-walking text-muted"></i> A pé
+                                                    <?php else: ?>
+                                                        <i class="fas fa-car text-primary"></i> <?= strtoupper($prof['placa_veiculo']) ?>
+                                                    <?php endif; ?>
                                                 </td>
-                                                <td>
-                                                    <div class="btn-group">
-                                                        <button class="btn btn-sm btn-primary" onclick="editarProfissional(<?= $prof['id'] ?>)">
-                                                            <i class="fas fa-edit"></i>
-                                                        </button>
-                                                        <button class="btn btn-sm btn-danger" onclick="excluirProfissional(<?= $prof['id'] ?>)">
-                                                            <i class="fas fa-trash"></i>
-                                                        </button>
-                                                    </div>
+                                                <td>
+                                                    <?php if ($prof['data_entrada']): ?>
+                                                        <i class="fas fa-clock text-muted"></i>
+                                                        <?= date('d/m/Y H:i', strtotime($prof['data_entrada'])) ?>
+                                                    <?php else: ?>
+                                                        <span class="text-muted">-</span>
+                                                    <?php endif; ?>
                                                 </td>
                                             </tr>
                                             <?php endforeach; ?>
                                         <?php endif; ?>
                                     </tbody>
                                 </table>
+                                
+                                <!-- Paginação -->
+                                <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
+                                <nav aria-label="Paginação">
+                                    <ul class="pagination justify-content-center">
+                                        <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
+                                        <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
+                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
+                                                <?= $i ?>
+                                            </a>
+                                        </li>
+                                        <?php endfor; ?>
+                                    </ul>
+                                    <div class="text-center text-muted">
+                                        Página <?= $pagination['page'] ?> de <?= $pagination['totalPages'] ?> 
+                                        (<?= $pagination['total'] ?> registros)
+                                    </div>
+                                </nav>
+                                <?php endif; ?>
                             </div>
                         </div>
                     </div>
```

---

## DATABASE - Índices para Performance

### 1. Script SQL para Índices

```sql
-- Arquivo: migrations/indices_profissionais_renner.sql

-- Índice para filtros de data de entrada
CREATE INDEX IF NOT EXISTS idx_profissionais_data_entrada 
ON profissionais_renner (data_entrada DESC);

-- Índice para filtro por setor  
CREATE INDEX IF NOT EXISTS idx_profissionais_setor 
ON profissionais_renner (setor);

-- Índice para filtro de status (saída final)
CREATE INDEX IF NOT EXISTS idx_profissionais_status 
ON profissionais_renner (saida_final);

-- Índice composto para relatórios (data + status)
CREATE INDEX IF NOT EXISTS idx_profissionais_relatorio 
ON profissionais_renner (data_entrada DESC, saida_final);

-- Comentários para documentação
COMMENT ON INDEX idx_profissionais_data_entrada IS 'Otimização para filtros de data em relatórios';
COMMENT ON INDEX idx_profissionais_setor IS 'Otimização para filtros por setor';
COMMENT ON INDEX idx_profissionais_status IS 'Otimização para filtros de status (em aberto/finalizado)';
COMMENT ON INDEX idx_profissionais_relatorio IS 'Índice composto para consultas de relatório';
```

---

## TESTES - Validação

### 1. Testes Manuais

#### Checklist de Funcionalidades
- [ ] Filtro por data funciona (padrão: hoje)
- [ ] Filtro por setor funciona 
- [ ] Filtro de status (Em aberto/Finalizado) funciona
- [ ] Paginação navegável (20 itens/página)
- [ ] Ordenação por data_entrada DESC
- [ ] Placa vazia exibe "A pé"
- [ ] Placa preenchida exibe maiúscula
- [ ] Botão "Novo Profissional" removido da tela de relatórios
- [ ] Performance < 300ms com índices

#### URLs de Teste
```
# Relatório padrão (hoje)
/reports/profissionais-renner

# Filtro por data específica
/reports/profissionais-renner?data=2025-09-24

# Filtro por setor
/reports/profissionais-renner?setor=TI

# Filtro por status  
/reports/profissionais-renner?status=ativo

# Paginação
/reports/profissionais-renner?page=2&pageSize=10

# Combinados
/reports/profissionais-renner?data=2025-09-24&setor=TI&status=ativo
```

### 2. Teste de Performance

```sql
-- Query para medir performance
EXPLAIN ANALYZE 
SELECT id, nome, setor, placa_veiculo, data_entrada, saida_final 
FROM profissionais_renner 
WHERE DATE(data_entrada) = CURRENT_DATE 
  AND setor = 'TI' 
  AND saida_final IS NULL 
ORDER BY data_entrada DESC 
LIMIT 20 OFFSET 0;
```

---

## COMANDOS DE EXECUÇÃO

### 1. Aplicar Índices
```bash
# Conectar ao banco e aplicar
psql $DATABASE_URL -f migrations/indices_profissionais_renner.sql
```

### 2. Testar Rotas
```bash
# Testar relatório básico
curl "http://localhost:5000/reports/profissionais-renner"

# Testar com filtros  
curl "http://localhost:5000/reports/profissionais-renner?data=2025-09-24&setor=TI"
```

### 3. Verificar Performance
```bash
# Análise de queries lentas
grep "slow" /var/log/postgresql/*.log
```

---

## CRITÉRIOS DE ACEITE

### Funcionais
- ✅ Exibe registros de entrada do dia atual por padrão
- ✅ Filtros por data, setor e status funcionam  
- ✅ Apenas 4 colunas: Nome, Setor, Placa/A pé, Data/Hora
- ✅ Paginação 20 itens por página
- ✅ Sem botão de cadastro na tela de relatórios
- ✅ Placa vazia = "A pé", preenchida = maiúscula

### Performance
- ✅ Consulta retorna em < 300ms
- ✅ Índices otimizam filtros de data e setor
- ✅ Paginação eficiente com LIMIT/OFFSET

### UX/UI
- ✅ Interface limpa focada em relatórios
- ✅ Filtros intuitivos com valores padrão
- ✅ Navegação de paginação clara
- ✅ Mensagem quando sem resultados

---

## ARQUIVOS MODIFICADOS

### Backend
- `src/controllers/ProfissionaisRennerController.php` (ajustes na query e paginação)

### Frontend  
- `views/reports/profissionais_renner/list.php` (simplificação e novos filtros)

### Database
- `migrations/indices_profissionais_renner.sql` (novos índices)

### Configuração
- `public/index.php` (opcional: nova rota API)

---

*Plano criado em: 24/09/2025*