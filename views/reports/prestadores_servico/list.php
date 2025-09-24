<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Prestadores de Serviço - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile']) ?></span>
                        <div class="dropdown-divider"></div>
                        <a href="/logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Main Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <img src="/logo.jpg" alt="Renner Logo" class="brand-image img-circle elevation-3" style="width: 40px; height: 40px; object-fit: contain;">
                <span class="brand-text font-weight-light">Controle Acesso</span>
            </a>
            
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item has-treeview menu-open">
                            <a href="#" class="nav-link active">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Relatórios
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/reports/profissionais-renner" class="nav-link">
                                        <i class="nav-icon fas fa-user-tie"></i>
                                        <p>Profissionais Renner</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/visitantes" class="nav-link">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Visitantes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/prestadores-servico" class="nav-link active">
                                        <i class="nav-icon fas fa-tools"></i>
                                        <p>Prestador de Serviços</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Prestadores de Serviço</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            Prestador de serviço cadastrado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            Prestador de serviço atualizado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Relatório - Prestadores de Serviço</h3>
                                    <p class="text-muted mb-0">Relatório de entradas do dia com filtros avançados</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros de Relatório -->
                            <form method="GET" class="mb-3" id="report-filters">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Data</label>
                                        <input type="date" name="data" class="form-control" value="<?= htmlspecialchars($_GET['data'] ?? date('Y-m-d')) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Setor</label>
                                        <select name="setor" class="form-control">
                                            <option value="">Todos</option>
                                            <?php foreach ($setores as $s): ?>
                                                <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['setor']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="todos" <?= ($_GET['status'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                                            <option value="aberto" <?= ($_GET['status'] ?? '') === 'aberto' ? 'selected' : '' ?>>Em aberto</option>
                                            <option value="finalizado" <?= ($_GET['status'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Empresa</label>
                                        <input type="text" name="empresa" class="form-control" placeholder="Nome da empresa" value="<?= htmlspecialchars($_GET['empresa'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Responsável</label>
                                        <select name="responsavel" class="form-control">
                                            <option value="">Todos</option>
                                            <?php foreach ($responsaveis as $r): ?>
                                                <option value="<?= htmlspecialchars($r['funcionario_responsavel']) ?>" <?= ($_GET['responsavel'] ?? '') === $r['funcionario_responsavel'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($r['funcionario_responsavel']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nome Completo</th>
                                            <th>Setor</th>
                                            <th>Placa / A pé</th>
                                            <th>Empresa</th>
                                            <th>Funcionário Responsável</th>
                                            <th>CPF</th>
                                            <th>Data/Hora Entrada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($prestadores)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fas fa-info-circle"></i> Nenhum registro encontrado para os filtros selecionados.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($prestadores as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                                <td><?= htmlspecialchars($p['setor'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['placa_ou_ape']) ?></td>
                                                <td><?= htmlspecialchars($p['empresa'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['funcionario_responsavel'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($p['cpf']) ?></td>
                                                <td><?= $p['entrada_at'] ? date('d/m/Y H:i', strtotime($p['entrada_at'])) : '-' ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                
                                <!-- Paginação -->
                                <?php if ($pagination['total'] > 1): ?>
                                <nav aria-label="Paginação">
                                    <ul class="pagination justify-content-center">
                                        <!-- Primeira página -->
                                        <?php if ($pagination['current'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])) ?>">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <!-- Páginas -->
                                        <?php 
                                        $start = max(1, $pagination['current'] - 2);
                                        $end = min($pagination['total'], $pagination['current'] + 2);
                                        for ($i = $start; $i <= $end; $i++): 
                                        ?>
                                        <li class="page-item <?= $i === $pagination['current'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Última página -->
                                        <?php if ($pagination['current'] < $pagination['total']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])) ?>">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['total']])) ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                
                                <div class="text-center text-muted">
                                    Exibindo <?= count($prestadores) ?> de <?= $pagination['totalItems'] ?> registros 
                                    (Página <?= $pagination['current'] ?> de <?= $pagination['total'] ?>)
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Limpeza ao desmontar a página
        $(window).on('beforeunload', function() {
            // Cancelar requests AJAX pendentes
            if (window.activeRequests) {
                window.activeRequests.forEach(function(xhr) {
                    if (xhr.readyState !== 4) {
                        xhr.abort();
                    }
                });
                window.activeRequests = [];
            }
            
            // Limpar timers
            if (window.reportTimers) {
                window.reportTimers.forEach(clearTimeout);
                window.reportTimers = [];
            }
            
            // Limpar event listeners específicos
            $(document).off('.prestadores-report');
            
            // Remover loading states
            $('.loading').removeClass('loading');
        });
        
        // Namespace para eventos desta tela
        $('#report-filters').on('submit.prestadores-report', function() {
            $(this).find('button[type="submit"]').addClass('loading').prop('disabled', true);
        });
    });
    </script>
</body>
</html>