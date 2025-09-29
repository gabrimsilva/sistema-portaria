<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
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
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Funcionários</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
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
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="card-title">Lista de Funcionários</h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/employees?action=new" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Novo Funcionário
                                    </a>
                                </div>
                            </div>
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
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <?php
    // 🛡️ SISTEMA LGPD UNIVERSAL
    require_once BASE_PATH . '/src/services/LayoutService.php';
    LayoutService::includeUniversalComponents(['page' => 'employees_list']);
    ?>
</body>
</html>