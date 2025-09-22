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
                            <a href="/prestadores-servico" class="nav-link active">
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
                                <div class="col-md-6">
                                    <h3 class="card-title">Lista de Prestadores de Serviço</h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/prestadores-servico?action=new" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Novo Prestador
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou empresa" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="setor" class="form-control">
                                            <option value="">Todos os setores</option>
                                            <?php foreach ($setores as $s): ?>
                                                <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['setor']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="empresa" class="form-control">
                                            <option value="">Todas as empresas</option>
                                            <?php foreach ($empresas as $e): ?>
                                                <option value="<?= htmlspecialchars($e['empresa']) ?>" <?= ($_GET['empresa'] ?? '') === $e['empresa'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($e['empresa']) ?>
                                                </option>
                                            <?php endforeach; ?>
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
                                            <th>Nome</th>
                                            <th>CPF</th>
                                            <th>Empresa</th>
                                            <th>Setor</th>
                                            <th>Entrada</th>
                                            <th>Saída</th>
                                            <th>Status</th>
                                            <th>Observações</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($prestadores)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center">Nenhum prestador encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($prestadores as $prestador): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prestador['nome']) ?></td>
                                                <td><?= htmlspecialchars($prestador['cpf']) ?></td>
                                                <td><?= htmlspecialchars($prestador['empresa']) ?></td>
                                                <td><?= htmlspecialchars($prestador['setor']) ?></td>
                                                <td><?= $prestador['entrada'] ? date('d/m/Y H:i', strtotime($prestador['entrada'])) : '-' ?></td>
                                                <td><?= $prestador['saida'] ? date('d/m/Y H:i', strtotime($prestador['saida'])) : '-' ?></td>
                                                <td>
                                                    <?php 
                                                    $status = '';
                                                    $badgeClass = '';
                                                    if ($prestador['entrada'] && !$prestador['saida']) {
                                                        $status = 'Trabalhando';
                                                        $badgeClass = 'success';
                                                    } elseif ($prestador['saida']) {
                                                        $status = 'Finalizado';
                                                        $badgeClass = 'secondary';
                                                    } else {
                                                        $status = 'Aguardando';
                                                        $badgeClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $badgeClass ?>"><?= $status ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($prestador['observacao']): ?>
                                                        <span title="<?= htmlspecialchars($prestador['observacao']) ?>">
                                                            <?= strlen($prestador['observacao']) > 30 ? substr(htmlspecialchars($prestador['observacao']), 0, 30) . '...' : htmlspecialchars($prestador['observacao']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/prestadores-servico?action=edit&id=<?= $prestador['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (!$prestador['entrada']): ?>
                                                            <form method="POST" action="/prestadores-servico?action=entrada" class="d-inline">
                                                                <?= CSRFProtection::getHiddenInput() ?>
                                                                <input type="hidden" name="id" value="<?= $prestador['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" title="Registrar Entrada">
                                                                    <i class="fas fa-sign-in-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php elseif (!$prestador['saida']): ?>
                                                            <form method="POST" action="/prestadores-servico?action=saida" class="d-inline">
                                                                <?= CSRFProtection::getHiddenInput() ?>
                                                                <input type="hidden" name="id" value="<?= $prestador['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning" title="Registrar Saída">
                                                                    <i class="fas fa-sign-out-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" action="/prestadores-servico?action=delete" class="d-inline">
                                                            <?= CSRFProtection::getHiddenInput() ?>
                                                            <input type="hidden" name="id" value="<?= $prestador['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>