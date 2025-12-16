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
        .rights-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            height: 100%;
        }
        .rights-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .rights-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
            margin: 0 auto 1rem;
        }
        .icon-access { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .icon-correction { background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%); }
        .icon-deletion { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .icon-export { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        
        .form-card {
            display: none;
            animation: slideDown 0.3s ease;
        }
        .form-card.active {
            display: block;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
            background: #e9ecef;
            color: #6c757d;
            font-weight: bold;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
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
                                <a href="/privacy/portal" class="dropdown-item active">Portal do Titular</a>
                                <a href="/privacy/cookies" class="dropdown-item">Gestão de Cookies</a>
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
                                <i class="fas fa-user-shield mr-3"></i>
                                Portal do Titular de Dados
                            </h1>
                            <p class="lead mb-4">Exerça seus direitos conforme a Lei Geral de Proteção de Dados (LGPD)</p>
                            <p class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Suas solicitações são processadas em até <strong>15 dias úteis</strong>
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

                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step-1">1</div>
                        <div class="step" id="step-2">2</div>
                        <div class="step" id="step-3">3</div>
                    </div>

                    <!-- Step 1: Choose Rights -->
                    <div id="rights-selection" class="row">
                        <div class="col-12 text-center mb-4">
                            <h3>Escolha o direito que deseja exercer:</h3>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card rights-card" data-right="access">
                                <div class="card-body text-center">
                                    <div class="rights-icon icon-access">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h5 class="font-weight-bold">Acesso aos Dados</h5>
                                    <p class="text-muted">Veja quais dados pessoais temos sobre você</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card rights-card" data-right="correction">
                                <div class="card-body text-center">
                                    <div class="rights-icon icon-correction">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <h5 class="font-weight-bold">Correção</h5>
                                    <p class="text-muted">Solicite correção de dados incorretos</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card rights-card" data-right="deletion">
                                <div class="card-body text-center">
                                    <div class="rights-icon icon-deletion">
                                        <i class="fas fa-trash"></i>
                                    </div>
                                    <h5 class="font-weight-bold">Eliminação</h5>
                                    <p class="text-muted">Solicite remoção dos seus dados</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card rights-card" data-right="export">
                                <div class="card-body text-center">
                                    <div class="rights-icon icon-export">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <h5 class="font-weight-bold">Portabilidade</h5>
                                    <p class="text-muted">Baixe seus dados em formato digital</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Request Form -->
                    <div id="request-form" class="form-card">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0" id="form-title">Solicitação de Direito LGPD</h4>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" action="/privacy/portal" id="lgpd-form">
                                            <!-- 🛡️ PROTEÇÃO CSRF OBRIGATÓRIA -->
                                            <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                                            <input type="hidden" name="request_type" id="request_type">
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="cpf">CPF:</label>
                                                        <input type="text" class="form-control" id="cpf" name="cpf" 
                                                               placeholder="000.000.000-00" maxlength="14">
                                                        <small class="form-text text-muted">Apenas números ou formato com pontos e hífen</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="email">Email:</label>
                                                        <input type="email" class="form-control" id="email" name="email" 
                                                               placeholder="seu-email@exemplo.com">
                                                        <small class="form-text text-muted">Informe CPF ou email (obrigatório ao menos um)</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group" id="message-group" style="display: none;">
                                                <label for="message">Detalhes da Solicitação:</label>
                                                <textarea class="form-control" id="message" name="message" rows="4" 
                                                          placeholder="Descreva sua solicitação em detalhes..."></textarea>
                                            </div>

                                            <div class="form-group form-check">
                                                <input type="checkbox" class="form-check-input" id="consent" required>
                                                <label class="form-check-label" for="consent">
                                                    Declaro que as informações fornecidas são verdadeiras e autorizo o processamento desta solicitação conforme a LGPD.
                                                </label>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                <button type="button" class="btn btn-secondary" onclick="goBack()">
                                                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-paper-plane mr-2"></i>Enviar Solicitação
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Cards -->
                    <div class="row mt-5">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                                    <h5>Prazo de Resposta</h5>
                                    <p class="text-muted">Suas solicitações são processadas em até 15 dias úteis</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-shield-alt fa-2x text-success mb-3"></i>
                                    <h5>Segurança</h5>
                                    <p class="text-muted">Todas as solicitações são verificadas para sua segurança</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-2x text-info mb-3"></i>
                                    <h5>Contato Direto</h5>
                                    <p class="text-muted">
                                        <a href="mailto:privacidade@renner.com.br">privacidade@renner.com.br</a>
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
                        <strong>Renner Coatings</strong> - Portal do Titular LGPD
                    </div>
                    <div class="col-sm-6 text-right">
                        <strong>Seus direitos protegidos</strong> - Versão 1.1.0
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
        $(document).ready(function() {
            // Handle rights card selection
            $('.rights-card').click(function() {
                const rightType = $(this).data('right');
                selectRight(rightType);
            });

            // CPF mask
            $('#cpf').on('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-$4');
                }
                this.value = value;
            });
        });

        function selectRight(rightType) {
            // Update form
            $('#request_type').val(rightType);
            
            // Update step indicator
            $('#step-1').removeClass('active').addClass('completed');
            $('#step-2').addClass('active');
            
            // Show form
            $('#rights-selection').hide();
            $('#request-form').addClass('active');
            
            // Update form content based on right type
            const titles = {
                'access': 'Solicitação de Acesso aos Dados',
                'correction': 'Solicitação de Correção de Dados',
                'deletion': 'Solicitação de Eliminação de Dados',
                'export': 'Solicitação de Portabilidade de Dados'
            };
            
            $('#form-title').text(titles[rightType] || 'Solicitação de Direito LGPD');
            
            // Show message field for correction and deletion
            if (rightType === 'correction' || rightType === 'deletion') {
                $('#message-group').show();
                $('#message').prop('required', true);
            } else {
                $('#message-group').hide();
                $('#message').prop('required', false);
            }
        }

        function goBack() {
            // Reset step indicator
            $('#step-1').removeClass('completed').addClass('active');
            $('#step-2').removeClass('active');
            
            // Show rights selection
            $('#request-form').removeClass('active');
            $('#rights-selection').show();
            
            // Reset form
            $('#lgpd-form')[0].reset();
        }
    </script>

    <?php
    // Incluir banner de cookies
    require_once __DIR__ . '/../components/cookie-banner.php';
    ?>
</body>
</html>