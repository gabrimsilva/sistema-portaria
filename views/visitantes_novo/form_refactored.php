<?php
// Incluir FormService para componentes padronizados
require_once __DIR__ . '/../../src/services/FormService.php';
?>
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
        
        <!-- 🎯 NavigationService: Navegação Unificada -->
        <?= NavigationService::renderSidebar() ?>
        
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
                                    <h3 class="card-title">📋 Dados do Visitante - Formulário Padronizado</h3>
                                </div>
                                
                                <form method="POST" action="/visitantes?action=<?= isset($visitante) ? 'update' : 'save' ?>">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <?php if (isset($visitante)): ?>
                                        <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <!-- ✅ Alerta Padronizado com FormService -->
                                        <?php if (isset($error)): ?>
                                            <?= FormService::renderAlert($error, 'danger') ?>
                                        <?php endif; ?>
                                        
                                        <!-- ✅ Campos Nome e CPF Padronizados -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <?= FormService::renderTextInput([
                                                    'id' => 'nome',
                                                    'name' => 'nome',
                                                    'label' => 'Nome Completo',
                                                    'value' => $visitante['nome'] ?? '',
                                                    'required' => true
                                                ]) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?= FormService::renderCpfInput('cpf', 'cpf', $visitante['cpf'] ?? '', false) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- ✅ Campos Empresa e Funcionário Responsável -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <?= FormService::renderTextInput([
                                                    'id' => 'empresa',
                                                    'name' => 'empresa',
                                                    'label' => 'Empresa',
                                                    'value' => $visitante['empresa'] ?? ''
                                                ]) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?= FormService::renderTextInput([
                                                    'id' => 'funcionario_responsavel',
                                                    'name' => 'funcionario_responsavel',
                                                    'label' => 'Funcionário Responsável',
                                                    'value' => $visitante['funcionario_responsavel'] ?? ''
                                                ]) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- ✅ Campo Placa com Checkbox "A pé" -->
                                        <?= FormService::renderPlacaInput('placa_veiculo', 'placa_veiculo', $visitante['placa_veiculo'] ?? '') ?>
                                        
                                        <!-- ✅ Campos Setor e DateTime -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <?= FormService::renderTextInput([
                                                    'id' => 'setor',
                                                    'name' => 'setor',
                                                    'label' => 'Setor',
                                                    'value' => $visitante['setor'] ?? '',
                                                    'required' => true
                                                ]) ?>
                                            </div>
                                            <div class="col-md-4">
                                                <?= FormService::renderDateTimeInput([
                                                    'id' => 'hora_entrada',
                                                    'name' => 'hora_entrada',
                                                    'label' => 'Hora de Entrada',
                                                    'value' => $visitante['hora_entrada'] ?? ''
                                                ]) ?>
                                            </div>
                                            <div class="col-md-4">
                                                <?= FormService::renderDateTimeInput([
                                                    'id' => 'hora_saida',
                                                    'name' => 'hora_saida',
                                                    'label' => 'Hora de Saída',
                                                    'value' => $visitante['hora_saida'] ?? ''
                                                ]) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- ✅ Botões Padronizados -->
                                    <?= FormService::renderFormButtons('/visitantes', 'Salvar Visitante') ?>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">🎉 Padronização Completa!</h3>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <h5><i class="fas fa-check-circle"></i> FormService Aplicado!</h5>
                                        <ul class="mb-0">
                                            <li>✅ Alertas padronizados</li>
                                            <li>✅ Inputs consistentes</li>
                                            <li>✅ Campo CPF com máscara</li>
                                            <li>✅ Placa com "A pé"</li>
                                            <li>✅ DateTime padronizado</li>
                                            <li>✅ Botões uniformes</li>
                                            <li>✅ Scripts centralizados</li>
                                            <li>✅ NavigationService</li>
                                        </ul>
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
    
    <!-- ✅ Scripts FormService Centralizados -->
    <?= FormService::renderFormScripts(['cpf', 'placa']) ?>
</body>
</html>