<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Funcionário - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navigation -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <!-- Toggle button removed for hover-only sidebar -->
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
                            <h1 class="m-0">Histórico de Acessos</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="/employees">Funcionários</a></li>
                                <li class="breadcrumb-item active">Histórico</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Employee Info Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informações do Funcionário</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <?php if (!empty($employee['foto'])): ?>
                                        <img src="/<?= htmlspecialchars($employee['foto']) ?>" alt="Foto" class="img-fluid rounded">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white p-4 text-center rounded">
                                            <i class="fas fa-user fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-10">
                                    <h4><?= htmlspecialchars($employee['nome']) ?></h4>
                                    <p class="mb-1"><strong>CPF:</strong> <?= htmlspecialchars($employee['cpf']) ?></p>
                                    <p class="mb-1"><strong>Cargo:</strong> <?= htmlspecialchars($employee['cargo']) ?></p>
                                    <p class="mb-1"><strong>Data de Admissão:</strong> <?= date('d/m/Y', strtotime($employee['data_admissao'])) ?></p>
                                    <p class="mb-0">
                                        <strong>Status:</strong> 
                                        <span class="badge badge-<?= $employee['ativo'] ? 'success' : 'danger' ?>">
                                            <?= $employee['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Access History -->
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="card-title">Histórico de Acessos (Últimos 50)</h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/employees" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Voltar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Tipo</th>
                                            <th>Registrado por</th>
                                            <th>Observações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($accessLogs)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum acesso registrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($accessLogs as $log): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $log['tipo'] === 'entrada' ? 'success' : 'warning' ?>">
                                                        <i class="fas fa-<?= $log['tipo'] === 'entrada' ? 'sign-in-alt' : 'sign-out-alt' ?>"></i>
                                                        <?= ucfirst($log['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($log['usuario_nome']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($log['observacoes'])): ?>
                                                        <small><?= htmlspecialchars($log['observacoes']) ?></small>
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