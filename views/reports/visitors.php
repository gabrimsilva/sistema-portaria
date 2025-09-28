<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Visitantes - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Include navigation (same as other pages) -->
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
        
        <!-- 🎯 NavigationService: Navegação Unificada -->
        <?= NavigationService::renderSidebar() ?>
        
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Relatório de Visitantes</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-right">
                                <a href="/reports?action=export&report_type=visitors&format=csv&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>" 
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
                                <input type="hidden" name="action" value="visitors">
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
                                            <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                            <option value="saiu" <?= $status === 'saiu' ? 'selected' : '' ?>>Saiu</option>
                                            <option value="vencido" <?= $status === 'vencido' ? 'selected' : '' ?>>Vencido</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="company">Empresa</label>
                                        <select name="company" class="form-control">
                                            <option value="">Todas</option>
                                            <?php foreach ($companies as $comp): ?>
                                                <option value="<?= htmlspecialchars($comp['empresa']) ?>" <?= $company === $comp['empresa'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($comp['empresa']) ?>
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
                            <h3 class="card-title">Visitantes (<?= count($visitors) ?> encontrados)</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Empresa</th>
                                            <th>Pessoa Visitada</th>
                                            <th>Status</th>
                                            <th>Data Cadastro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($visitors)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Nenhum visitante encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($visitors as $visitor): ?>
                                            <tr>
                                                <td><?= $visitor['id'] ?></td>
                                                <td><?= htmlspecialchars($visitor['nome']) ?></td>
                                                <td><?= htmlspecialchars($visitor['empresa']) ?></td>
                                                <td><?= htmlspecialchars($visitor['pessoa_visitada']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $visitor['status'] === 'ativo' ? 'success' : ($visitor['status'] === 'saiu' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($visitor['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($visitor['data_cadastro'])) ?></td>
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