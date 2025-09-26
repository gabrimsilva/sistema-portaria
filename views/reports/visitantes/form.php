<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($visitante) ? 'Editar' : 'Novo' ?> Visitante - Sistema de Controle de Acesso</title>
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
                <img src="https://via.placeholder.com/33x33" alt="Logo" class="brand-image img-circle elevation-3">
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
                        <li class="nav-item has-treeview menu-open">
                            <a href="#" class="nav-link active">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Relatórios
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/reports/profissionais-renner" class="nav-link">
                                        <i class="nav-icon fas fa-user-tie"></i>
                                        <p>Profissionais Renner</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/visitantes" class="nav-link active">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Visitantes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/prestadores-servico" class="nav-link">
                                        <i class="nav-icon fas fa-tools"></i>
                                        <p>Prestador de Serviços</p>
                                    </a>
                                </li>
                            </ul>
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
                            <h1 class="m-0"><?= isset($visitante) ? 'Editar' : 'Novo' ?> Visitante</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Dados do Visitante</h3>
                                </div>
                                
                                <form method="POST" action="/visitantes?action=<?= isset($visitante) ? 'update' : 'save' ?>">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <?php if (isset($visitante)): ?>
                                        <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <?php if (isset($error)): ?>
                                            <div class="alert alert-danger">
                                                <?= htmlspecialchars($error) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nome">Nome *</label>
                                                    <input type="text" class="form-control" id="nome" name="nome" 
                                                           value="<?= htmlspecialchars($visitante['nome'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="cpf">CPF</label>
                                                    <input type="text" class="form-control" id="cpf" name="cpf" 
                                                           value="<?= htmlspecialchars($visitante['cpf'] ?? '') ?>" 
                                                           placeholder="000.000.000-00">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="empresa">Empresa</label>
                                                    <input type="text" class="form-control" id="empresa" name="empresa" 
                                                           value="<?= htmlspecialchars($visitante['empresa'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="funcionario_responsavel">Funcionário Responsável</label>
                                                    <input type="text" class="form-control" id="funcionario_responsavel" name="funcionario_responsavel" 
                                                           value="<?= htmlspecialchars($visitante['funcionario_responsavel'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="placa_veiculo">Placa de Veículo *</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="placa_veiculo" name="placa_veiculo" 
                                                       value="<?= htmlspecialchars($visitante['placa_veiculo'] ?? '') ?>" 
                                                       placeholder="ABC-1234" style="text-transform: uppercase;" required>
                                                <span class="input-group-text">
                                                    <input type="checkbox" id="ape_checkbox" <?= ($visitante['placa_veiculo'] ?? '') === 'APE' ? 'checked' : '' ?>>
                                                    <label for="ape_checkbox" class="ml-1 mb-0">A pé</label>
                                                </span>
                                            </div>
                                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="setor">Setor</label>
                                                    <input type="text" class="form-control" id="setor" name="setor" 
                                                           value="<?= htmlspecialchars($visitante['setor'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="hora_entrada">Hora de Entrada</label>
                                                    <input type="datetime-local" class="form-control" id="hora_entrada" name="hora_entrada" 
                                                           value="<?= $visitante['hora_entrada'] ? date('Y-m-d\TH:i', strtotime($visitante['hora_entrada'])) : '' ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="hora_saida">Hora de Saída</label>
                                                    <input type="datetime-local" class="form-control" id="hora_saida" name="hora_saida" 
                                                           value="<?= $visitante['hora_saida'] ? date('Y-m-d\TH:i', strtotime($visitante['hora_saida'])) : '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                        <a href="/visitantes" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </form>
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
    $(document).ready(function() {
        let previousValue = '';
        
        // Controlar checkbox "A pé"
        $('#ape_checkbox').change(function() {
            if ($(this).is(':checked')) {
                previousValue = $('#placa_veiculo').val() !== 'APE' ? $('#placa_veiculo').val() : '';
                $('#placa_veiculo').val('APE').prop('readonly', true);
            } else {
                $('#placa_veiculo').val(previousValue).prop('readonly', false);
            }
        });
        
        // Verificar estado inicial
        if ($('#placa_veiculo').val() === 'APE') {
            $('#ape_checkbox').prop('checked', true);
            $('#placa_veiculo').prop('readonly', true);
        }
    });
    
    // Format CPF input
    document.getElementById('cpf').addEventListener('input', function (e) {
        var value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    });
    </script>
</body>
</html>