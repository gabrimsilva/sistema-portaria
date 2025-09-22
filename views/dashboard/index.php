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
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile'] ?? 'Porteiro') ?></span>
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
                            <a href="/profissionais-renner" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Profissionais Renner</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/visitantes" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Visitantes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/prestadores-servico" class="nav-link">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>Prestador de Serviços</p>
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
                                    <h3><?= $stats['visitors_today'] ?? 0 ?></h3>
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
                                    <h3><?= $stats['employees_active'] ?? 0 ?></h3>
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
                                    <h3><?= $stats['entries_today'] ?? 0 ?></h3>
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
                                    <h3><?= $stats['exits_today'] ?? 0 ?></h3>
                                    <p>Saídas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pessoas Atualmente na Empresa -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-building"></i> Pessoas Atualmente na Empresa
                                        <span class="badge badge-info ml-2"><?= count($pessoasNaEmpresa ?? []) ?></span>
                                    </h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <?php if (!empty($pessoasNaEmpresa)): ?>
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Tipo</th>
                                                    <th>CPF</th>
                                                    <th>Empresa</th>
                                                    <th>Setor</th>
                                                    <th>Hora de Entrada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pessoasNaEmpresa as $pessoa): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($pessoa['nome']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $badgeClass = '';
                                                        switch ($pessoa['tipo']) {
                                                            case 'Visitante':
                                                                $badgeClass = 'success';
                                                                break;
                                                            case 'Prestador':
                                                                $badgeClass = 'warning';
                                                                break;
                                                            case 'Profissional Renner':
                                                                $badgeClass = 'primary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $badgeClass ?>">
                                                            <?= htmlspecialchars($pessoa['tipo']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= !empty($pessoa['cpf']) ? htmlspecialchars($pessoa['cpf']) : '-' ?></td>
                                                    <td><?= !empty($pessoa['empresa']) ? htmlspecialchars($pessoa['empresa']) : '-' ?></td>
                                                    <td><?= !empty($pessoa['setor']) ? htmlspecialchars($pessoa['setor']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($pessoa['hora_entrada']): ?>
                                                            <i class="fas fa-clock text-muted"></i>
                                                            <?= date('d/m/Y H:i', strtotime($pessoa['hora_entrada'])) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="text-center p-4">
                                            <i class="fas fa-building text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">Nenhuma pessoa está registrada na empresa no momento.</p>
                                        </div>
                                    <?php endif; ?>
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