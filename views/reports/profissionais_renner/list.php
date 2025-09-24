<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Profissionais Renner - Sistema de Controle de Acesso</title>
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
                            <a href="/profissionais-renner" class="nav-link active">
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
                            <h1 class="m-0">Profissionais Renner</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            Profissional cadastrado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            Profissional atualizado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="card-title">Lista de Profissionais</h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/profissionais-renner?action=new" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Novo Profissional
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou setor" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
                                        <select name="status" class="form-control">
                                            <option value="">Todos os status</option>
                                            <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo (na empresa)</option>
                                            <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Saiu</option>
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
                                            <th>Data Entrada</th>
                                            <th>Saída</th>
                                            <th>Retorno</th>
                                            <th>Saída Final</th>
                                            <th>Setor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($profissionais)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhum profissional encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($profissionais as $prof): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($prof['nome']) ?></td>
                                                <td><?= $prof['data_entrada'] ? date('d/m/Y H:i', strtotime($prof['data_entrada'])) : '-' ?></td>
                                                <td><?= $prof['saida'] ? date('d/m/Y H:i', strtotime($prof['saida'])) : '-' ?></td>
                                                <td><?= $prof['retorno'] ? date('d/m/Y H:i', strtotime($prof['retorno'])) : '-' ?></td>
                                                <td><?= $prof['saida_final'] ? date('d/m/Y H:i', strtotime($prof['saida_final'])) : '-' ?></td>
                                                <td><?= htmlspecialchars($prof['setor']) ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/profissionais-renner?action=edit&id=<?= $prof['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" action="/profissionais-renner?action=delete" class="d-inline">
                                                            <?= CSRFProtection::getHiddenInput() ?>
                                                            <input type="hidden" name="id" value="<?= $prof['id'] ?>">
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