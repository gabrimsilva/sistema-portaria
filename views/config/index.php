<?php
require_once __DIR__ . '/../../src/middleware/auth.php';
require_once __DIR__ . '/../../src/services/ConfigService.php';
require_once __DIR__ . '/../../src/services/RbacService.php';

$configService = new ConfigService();
$rbacService = new RbacService();

// Verificar permissões
if (!$rbacService->checkPermission($_SESSION['user_id'], 'view_system_settings')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurações do Sistema - Controle de Acesso</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    
    <style>
    .config-section { display: none; }
    .config-section.active { display: block; }
    .nav-link.active { background-color: #007bff !important; color: white !important; }
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
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile'] ?? 'Administrador') ?></span>
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
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Relatórios
                                    <i class="fas fa-angle-left right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/relatorios/funcionarios" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Profissionais Renner</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/visitantes-novo" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Visitantes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/prestadores-servico" class="nav-link">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Prestador de Serviços</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="/config" class="nav-link active">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Configurações</p>
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
                            <h1>Configurações do Sistema</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Configurações</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Menu de Configurações -->
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Menu de Configurações</h3>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="nav nav-pills flex-column">
                                        <li class="nav-item">
                                            <a href="#organization" class="nav-link active" data-section="organization">
                                                <i class="fas fa-building mr-2"></i> Organização
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#sites" class="nav-link" data-section="sites">
                                                <i class="fas fa-map-marker-alt mr-2"></i> Locais/Sites
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#rbac" class="nav-link" data-section="rbac">
                                                <i class="fas fa-user-shield mr-2"></i> Permissões (RBAC)
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#auth" class="nav-link" data-section="auth">
                                                <i class="fas fa-lock mr-2"></i> Autenticação
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#audit" class="nav-link" data-section="audit">
                                                <i class="fas fa-history mr-2"></i> Auditoria
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conteúdo Principal -->
                        <div class="col-md-9">
                            
                            <!-- Seção: Organização -->
                            <div id="organization" class="config-section active">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-building mr-2"></i>Configurações da Organização
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="organizationForm">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label for="companyName">Nome da Empresa *</label>
                                                        <input type="text" class="form-control" id="companyName" 
                                                               name="company_name" placeholder="Digite o nome da empresa" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="cnpj">CNPJ</label>
                                                        <input type="text" class="form-control" id="cnpj" 
                                                               name="cnpj" placeholder="00.000.000/0000-00" data-mask="00.000.000/0000-00">
                                                        <div class="invalid-feedback" id="cnpjError"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="timezone">Fuso Horário</label>
                                                        <select class="form-control" id="timezone" name="timezone">
                                                            <option value="America/Sao_Paulo">Brasília (GMT-3)</option>
                                                            <option value="America/Manaus">Manaus (GMT-4)</option>
                                                            <option value="America/Rio_Branco">Rio Branco (GMT-5)</option>
                                                            <option value="America/Noronha">Fernando de Noronha (GMT-2)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="locale">Idioma</label>
                                                        <select class="form-control" id="locale" name="locale">
                                                            <option value="pt-BR">Português (Brasil)</option>
                                                            <option value="en-US">English (US)</option>
                                                            <option value="es-ES">Español</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label for="logoUpload">Logo da Empresa</label>
                                                        <div class="custom-file">
                                                            <input type="file" class="custom-file-input" id="logoUpload" 
                                                                   accept=".png,.jpg,.jpeg" onchange="handleLogoUpload(this)">
                                                            <label class="custom-file-label" for="logoUpload">Escolher arquivo...</label>
                                                        </div>
                                                        <small class="form-text text-muted">
                                                            Formatos aceitos: PNG, JPG. Tamanho máximo: 2MB. Recomendado: 200x60px.
                                                        </small>
                                                        <div class="progress mt-2" id="logoUploadProgress" style="display:none;">
                                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Preview</label>
                                                        <div style="border: 1px dashed #ccc; padding: 15px; text-align: center; min-height: 80px; background: #f9f9f9;">
                                                            <img id="logoPreview" style="max-width: 100%; max-height: 60px; display: none;" />
                                                            <div id="logoPlaceholder" class="text-muted">
                                                                <i class="fas fa-image fa-2x mb-2"></i><br>
                                                                Nenhuma imagem selecionada
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" 
                                                                id="removeLogo" style="display:none;" onclick="removeLogo()">
                                                            <i class="fas fa-times"></i> Remover Logo
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i>Salvar Configurações
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Sites/Locais -->
                            <div id="sites" class="config-section">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-map-marker-alt mr-2"></i>Locais e Sites
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="showAddSiteModal()">
                                                <i class="fas fa-plus mr-1"></i>Novo Local
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="sitesList">
                                            <!-- Sites serão carregados via JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: RBAC -->
                            <div id="rbac" class="config-section">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-user-shield mr-2"></i>Matriz de Permissões (RBAC)
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-info btn-sm mr-2" onclick="showRbacUsers()">
                                                <i class="fas fa-users mr-1"></i>Ver Usuários por Perfil
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" onclick="saveRbacMatrix()">
                                                <i class="fas fa-save mr-1"></i>Salvar Alterações
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Configure as permissões para cada perfil de usuário. O perfil <strong>Administrador</strong> 
                                            sempre mantém acesso total às configurações.
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered" id="rbacMatrix">
                                                <tbody>
                                                    <tr>
                                                        <td colspan="100%" class="text-center">
                                                            <i class="fas fa-spinner fa-spin mr-2"></i>Carregando matriz RBAC...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Autenticação -->
                            <div id="auth" class="config-section">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-lock mr-2"></i>Políticas de Autenticação
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="authPoliciesForm">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5>Políticas de Senha</h5>
                                                    <div class="form-group">
                                                        <label for="passwordMinLength">Comprimento Mínimo</label>
                                                        <input type="number" class="form-control" id="passwordMinLength" 
                                                               name="password_min_length" min="4" max="50" value="8">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="passwordExpiryDays">Expiração (dias)</label>
                                                        <input type="number" class="form-control" id="passwordExpiryDays" 
                                                               name="password_expiry_days" min="30" max="365" value="90">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>Sessão e Segurança</h5>
                                                    <div class="form-group">
                                                        <label for="sessionTimeout">Timeout de Sessão (minutos)</label>
                                                        <input type="number" class="form-control" id="sessionTimeout" 
                                                               name="session_timeout_minutes" min="15" max="1440" value="1440">
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="require2fa" name="require_2fa">
                                                            <label class="custom-control-label" for="require2fa">Exigir Autenticação de Dois Fatores (2FA)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <h5>Single Sign-On (SSO)</h5>
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="enableSso" 
                                                           name="enable_sso" onchange="toggleSsoFields()">
                                                    <label class="custom-control-label" for="enableSso">Habilitar SSO</label>
                                                </div>
                                            </div>
                                            
                                            <div id="ssoFields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="ssoProvider">Provedor</label>
                                                            <select class="form-control" id="ssoProvider" name="sso_provider">
                                                                <option value="">Selecione</option>
                                                                <option value="azure">Azure AD</option>
                                                                <option value="google">Google Workspace</option>
                                                                <option value="okta">Okta</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="ssoClientId">Client ID</label>
                                                            <input type="text" class="form-control" id="ssoClientId" name="sso_client_id">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="ssoIssuer">Issuer</label>
                                                            <input type="text" class="form-control" id="ssoIssuer" name="sso_issuer">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i>Salvar Políticas
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção: Auditoria -->
                            <div id="audit" class="config-section">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-history mr-2"></i>Logs de Auditoria
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-success btn-sm" onclick="exportAuditLogs()">
                                                <i class="fas fa-download mr-1"></i>Exportar CSV
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Filtros -->
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label for="filterUser">Usuário</label>
                                                <select class="form-control form-control-sm" id="filterUser" name="user_id">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="filterEntity">Entidade</label>
                                                <select class="form-control form-control-sm" id="filterEntity" name="entity">
                                                    <option value="">Todas</option>
                                                    <option value="usuarios">Usuários</option>
                                                    <option value="funcionarios">Funcionários</option>
                                                    <option value="visitantes">Visitantes</option>
                                                    <option value="prestadores_servico">Prestadores</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="filterAction">Ação</label>
                                                <select class="form-control form-control-sm" id="filterAction" name="action">
                                                    <option value="">Todas</option>
                                                    <option value="create">Criar</option>
                                                    <option value="update">Atualizar</option>
                                                    <option value="delete">Excluir</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="filterDateStart">De</label>
                                                <input type="date" class="form-control form-control-sm" id="filterDateStart" name="date_start">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="filterDateEnd">Até</label>
                                                <input type="date" class="form-control form-control-sm" id="filterDateEnd" name="date_end">
                                            </div>
                                            <div class="col-md-2">
                                                <label>&nbsp;</label><br>
                                                <button type="button" class="btn btn-primary btn-sm" onclick="loadAuditLogs()">
                                                    <i class="fas fa-search mr-1"></i>Filtrar
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="auditTable">
                                                <thead>
                                                    <tr>
                                                        <th>Data/Hora</th>
                                                        <th>Usuário</th>
                                                        <th>Ação</th>
                                                        <th>Entidade</th>
                                                        <th>Detalhes</th>
                                                        <th>IP</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="6" class="text-center">
                                                            <i class="fas fa-spinner fa-spin mr-2"></i>Carregando logs...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Navegação entre seções
        $('.nav-link[data-section]').click(function(e) {
            e.preventDefault();
            
            // Remove active de todos
            $('.nav-link[data-section]').removeClass('active');
            $('.config-section').removeClass('active');
            
            // Adiciona active no clicado
            $(this).addClass('active');
            const section = $(this).data('section');
            $('#' + section).addClass('active');
        });
        
        // Máscara CNPJ
        $('#cnpj').mask('00.000.000/0000-00');
        
        // Inicializar seções
        loadOrganizationSettings();
        loadSites();
        loadRbacMatrix();
        loadAuthSettings();
        loadAuditLogs();
    });
    
    // Funções para carregar dados
    function loadOrganizationSettings() {
        // Implementar carregamento das configurações da organização
    }
    
    function loadSites() {
        // Implementar carregamento dos sites
    }
    
    function loadRbacMatrix() {
        // Implementar carregamento da matriz RBAC
    }
    
    function loadAuthSettings() {
        // Implementar carregamento das configurações de autenticação
    }
    
    function loadAuditLogs() {
        // Implementar carregamento dos logs de auditoria
    }
    
    function toggleSsoFields() {
        const checkbox = document.getElementById('enableSso');
        const fields = document.getElementById('ssoFields');
        fields.style.display = checkbox.checked ? 'block' : 'none';
    }
    
    function handleLogoUpload(input) {
        // Implementar upload de logo
    }
    
    function removeLogo() {
        // Implementar remoção de logo
    }
    
    function showAddSiteModal() {
        // Implementar modal de adicionar site
    }
    
    function showRbacUsers() {
        // Implementar modal de usuários por perfil
    }
    
    function saveRbacMatrix() {
        // Implementar salvamento da matriz RBAC
    }
    
    function exportAuditLogs() {
        // Implementar exportação de logs
    }
    </script>
</body>
</html>