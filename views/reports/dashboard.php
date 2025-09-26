<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Relatórios e Análises</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Date Range Filter -->
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="start_date">Data Inicial</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date">Data Final</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="btn-group">
                                        <a href="/reports?action=visitors&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-info">
                                            <i class="fas fa-users"></i> Relatório Visitantes
                                        </a>
                                        <a href="/reports?action=employees&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-success">
                                            <i class="fas fa-id-badge"></i> Relatório Funcionários
                                        </a>
                                        <a href="/reports?action=access&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-warning">
                                            <i class="fas fa-door-open"></i> Relatório Acessos
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= $stats['total_visitors'] ?></h3>
                                    <p>Total Visitantes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?= $stats['total_employees'] ?></h3>
                                    <p>Total Funcionários</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?= $stats['total_accesses'] ?></h3>
                                    <p>Total Acessos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?= $stats['entries_today'] ?></h3>
                                    <p>Entradas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Daily Access Chart -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Acessos Diários</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hourly Access Chart -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Acessos por Horário</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="hourlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Visitors -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top 10 Visitantes por Frequência</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Posição</th>
                                                    <th>Nome</th>
                                                    <th>Empresa</th>
                                                    <th>Total de Acessos</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($topVisitors)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Nenhum dado encontrado</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($topVisitors as $index => $visitor): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($index === 0): ?>
                                                                <i class="fas fa-trophy text-warning"></i> <?= $index + 1 ?>º
                                                            <?php elseif ($index === 1): ?>
                                                                <i class="fas fa-medal text-secondary"></i> <?= $index + 1 ?>º
                                                            <?php elseif ($index === 2): ?>
                                                                <i class="fas fa-award text-danger"></i> <?= $index + 1 ?>º
                                                            <?php else: ?>
                                                                <?= $index + 1 ?>º
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($visitor['nome']) ?></td>
                                                        <td><?= htmlspecialchars($visitor['empresa']) ?></td>
                                                        <td>
                                                            <span class="badge badge-primary"><?= $visitor['access_count'] ?></span>
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
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    // Daily Access Chart
    const dailyData = <?= json_encode($dailyAccess) ?>;
    const dailyLabels = dailyData.map(item => new Date(item.date).toLocaleDateString('pt-BR'));
    const entriesData = dailyData.map(item => parseInt(item.entries));
    const exitsData = dailyData.map(item => parseInt(item.exits));
    
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Entradas',
                data: entriesData,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Saídas',
                data: exitsData,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Hourly Access Chart
    const hourlyData = <?= json_encode($hourlyAccess) ?>;
    const hourlyLabels = [];
    const hourlyValues = [];
    
    // Fill all 24 hours
    for (let i = 0; i < 24; i++) {
        hourlyLabels.push(i + ':00');
        const found = hourlyData.find(item => parseInt(item.hour) === i);
        hourlyValues.push(found ? parseInt(found.total) : 0);
    }
    
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'doughnut',
        data: {
            labels: hourlyLabels,
            datasets: [{
                data: hourlyValues,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>