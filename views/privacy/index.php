<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Sistema de Acesso</title>
    
    <!-- Bootstrap 4.6.2 + AdminLTE 3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        .privacy-section {
            margin-bottom: 2rem;
        }
        .privacy-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
            margin-right: 1rem;
        }
        .content-item {
            padding: 0.25rem 0;
        }
        .important-note {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
            color: #212529;
            padding: 1rem;
            border-radius: 10px;
            margin: 0.5rem 0;
            border-left: 4px solid #ff6f00;
        }
        .contact-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }
        .hero-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        .action-buttons {
            margin-top: 2rem;
        }
        .btn-privacy {
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0.5rem;
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
                            <a href="/privacy" class="nav-link active">Privacidade</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">LGPD</a>
                            <div class="dropdown-menu">
                                <a href="/privacy/portal" class="dropdown-item">Portal do Titular</a>
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
                                <i class="fas fa-shield-alt mr-3"></i>
                                <?= htmlspecialchars($privacyNotice['title']) ?>
                            </h1>
                            <p class="lead mb-4"><?= htmlspecialchars($privacyNotice['subtitle']) ?></p>
                            <p class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Este sistema processa dados pessoais em conformidade com a <strong>Lei Geral de Proteção de Dados (LGPD)</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container">
                    <!-- Privacy Sections -->
                    <div class="row">
                        <?php foreach ($privacyNotice['sections'] as $section): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 privacy-section">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="privacy-icon">
                                            <i class="<?= htmlspecialchars($section['icon']) ?>"></i>
                                        </div>
                                        <h4 class="mb-0 font-weight-bold"><?= htmlspecialchars($section['title']) ?></h4>
                                    </div>
                                    
                                    <?php foreach ($section['content'] as $item): ?>
                                    <div class="content-item">
                                        <?= $item ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Contact Information -->
                    <div class="row mt-4">
                        <div class="col-lg-8 mx-auto">
                            <div class="contact-card">
                                <h3 class="font-weight-bold mb-3">
                                    <i class="fas fa-envelope mr-2"></i>
                                    Entre em Contato
                                </h3>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Email:</strong><br>
                                        <a href="mailto:<?= htmlspecialchars($privacyNotice['contact']['email']) ?>" class="text-white">
                                            <?= htmlspecialchars($privacyNotice['contact']['email']) ?>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>DPO:</strong><br>
                                        <?= htmlspecialchars($privacyNotice['contact']['dpo']) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Prazo de Resposta:</strong><br>
                                        <?= htmlspecialchars($privacyNotice['contact']['response_time']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="row mt-4">
                        <div class="col-lg-10 mx-auto">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title mb-0">
                                        <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                                        Informações Importantes
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($privacyNotice['important_notes'] as $note): ?>
                                    <div class="important-note">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <?= $note ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12 text-center action-buttons">
                            <a href="/privacy/portal" class="btn btn-primary btn-privacy">
                                <i class="fas fa-user-shield mr-2"></i>
                                Portal do Titular
                            </a>
                            <a href="/privacy/cookies" class="btn btn-info btn-privacy">
                                <i class="fas fa-cookie-bite mr-2"></i>
                                Gestão de Cookies
                            </a>
                            <a href="/login" class="btn btn-success btn-privacy">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Acessar Sistema
                            </a>
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
                        <strong>Renner Coatings</strong> - Sistema de Controle de Acesso
                    </div>
                    <div class="col-sm-6 text-right">
                        <strong>LGPD Compliant</strong> - Versão 1.0
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

    <?php
    // Incluir banner de cookies
    require_once __DIR__ . '/../components/cookie-banner.php';
    ?>
</body>
</html>