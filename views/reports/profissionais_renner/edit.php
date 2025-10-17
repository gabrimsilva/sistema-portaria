<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Editar Profissional Renner - Sistema de Controle de Acesso</title>
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
                            <h1 class="m-0">Editar Profissional Renner</h1>
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
                                
                                <form method="POST" action="/reports/profissionais-renner?action=update">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <input type="hidden" name="id" value="<?= $profissional['profissional_renner_id'] ?>">
                                    <input type="hidden" name="registro_id" value="<?= $profissional['id'] ?>">
                                    
                                    <div class="card-body">
                                        <?php if (isset($error)): ?>
                                            <div class="alert alert-danger">
                                                <?= htmlspecialchars($error) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label>Nome Completo *</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($profissional['nome'] ?? '') ?>" readonly>
                                            <input type="hidden" name="nome" value="<?= htmlspecialchars($profissional['nome'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Setor</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($profissional['setor'] ?? '') ?>" readonly>
                                            <input type="hidden" name="setor" value="<?= htmlspecialchars($profissional['setor'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Placa de Veículo</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control text-uppercase" id="placa_veiculo" name="placa_veiculo" 
                                                       value="<?= htmlspecialchars($profissional['placa_veiculo'] ?? '') ?>" maxlength="7">
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <input type="checkbox" id="ape_checkbox" <?= ($profissional['placa_veiculo'] ?? '') === 'APE' ? 'checked' : '' ?>>
                                                        <label for="ape_checkbox" class="ml-1 mb-0">A pé</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted">Marque "A pé" se não houver veículo</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Saída Intermediária</label>
                                                    <input type="datetime-local" class="form-control" id="saida" name="saida"
                                                           value="<?= !empty($profissional['saida_at']) ? date('Y-m-d\TH:i', strtotime($profissional['saida_at'])) : '' ?>">
                                                    <small class="text-muted">Primeira saída durante o expediente</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Retorno</label>
                                                    <input type="datetime-local" class="form-control" id="retorno" name="retorno"
                                                           value="<?= !empty($profissional['retorno']) ? date('Y-m-d\TH:i', strtotime($profissional['retorno'])) : '' ?>">
                                                    <small class="text-muted">Retorno após saída intermediária</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Hora de Saída Final</label>
                                            <input type="datetime-local" class="form-control" id="saida_final" name="saida_final"
                                                   value="<?= !empty($profissional['saida_final']) ? date('Y-m-d\TH:i', strtotime($profissional['saida_final'])) : '' ?>">
                                            <small class="text-muted">Deixe em branco se a pessoa ainda não saiu da empresa</small>
                                        </div>
                                        
                                        <!-- Campo Observação para entrada retroativa -->
                                        <?php if ($profissional['is_retroativa'] && !empty($profissional['observacao_retroativa'])): ?>
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-exclamation-triangle text-warning"></i> 
                                                Observação/Justificativa
                                                <span class="badge badge-warning">Entrada Retroativa</span>
                                            </label>
                                            <textarea class="form-control" rows="3" readonly><?= htmlspecialchars($profissional['observacao_retroativa']) ?></textarea>
                                            <small class="text-muted">Justificativa informada no momento do registro retroativo</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                        <a href="/reports/profissionais-renner" class="btn btn-secondary">
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
        // Máscara para placa de veículo (Mercosul)
        function aplicarMascaraPlaca() {
            $('#placa_veiculo').on('input', function() {
                let value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (value.length <= 7) {
                    if (value.length > 3) {
                        value = value.slice(0, 3) + value.slice(3);
                    }
                    this.value = value;
                }
            });
        }
        
        aplicarMascaraPlaca();
        
        // Controle checkbox "A pé"
        let previousValue = $('#placa_veiculo').val();
        $('#ape_checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                previousValue = $('#placa_veiculo').val();
                $('#placa_veiculo').val('APE').prop('readonly', true);
            } else {
                $('#placa_veiculo').val(previousValue).prop('readonly', false);
            }
        });
        
        // Se já está marcado como "A pé", deixar readonly
        if ($('#ape_checkbox').is(':checked')) {
            $('#placa_veiculo').prop('readonly', true);
        }
    });
    </script>
</body>
</html>
