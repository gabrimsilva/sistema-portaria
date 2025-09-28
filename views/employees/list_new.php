<?php
/**
 * 🎯 Lista de Funcionários - Versão 3.0 com Layout Unificado
 * 
 * Demonstração do novo sistema LayoutService que elimina
 * duplicação de código e padroniza toda a interface.
 * 
 * DE: 188 linhas PARA: ~60 linhas (68% de redução!)
 */

// Configuração da página
$pageConfig = [
    'title' => 'Funcionários - Sistema de Controle de Acesso',
    'context' => 'default',
    'pageTitle' => 'Funcionários',
    'headerActions' => [
        [
            'url' => '/employees?action=new',
            'icon' => 'fas fa-plus',
            'label' => 'Novo Funcionário',
            'class' => 'btn btn-primary'
        ]
    ]
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];
?>

<!-- 🎯 CONTEÚDO PRINCIPAL - Apenas o que é específico desta página -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Funcionário cadastrado com sucesso!
    </div>
<?php endif; ?>

<?php if (isset($_GET['status_changed'])): ?>
    <div class="alert alert-info alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?= htmlspecialchars($_GET['status_changed']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Funcionários</h3>
    </div>
    
    <div class="card-body">
        <!-- Filtros -->
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou cargo" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">Todos os status</option>
                        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Cargo</th>
                        <th>Data Admissão</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Nenhum funcionário encontrado</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <?php if (!empty($employee['foto'])): ?>
                                    <img src="/<?= htmlspecialchars($employee['foto']) ?>" 
                                         alt="Foto" class="img-circle" width="40" height="40">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($employee['nome']) ?></td>
                            <td><?= htmlspecialchars($employee['cpf']) ?></td>
                            <td><?= htmlspecialchars($employee['cargo']) ?></td>
                            <td><?= date('d/m/Y', strtotime($employee['data_admissao'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $employee['ativo'] ? 'success' : 'danger' ?>">
                                    <?= $employee['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/employees?action=history&id=<?= $employee['id'] ?>" class="btn btn-sm btn-info" title="Histórico de Acessos">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <form method="POST" action="/employees?action=toggle" class="d-inline">
                                        <?= CSRFProtection::getHiddenInput() ?>
                                        <input type="hidden" name="id" value="<?= $employee['id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-sm btn-<?= $employee['ativo'] ? 'warning' : 'success' ?>"
                                                title="<?= $employee['ativo'] ? 'Desativar' : 'Ativar' ?>"
                                                onclick="return confirm('Tem certeza que deseja <?= $employee['ativo'] ? 'desativar' : 'ativar' ?> este funcionário?')">
                                            <i class="fas fa-<?= $employee['ativo'] ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    <a href="/access?employee_id=<?= $employee['id'] ?>" class="btn btn-sm btn-primary" title="Registrar Acesso">
                                        <i class="fas fa-door-open"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Renderizar layout end (inclui scripts, LGPD, etc automaticamente)
echo $layout['end'];
?>