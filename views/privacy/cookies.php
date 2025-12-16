<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Sistema de Acesso</title>
    
    <!-- Bootstrap 4.6.2 + AdminLTE 3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        .cookie-category {
            margin-bottom: 2rem;
        }
        .cookie-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .cookie-card.essential {
            border-left-color: #28a745;
        }
        .cookie-card.functional {
            border-left-color: #ffc107;
        }
        .cookie-card.performance {
            border-left-color: #17a2b8;
        }
        .cookie-card.marketing {
            border-left-color: #dc3545;
        }
        .cookie-toggle {
            transform: scale(1.2);
        }
        .cookie-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .cookie-list {
            list-style: none;
            padding: 0;
        }
        .cookie-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .cookie-list li:last-child {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-required {
            background: #d4edda;
            color: #155724;
        }
        .status-optional {
            background: #d1ecf1;
            color: #0c5460;
        }
        .save-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
            <div class="container">
                <a href="/" class="navbar-brand">
                    <?= LogoService::renderSimpleLogo('renner', 'header'); ?>
                    <span class="brand-text font-weight-light ml-2">Sistema de Acesso</span>
                </a>
                
                <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse order-3" id="navbarCollapse">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a href="/" class="nav-link">Início</a>
                        </li>
                        <li class="nav-item">
                            <a href="/privacy" class="nav-link">Privacidade</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle active" data-toggle="dropdown">LGPD</a>
                            <div class="dropdown-menu">
                                <a href="/privacy/portal" class="dropdown-item">Portal do Titular</a>
                                <a href="/privacy/cookies" class="dropdown-item active">Gestão de Cookies</a>
                            </div>
                        </li>
                    </ul>
                </div>

                <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                    <li class="nav-item">
                        <a href="/login" class="nav-link">
                            <i class="fas fa-sign-in-alt"></i>
                            Entrar
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container">
                    <div class="row justify-content-center text-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 font-weight-bold mb-3">
                                <i class="fas fa-cookie-bite mr-3"></i>
                                Gestão de Cookies
                            </h1>
                            <p class="lead mb-4">Configure suas preferências de cookies e tecnologias similares</p>
                            <p class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Cookies essenciais não podem ser desabilitados para garantir o funcionamento do sistema
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container">
                    <!-- Alerts -->
                    <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="/privacy/cookies" id="cookie-form">
                        <!-- 🛡️ PROTEÇÃO CSRF OBRIGATÓRIA -->
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        
                        <!-- Essential Cookies -->
                        <div class="cookie-category">
                            <div class="card cookie-card essential">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">
                                            <i class="fas fa-shield-alt mr-2 text-success"></i>
                                            Cookies Essenciais
                                        </h4>
                                        <div>
                                            <span class="status-badge status-required">Obrigatórios</span>
                                            <input type="checkbox" class="cookie-toggle ml-2" checked disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Necessários para o funcionamento básico do sistema. Não podem ser desabilitados.
                                    </p>
                                    <div class="cookie-details">
                                        <strong>Base Legal:</strong> Legítimo interesse (segurança e funcionamento)<br>
                                        <strong>Duração:</strong> Sessão (excluídos ao fechar o navegador)<br>
                                        <strong>Finalidade:</strong> Autenticação, proteção CSRF, manutenção de sessão
                                        
                                        <ul class="cookie-list mt-2">
                                            <li><strong>PHPSESSID:</strong> Mantém sua sessão ativa no sistema</li>
                                            <li><strong>csrf_token:</strong> Proteção contra ataques de segurança</li>
                                            <li><strong>auth_token:</strong> Verificação de autenticação</li>
                                            <li><strong>audit_id:</strong> Identificador para logs de auditoria</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Functional Cookies -->
                        <div class="cookie-category">
                            <div class="card cookie-card functional">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">
                                            <i class="fas fa-cogs mr-2 text-warning"></i>
                                            Cookies de Funcionalidade
                                        </h4>
                                        <div>
                                            <span class="status-badge status-optional">Opcionais</span>
                                            <input type="checkbox" class="cookie-toggle ml-2" name="cookies_functional" 
                                                   id="cookies_functional" <?= $cookiePreferences['functional'] ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Melhoram sua experiência de uso, lembrando suas preferências.
                                    </p>
                                    <div class="cookie-details">
                                        <strong>Base Legal:</strong> Consentimento ou legítimo interesse<br>
                                        <strong>Duração:</strong> 30 dias<br>
                                        <strong>Finalidade:</strong> Personalização da interface, preferências do usuário
                                        
                                        <ul class="cookie-list mt-2">
                                            <li><strong>language_pref:</strong> Idioma preferido do sistema</li>
                                            <li><strong>theme_mode:</strong> Tema claro/escuro escolhido</li>
                                            <li><strong>dashboard_layout:</strong> Layout personalizado do dashboard</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Cookies -->
                        <div class="cookie-category">
                            <div class="card cookie-card performance">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">
                                            <i class="fas fa-chart-line mr-2 text-info"></i>
                                            Cookies de Performance
                                        </h4>
                                        <div>
                                            <span class="status-badge status-optional">Opcionais</span>
                                            <input type="checkbox" class="cookie-toggle ml-2" name="cookies_performance" 
                                                   id="cookies_performance" <?= $cookiePreferences['performance'] ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Coletam informações sobre o uso do sistema para melhorar performance.
                                    </p>
                                    <div class="cookie-details">
                                        <strong>Base Legal:</strong> Consentimento<br>
                                        <strong>Duração:</strong> 90 dias<br>
                                        <strong>Finalidade:</strong> Análise de performance, identificação de problemas
                                        
                                        <ul class="cookie-list mt-2">
                                            <li><strong>analytics_session:</strong> Análise de performance do sistema</li>
                                            <li><strong>error_tracking:</strong> Identificação de problemas técnicos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Marketing Cookies -->
                        <div class="cookie-category">
                            <div class="card cookie-card marketing">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">
                                            <i class="fas fa-bullhorn mr-2 text-danger"></i>
                                            Cookies de Marketing
                                        </h4>
                                        <div>
                                            <span class="status-badge status-optional">Opcionais</span>
                                            <input type="checkbox" class="cookie-toggle ml-2" name="cookies_marketing" 
                                                   id="cookies_marketing" <?= $cookiePreferences['marketing'] ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">
                                        Usados para personalizar comunicações e ofertas relevantes.
                                    </p>
                                    <div class="cookie-details">
                                        <strong>Base Legal:</strong> Consentimento<br>
                                        <strong>Duração:</strong> 365 dias<br>
                                        <strong>Finalidade:</strong> Comunicações personalizadas, ofertas relevantes
                                        
                                        <ul class="cookie-list mt-2">
                                            <li><strong>marketing_pref:</strong> Preferências de comunicação</li>
                                            <li><strong>campaign_tracking:</strong> Acompanhamento de campanhas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Section -->
                        <div class="save-section">
                            <h4 class="mb-3">
                                <i class="fas fa-save mr-2"></i>
                                Salvar Preferências
                            </h4>
                            <p class="mb-4">
                                Suas preferências serão salvas e aplicadas imediatamente. 
                                Você pode alterar essas configurações a qualquer momento.
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <button type="submit" class="btn btn-light btn-lg btn-block">
                                        <i class="fas fa-check mr-2"></i>
                                        Salvar Preferências
                                    </button>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <button type="button" class="btn btn-outline-light btn-lg btn-block" onclick="acceptAll()">
                                        <i class="fas fa-check-double mr-2"></i>
                                        Aceitar Todos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Information Cards -->
                    <div class="row mt-5">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-info-circle fa-2x text-primary mb-3"></i>
                                    <h5>O que são Cookies?</h5>
                                    <p class="text-muted">Pequenos arquivos que armazenam informações sobre suas preferências</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit fa-2x text-success mb-3"></i>
                                    <h5>Controle Total</h5>
                                    <p class="text-muted">Você pode alterar suas preferências a qualquer momento</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-2x text-info mb-3"></i>
                                    <h5>Política Completa</h5>
                                    <p class="text-muted">
                                        <a href="/privacy">Leia nossa política completa de privacidade</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="container">
                <div class="row">
                    <div class="col-sm-6">
                        <strong>Renner Coatings</strong> - Gestão de Cookies
                    </div>
                    <div class="col-sm-6 text-right">
                        <strong>Sua privacidade é importante</strong> - Versão 1.1.0
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <!-- Cookie Consent -->
    <script src="/assets/js/cookie-consent.js?v=<?= time() ?>"></script>
    
    <script>
        function acceptAll() {
            // Check all optional cookies
            $('#cookies_functional').prop('checked', true);
            $('#cookies_performance').prop('checked', true);
            $('#cookies_marketing').prop('checked', true);
            
            // Submit form
            $('#cookie-form').submit();
        }

        $(document).ready(function() {
            // Add visual feedback when toggles change
            $('.cookie-toggle').change(function() {
                if (!$(this).prop('disabled')) {
                    const card = $(this).closest('.card');
                    if ($(this).prop('checked')) {
                        card.addClass('border-success');
                    } else {
                        card.removeClass('border-success');
                    }
                }
            });
        });
    </script>

    <?php
    // Incluir banner de cookies
    require_once __DIR__ . '/../components/cookie-banner.php';
    ?>
</body>
</html>