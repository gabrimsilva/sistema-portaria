<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($profissional) ? 'Editar' : 'Novo' ?> Profissional Renner - Sistema de Controle de Acesso</title>
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
        
        <!-- 🎯 NavigationService: Navegação Unificada -->
        <?= NavigationService::renderSidebar() ?>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= isset($profissional) ? 'Editar' : 'Novo' ?> Profissional Renner</h1>
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
                                    <h3 class="card-title">Dados do Profissional</h3>
                                </div>
                                
                                <form method="POST" action="/profissionais-renner?action=<?= isset($profissional) ? 'update' : 'save' ?>">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <?php if (isset($profissional)): ?>
                                        <input type="hidden" name="id" value="<?= $profissional['id'] ?>">
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
                                                    <label for="nome">Nome Completo *</label>
                                                    <input type="text" class="form-control" id="nome" name="nome" 
                                                           value="<?= htmlspecialchars($profissional['nome'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="setor">Setor</label>
                                                    <input type="text" class="form-control" id="setor" name="setor" 
                                                           value="<?= htmlspecialchars($profissional['setor'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="data_entrada">Data Entrada</label>
                                                    <input type="datetime-local" class="form-control" id="data_entrada" name="data_entrada" 
                                                           value="<?= $profissional['data_entrada'] ? date('Y-m-d\TH:i', strtotime($profissional['data_entrada'])) : '' ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="saida">Saída Intermediária</label>
                                                    <input type="datetime-local" class="form-control" id="saida" name="saida" 
                                                           value="<?= $profissional['saida'] ? date('Y-m-d\TH:i', strtotime($profissional['saida'])) : '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="retorno">Retorno</label>
                                                    <input type="datetime-local" class="form-control" id="retorno" name="retorno" 
                                                           value="<?= $profissional['retorno'] ? date('Y-m-d\TH:i', strtotime($profissional['retorno'])) : '' ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="saida_final">Saída Final</label>
                                                    <input type="datetime-local" class="form-control" id="saida_final" name="saida_final" 
                                                           value="<?= $profissional['saida_final'] ? date('Y-m-d\TH:i', strtotime($profissional['saida_final'])) : '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="placa_veiculo">Placa de Veículo *</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="placa_veiculo" name="placa_veiculo" 
                                                       value="<?= htmlspecialchars($profissional['placa_veiculo'] ?? '') ?>" 
                                                       placeholder="ABC-1234" style="text-transform: uppercase;" required>
                                                <span class="input-group-text">
                                                    <input type="checkbox" id="ape_checkbox" <?= ($profissional['placa_veiculo'] ?? '') === 'APE' ? 'checked' : '' ?>>
                                                    <label for="ape_checkbox" class="ml-1 mb-0">A pé</label>
                                                </span>
                                            </div>
                                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                        <a href="/profissionais-renner" class="btn btn-secondary">
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
    </script>
</body>
</html>