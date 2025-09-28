<?php
/**
 * 📊 RELATÓRIO DE FUNCIONÁRIOS - REFATORADO COM LAYOUTSERVICE
 * 
 * Página refatorada para usar o sistema unificado de layout,
 * eliminando duplicação de código de navegação.
 */

// Configuração da página
$pageConfig = [
    'title' => 'Relatório de Funcionários - Sistema de Controle de Acesso',
    'context' => 'reports',
    'pageTitle' => 'Relatório de Funcionários',
    'breadcrumbs' => [
        ['name' => 'Início', 'url' => '/dashboard'],
        ['name' => 'Relatórios', 'url' => '/reports'],
        ['name' => 'Funcionários', 'active' => true]
    ],
    'activeMenu' => 'reports'
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];
?>

<!-- 📊 CONTEÚDO ESPECÍFICO DA PÁGINA -->
<div class="container-fluid">
    <!-- Botão de exportação -->
    <div class="row mb-3">
        <div class="col-12 text-right">
            <a href="/reports?action=export&report_type=employees&format=csv&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        </div>
    </div>
    
    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Filtros</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET">
                                <input type="hidden" name="action" value="employees">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="start_date">Data Inicial</label>
                                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date">Data Final</label>
                                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="status">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">Todos</option>
                                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Ativo</option>
                                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inativo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="department">Departamento/Cargo</label>
                                        <select name="department" class="form-control">
                                            <option value="">Todos</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= htmlspecialchars($dept['cargo']) ?>" <?= $department === $dept['cargo'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dept['cargo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <a href="/reports" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Funcionários (<?= count($employees) ?> encontrados)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>CPF</th>
                                            <th>Cargo</th>
                                            <th>Data Admissão</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($employees)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhum funcionário encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?= $employee['id'] ?></td>
                                                <td><?= htmlspecialchars($employee['nome']) ?></td>
                                                <td><?= htmlspecialchars($employee['cpf']) ?></td>
                                                <td><?= htmlspecialchars($employee['cargo']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($employee['data_admissao'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $employee['ativo'] ? 'success' : 'danger' ?>">
                                                        <?= $employee['ativo'] ? 'Ativo' : 'Inativo' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
</div>

<?php
// Renderizar layout end
echo $layout['end'];
?>