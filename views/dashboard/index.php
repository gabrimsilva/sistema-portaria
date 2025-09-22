<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Controle de Acesso</title>
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
                <img src="https://via.placeholder.com/33x33" alt="Logo" class="brand-image img-circle elevation-3">
                <span class="brand-text font-weight-light">Controle Acesso</span>
            </a>
            
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link active">
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
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= $stats['visitors_today'] ?></h3>
                                    <p>Visitantes Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?= $stats['employees_active'] ?></h3>
                                    <p>Funcionários Ativos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?= $stats['entries_today'] ?></h3>
                                    <p>Entradas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?= $stats['exits_today'] ?></h3>
                                    <p>Saídas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Access -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Acessos Recentes</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-hover text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Nome</th>
                                                <th>Data/Hora</th>
                                                <th>Categoria</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentAccess as $access): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-<?= $access['tipo'] === 'entrada' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($access['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($access['funcionario_nome'] ?? $access['visitante_nome']) ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($access['data_hora'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $access['funcionario_id'] ? 'primary' : 'info' ?>">
                                                        <?= $access['funcionario_id'] ? 'Funcionário' : 'Visitante' ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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
</body>
</html>