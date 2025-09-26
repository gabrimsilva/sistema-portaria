<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Acessos - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navigation -->
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
        
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <img src="https://via.placeholder.com/33x33" alt="Logo" class="brand-image img-circle elevation-3">
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
                        <li class="nav-item">
                            <a href="/visitors" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Visitantes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/employees" class="nav-link">
                                <i class="nav-icon fas fa-id-badge"></i>
                                <p>Funcionários</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/access" class="nav-link">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p>Controle de Acesso</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/reports" class="nav-link active">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Relatórios</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Relatório de Acessos</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-right">
                                <a href="/reports?action=export&report_type=access&format=csv&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-download"></i> Exportar CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Filtros</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET">
                                <input type="hidden" name="action" value="access">
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
                                        <label for="type">Tipo</label>
                                        <select name="type" class="form-control">
                                            <option value="">Todos</option>
                                            <option value="entrada" <?= $type === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                                            <option value="saida" <?= $type === 'saida' ? 'selected' : '' ?>>Saída</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="person_type">Categoria</label>
                                        <select name="person_type" class="form-control">
                                            <option value="">Todos</option>
                                            <option value="visitor" <?= $person_type === 'visitor' ? 'selected' : '' ?>>Visitantes</option>
                                            <option value="employee" <?= $person_type === 'employee' ? 'selected' : '' ?>>Funcionários</option>
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
                            <h3 class="card-title">Acessos (<?= count($accesses) ?> encontrados)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Tipo</th>
                                            <th>Nome</th>
                                            <th>Categoria</th>
                                            <th>Empresa/Cargo</th>
                                            <th>Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accesses)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhum acesso encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accesses as $access): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i:s', strtotime($access['data_hora'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $access['tipo'] === 'entrada' ? 'success' : 'warning' ?>">
                                                        <i class="fas fa-<?= $access['tipo'] === 'entrada' ? 'sign-in-alt' : 'sign-out-alt' ?>"></i>
                                                        <?= ucfirst($access['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($access['funcionario_nome'] ?? $access['visitante_nome']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $access['funcionario_id'] ? 'primary' : 'info' ?>">
                                                        <i class="fas fa-<?= $access['funcionario_id'] ? 'id-badge' : 'users' ?>"></i>
                                                        <?= $access['funcionario_id'] ? 'Funcionário' : 'Visitante' ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($access['cargo'] ?? $access['empresa'] ?? 'N/A') ?></td>
                                                <td><small><?= htmlspecialchars($access['usuario_nome']) ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>