<?php
// Verificar se as variáveis estão definidas para evitar erros LSP
$setores = $setores ?? [];
$paginationData = $paginationData ?? null;
$visitantes = $visitantes ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Visitantes - Sistema de Controle de Acesso</title>
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
                                    <a href="/reports/visitantes" class="nav-link active">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Visitantes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/prestadores-servico" class="nav-link">
                                        <i class="nav-icon fas fa-tools"></i>
                                        <p>Prestador de Serviços</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php if ($_SESSION['user_profile'] === 'administrador' || $_SESSION['user_profile'] === 'seguranca'): ?>
                        <li class="nav-item">
                            <a href="/config" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Configurações</p>
                            </a>
                        </li>
                        <?php endif; ?>
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
                            <h1 class="m-0">Visitantes</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Relatório de Entradas - Visitantes</h3>
                                    <p class="text-muted mt-1">Visualização de registros de entrada de visitantes</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros para Relatório -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <form method="GET" class="row align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label">Data de Entrada:</label>
                                                <input type="date" name="data_entrada" class="form-control" value="<?= htmlspecialchars($_GET['data_entrada'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Empresa:</label>
                                                <input type="text" name="empresa" class="form-control" placeholder="Nome da empresa" value="<?= htmlspecialchars($_GET['empresa'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Funcionário Responsável:</label>
                                                <input type="text" name="funcionario_responsavel" class="form-control" placeholder="Nome do responsável" value="<?= htmlspecialchars($_GET['funcionario_responsavel'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Setor:</label>
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
                                                <label class="form-label">Buscar:</label>
                                                <input type="text" name="search" class="form-control" placeholder="Nome ou CPF" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-filter"></i> Filtrar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informações do Relatório -->
                            <?php if (isset($paginationData)): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-list"></i> Total: <?= $paginationData['total_records'] ?> registros
                                        <?php if (!empty($_GET['data_entrada'])): ?>
                                            | <i class="fas fa-calendar"></i> Data: <?= date('d/m/Y', strtotime($_GET['data_entrada'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <p class="text-muted mb-0">
                                        Página <?= $paginationData['current_page'] ?> de <?= $paginationData['total_pages'] ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="25%">Nome</th>
                                            <th width="12%">CPF</th>
                                            <th width="18%">Empresa</th>
                                            <th width="15%">Funcionário Responsável</th>
                                            <th width="12%">Setor</th>
                                            <th width="10%">Placa/Veículo</th>
                                            <th width="8%">Data/Hora Entrada</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($visitantes)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-info-circle text-muted"></i>
                                                    Nenhum registro encontrado
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($visitantes as $visitante): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($visitante['nome']) ?></strong>
                                                    <?php if (!empty($visitante['hora_saida_formatted'])): ?>
                                                        <span class="badge bg-secondary ms-2">Saiu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success ms-2">Na empresa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="text-muted"><?= htmlspecialchars($visitante['cpf_masked'] ?? '') ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($visitante['empresa'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($visitante['funcionario_responsavel'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($visitante['setor'] ?? '') ?></td>
                                                <td>
                                                    <?php if ($visitante['placa_display'] === 'A pé'): ?>
                                                        <span class="text-muted"><i class="fas fa-walking"></i> A pé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($visitante['placa_display']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($visitante['data_entrada']) && !empty($visitante['hora_entrada_formatted'])): ?>
                                                        <?= $visitante['data_entrada'] ?><br>
                                                        <small class="text-muted"><?= $visitante['hora_entrada_formatted'] ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if (isset($paginationData) && $paginationData['total_pages'] > 1): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <nav aria-label="Paginação do relatório">
                                        <ul class="pagination justify-content-center">
                                            <?php 
                                            $currentPage = $paginationData['current_page'];
                                            $totalPages = $paginationData['total_pages'];
                                            $queryParams = $_GET;
                                            ?>
                                            
                                            <!-- Primeira página -->
                                            <?php if ($currentPage > 1): ?>
                                                <?php 
                                                $queryParams['page'] = 1;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-double-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Página anterior -->
                                            <?php if ($paginationData['has_previous']): ?>
                                                <?php 
                                                $queryParams['page'] = $paginationData['previous_page'];
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Páginas numéricas -->
                                            <?php 
                                            $start = max(1, $currentPage - 2);
                                            $end = min($totalPages, $currentPage + 2);
                                            for ($i = $start; $i <= $end; $i++): 
                                            ?>
                                                <?php 
                                                $queryParams['page'] = $i;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?= $queryString ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Próxima página -->
                                            <?php if ($paginationData['has_next']): ?>
                                                <?php 
                                                $queryParams['page'] = $paginationData['next_page'];
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Última página -->
                                            <?php if ($currentPage < $totalPages): ?>
                                                <?php 
                                                $queryParams['page'] = $totalPages;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-double-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>