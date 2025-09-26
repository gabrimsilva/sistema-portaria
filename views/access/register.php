<?php
// Inicializar variáveis para evitar warnings
$error = $error ?? '';
$activeVisitors = $activeVisitors ?? [];
$activeEmployees = $activeEmployees ?? [];
$selectedVisitor = $selectedVisitor ?? null;
$selectedEmployee = $selectedEmployee ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Acesso - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .access-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .access-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .access-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        .person-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
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
                <li class="nav-item">
                    <a href="/access?action=scan" class="nav-link">
                        <i class="fas fa-qrcode"></i>
                        <span class="d-none d-md-inline">Scanner QR</span>
                    </a>
                </li>
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
                            <h1 class="m-0">Controle de Acesso</h1>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-right">
                                <a href="/access?action=history" class="btn btn-info">
                                    <i class="fas fa-history"></i> Histórico de Hoje
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= htmlspecialchars($_GET['success']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Registrar Acesso</h3>
                                </div>
                                
                                <form method="POST" action="/access?action=save">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <div class="card-body">
                                        <!-- Tipo de Acesso -->
                                        <div class="form-group">
                                            <label>Tipo de Acesso *</label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="tipo" id="entrada" value="entrada" required>
                                                        <label class="form-check-label" for="entrada">
                                                            <i class="fas fa-sign-in-alt text-success"></i> Entrada
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="tipo" id="saida" value="saida" required>
                                                        <label class="form-check-label" for="saida">
                                                            <i class="fas fa-sign-out-alt text-warning"></i> Saída
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Seleção de Pessoa -->
                                        <div class="form-group">
                                            <label>Selecionar Pessoa *</label>
                                            
                                            <!-- Tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-toggle="tab" href="#visitors-tab">
                                                        <i class="fas fa-users"></i> Visitantes
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-toggle="tab" href="#employees-tab">
                                                        <i class="fas fa-id-badge"></i> Funcionários
                                                    </a>
                                                </li>
                                            </ul>
                                            
                                            <div class="tab-content mt-3">
                                                <!-- Visitantes -->
                                                <div class="tab-pane fade show active" id="visitors-tab">
                                                    <?php if (empty($activeVisitors)): ?>
                                                        <p class="text-muted">Nenhum visitante ativo encontrado.</p>
                                                    <?php else: ?>
                                                        <div class="row">
                                                            <?php foreach ($activeVisitors as $visitor): ?>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="access-card p-3" onclick="selectPerson('visitor', <?= $visitor['id'] ?>)">
                                                                    <input type="radio" name="visitor_id" value="<?= $visitor['id'] ?>" 
                                                                           <?= $selectedVisitor && $selectedVisitor['id'] == $visitor['id'] ? 'checked' : '' ?>
                                                                           style="display: none;">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($visitor['foto'])): ?>
                                                                            <img src="/<?= htmlspecialchars($visitor['foto']) ?>" class="person-photo mr-3" alt="Foto">
                                                                        <?php else: ?>
                                                                            <div class="person-photo mr-3 bg-secondary d-flex align-items-center justify-content-center">
                                                                                <i class="fas fa-user text-white"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <h6 class="mb-1"><?= htmlspecialchars($visitor['nome']) ?></h6>
                                                                            <small class="text-muted">
                                                                                <?= htmlspecialchars($visitor['empresa']) ?><br>
                                                                                Visitando: <?= htmlspecialchars($visitor['pessoa_visitada']) ?>
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Funcionários -->
                                                <div class="tab-pane fade" id="employees-tab">
                                                    <?php if (empty($activeEmployees)): ?>
                                                        <p class="text-muted">Nenhum funcionário ativo encontrado.</p>
                                                    <?php else: ?>
                                                        <div class="row">
                                                            <?php foreach ($activeEmployees as $employee): ?>
                                                            <div class="col-md-6 mb-3">
                                                                <div class="access-card p-3" onclick="selectPerson('employee', <?= $employee['id'] ?>)">
                                                                    <input type="radio" name="employee_id" value="<?= $employee['id'] ?>" 
                                                                           <?= $selectedEmployee && $selectedEmployee['id'] == $employee['id'] ? 'checked' : '' ?>
                                                                           style="display: none;">
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($employee['foto'])): ?>
                                                                            <img src="/<?= htmlspecialchars($employee['foto']) ?>" class="person-photo mr-3" alt="Foto">
                                                                        <?php else: ?>
                                                                            <div class="person-photo mr-3 bg-primary d-flex align-items-center justify-content-center">
                                                                                <i class="fas fa-user text-white"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <h6 class="mb-1"><?= htmlspecialchars($employee['nome']) ?></h6>
                                                                            <small class="text-muted">
                                                                                <?= htmlspecialchars($employee['cargo']) ?>
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Observações -->
                                        <div class="form-group">
                                            <label for="observacoes">Observações</label>
                                            <textarea class="form-control" name="observacoes" id="observacoes" rows="3" placeholder="Observações adicionais (opcional)"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-check"></i> Registrar Acesso
                                        </button>
                                        <a href="/dashboard" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Ações Rápidas</h3>
                                </div>
                                <div class="card-body">
                                    <a href="/visitors?action=new" class="btn btn-primary btn-block mb-2">
                                        <i class="fas fa-user-plus"></i> Cadastrar Visitante
                                    </a>
                                    <a href="/employees?action=new" class="btn btn-info btn-block mb-2">
                                        <i class="fas fa-id-badge"></i> Cadastrar Funcionário
                                    </a>
                                    <a href="/access?action=scan" class="btn btn-warning btn-block mb-2">
                                        <i class="fas fa-qrcode"></i> Scanner QR Code
                                    </a>
                                    <a href="/access?action=history" class="btn btn-secondary btn-block">
                                        <i class="fas fa-history"></i> Histórico do Dia
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Horário -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Informações</h3>
                                </div>
                                <div class="card-body text-center">
                                    <h3 id="current-time"></h3>
                                    <p id="current-date"></p>
                                    <small class="text-muted">Horário do registro</small>
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
    function selectPerson(type, id) {
        // Clear all selections
        document.querySelectorAll('.access-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Clear all radio buttons
        document.querySelectorAll('input[name="visitor_id"], input[name="employee_id"]').forEach(input => {
            input.checked = false;
        });
        
        // Select the clicked card
        event.currentTarget.classList.add('selected');
        const radio = event.currentTarget.querySelector('input[type="radio"]');
        radio.checked = true;
    }
    
    function updateTime() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString('pt-BR');
        document.getElementById('current-date').textContent = now.toLocaleDateString('pt-BR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    // Update time every second
    updateTime();
    setInterval(updateTime, 1000);
    
    // Auto-select if coming from visitor/employee page
    <?php if ($selectedVisitor): ?>
    selectPerson('visitor', <?= $selectedVisitor['id'] ?>);
    <?php endif; ?>
    
    <?php if ($selectedEmployee): ?>
    selectPerson('employee', <?= $selectedEmployee['id'] ?>);
    document.querySelector('a[href="#employees-tab"]').click();
    <?php endif; ?>
    </script>
</body>
</html>