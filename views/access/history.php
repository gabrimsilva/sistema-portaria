<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Acessos - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <?= LogoService::renderSimpleLogo('renner', 'sidebar'); ?>
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
                            <a href="/access" class="nav-link active">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p>Controle de Acesso</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/reports" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Relatórios</p>
                            </a>
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
                            <h1 class="m-0">Histórico de Acessos</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="/access">Controle de Acesso</a></li>
                                <li class="breadcrumb-item active">Histórico</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?= $stats['total_entries'] ?></h3>
                                    <p>Total de Entradas</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?= $stats['total_exits'] ?></h3>
                                    <p>Total de Saídas</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= $stats['visitor_entries'] ?></h3>
                                    <p>Entradas Visitantes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?= $stats['employee_entries'] ?></h3>
                                    <p>Entradas Funcionários</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="card-title">Acessos de <?= date('d/m/Y', strtotime($date)) ?></h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/access" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Registrar Acesso
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <input type="hidden" name="action" value="history">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="type" class="form-control">
                                            <option value="">Todos</option>
                                            <option value="entrada" <?= $type === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                                            <option value="saida" <?= $type === 'saida' ? 'selected' : '' ?>>Saída</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="person_type" class="form-control">
                                            <option value="">Visitantes e Funcionários</option>
                                            <option value="visitor" <?= $person_type === 'visitor' ? 'selected' : '' ?>>Apenas Visitantes</option>
                                            <option value="employee" <?= $person_type === 'employee' ? 'selected' : '' ?>>Apenas Funcionários</option>
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
                                            <th>Horário</th>
                                            <th>Tipo</th>
                                            <th>Foto</th>
                                            <th>Nome</th>
                                            <th>Categoria</th>
                                            <th>Empresa/Cargo</th>
                                            <th>Registrado por</th>
                                            <th>Observações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accessLogs)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Nenhum registro encontrado para esta data</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accessLogs as $access): ?>
                                            <tr>
                                                <td><?= date('H:i:s', strtotime($access['data_hora'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $access['tipo'] === 'entrada' ? 'success' : 'warning' ?>">
                                                        <i class="fas fa-<?= $access['tipo'] === 'entrada' ? 'sign-in-alt' : 'sign-out-alt' ?>"></i>
                                                        <?= ucfirst($access['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $foto = $access['funcionario_foto'] ?? $access['visitante_foto'];
                                                    if (!empty($foto)): 
                                                    ?>
                                                        <img src="/<?= htmlspecialchars($foto) ?>" alt="Foto" class="img-circle" width="40" height="40">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($access['funcionario_nome'] ?? $access['visitante_nome']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $access['funcionario_id'] ? 'primary' : 'info' ?>">
                                                        <i class="fas fa-<?= $access['funcionario_id'] ? 'id-badge' : 'users' ?>"></i>
                                                        <?= $access['funcionario_id'] ? 'Funcionário' : 'Visitante' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($access['funcionario_id']): ?>
                                                        <small class="text-muted">Cargo não informado</small>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($access['empresa']) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($access['usuario_nome']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($access['observacoes'])): ?>
                                                        <small><?= htmlspecialchars($access['observacoes']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
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
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>