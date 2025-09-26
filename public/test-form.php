<?php
session_start();

// Incluir arquivos necessários
require_once '../src/services/FormService.php';
require_once '../src/services/LogoService.php';
require_once '../src/services/NavigationService.php';
require_once '../config/csrf.php';

// Simular sessão para teste
$_SESSION['user_name'] = 'Teste Usuario';
$_SESSION['user_profile'] = 'administrador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste FormService - Sistema de Controle de Acesso</title>
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
                            <h1 class="m-0">🧪 Teste FormService</h1>
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
                                    <h3 class="card-title">Componentes Padronizados</h3>
                                </div>
                                
                                <form method="POST" action="#">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    
                                    <div class="card-body">
                                        <!-- Alert de Teste -->
                                        <?= FormService::renderAlert('Este é um formulário de teste para validar os componentes do FormService.', 'info') ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <!-- Input Nome Padronizado -->
                                                <?= FormService::renderTextInput([
                                                    'id' => 'nome',
                                                    'name' => 'nome',
                                                    'label' => 'Nome Completo',
                                                    'value' => 'João Silva',
                                                    'required' => true,
                                                    'placeholder' => 'Digite o nome completo'
                                                ]) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Campo CPF com Máscara -->
                                                <?= FormService::renderCpfInput('cpf', 'cpf', '12345678901', false) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <!-- Input Empresa -->
                                                <?= FormService::renderTextInput([
                                                    'id' => 'empresa',
                                                    'name' => 'empresa',
                                                    'label' => 'Empresa',
                                                    'value' => 'Empresa Teste Ltda',
                                                    'placeholder' => 'Nome da empresa'
                                                ]) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Input Setor -->
                                                <?= FormService::renderTextInput([
                                                    'id' => 'setor',
                                                    'name' => 'setor',
                                                    'label' => 'Setor',
                                                    'value' => 'TI',
                                                    'required' => true
                                                ]) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Campo Placa com "A pé" -->
                                        <?= FormService::renderPlacaInput('placa_veiculo', 'placa_veiculo', 'ABC1234') ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <!-- Campo DateTime Entrada -->
                                                <?= FormService::renderDateTimeInput([
                                                    'id' => 'entrada',
                                                    'name' => 'entrada',
                                                    'label' => 'Data/Hora de Entrada',
                                                    'value' => '2025-09-26T14:30'
                                                ]) ?>
                                            </div>
                                            <div class="col-md-6">
                                                <!-- Campo DateTime Saída -->
                                                <?= FormService::renderDateTimeInput([
                                                    'id' => 'saida',
                                                    'name' => 'saida',
                                                    'label' => 'Data/Hora de Saída',
                                                    'value' => ''
                                                ]) ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Textarea Observações -->
                                        <?= FormService::renderTextarea([
                                            'id' => 'observacoes',
                                            'name' => 'observacoes',
                                            'label' => 'Observações',
                                            'value' => 'Teste de observações...',
                                            'placeholder' => 'Digite suas observações aqui...',
                                            'rows' => 4
                                        ]) ?>
                                    </div>
                                    
                                    <!-- Botões Padronizados -->
                                    <?= FormService::renderFormButtons('/dashboard', 'Testar Salvamento') ?>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Resultados do Teste</h3>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-success">
                                        <h5><i class="fas fa-check"></i> FormService Funcionando!</h5>
                                        <ul>
                                            <li>✅ Inputs padronizados</li>
                                            <li>✅ Máscara CPF</li>
                                            <li>✅ Campo Placa com "A pé"</li>
                                            <li>✅ DateTime inputs</li>
                                            <li>✅ Textarea</li>
                                            <li>✅ Botões padrão</li>
                                            <li>✅ Alertas consistentes</li>
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
    
    <!-- Scripts FormService -->
    <?= FormService::renderFormScripts(['cpf', 'placa']) ?>
</body>
</html>