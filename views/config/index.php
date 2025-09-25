<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Configurações do Sistema - Controle de Acesso</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile'] ?? 'Porteiro') ?></span>
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
                                    <a href="/reports/profissionais-renner" class="nav-link">
                                        <i class="nav-icon fas fa-user-tie"></i>
                                        <p>Profissionais Renner</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/reports/visitantes" class="nav-link">
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
                        <?php if ($_SESSION['user_profile'] === 'administrador' || $_SESSION['user_profile'] === 'seguranca'): ?>
                        <li class="nav-item">
                            <a href="/config" class="nav-link active">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Configurações</p>
                            </a>
                        </li>
                        <?php endif; ?>
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
                                            <a href="#users" class="nav-link" data-section="users">
                                                <i class="fas fa-users-cog mr-2"></i> Usuários
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
                                            <button type="button" class="btn btn-primary btn-sm" onclick="showSiteModal()">
                                                <i class="fas fa-plus mr-1"></i>Novo Local
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Gerencie os locais físicos da empresa. Cada local pode ter setores específicos e horários de funcionamento.
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="sitesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Nome do Local</th>
                                                        <th>Endereço</th>
                                                        <th>Capacidade Total</th>
                                                        <th>Setores</th>
                                                        <th>Status</th>
                                                        <th width="120">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sitesTableBody">
                                                    <tr>
                                                        <td colspan="6" class="text-center">
                                                            <i class="fas fa-spinner fa-spin mr-2"></i>Carregando sites...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
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

                            <!-- Seção: Usuários -->
                            <div id="users" class="config-section">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-users-cog mr-2"></i>Gestão de Usuários
                                        </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="openUserModal()">
                                                <i class="fas fa-plus mr-1"></i>Novo Usuário
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Gerencie os usuários do sistema e suas permissões por perfil/role.
                                        </div>
                                        
                                        <!-- Tabela de Usuários -->
                                        <div class="table-responsive">
                                            <table id="usersTable" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nome</th>
                                                        <th>Email</th>
                                                        <th>Perfil/Role</th>
                                                        <th>Status</th>
                                                        <th>Último Login</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="usersTableBody">
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            <i class="fas fa-spinner fa-spin mr-2"></i>Carregando usuários...
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
                                        <form id="authPoliciesForm" onsubmit="event.preventDefault(); saveAuthPolicies();">
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
        
        // Validação CNPJ em tempo real
        $('#cnpj').on('blur', function() {
            validateCNPJ(this.value);
        });
        
        // Submissão do formulário de organização
        $('#organizationForm').on('submit', function(e) {
            e.preventDefault();
            saveOrganizationSettings();
        });
        
        // Inicializar seções
        loadOrganizationSettings();
        loadSites();
        loadRbacMatrix();
        loadUsers();
        loadAuthSettings();
        loadAuditLogs();
    });
    
    // Funções para carregar dados
    function loadOrganizationSettings() {
        fetch('/config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_organization'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const org = data.data || {};
                
                // Preencher campos do formulário com valores padrão
                document.getElementById('companyName').value = org.company_name || '';
                document.getElementById('cnpj').value = org.cnpj_formatted || org.cnpj || '';
                document.getElementById('timezone').value = org.timezone || 'America/Sao_Paulo';
                document.getElementById('locale').value = org.locale || 'pt-BR';
                
                // Carregar logo se existir
                if (org.logo_url) {
                    const logoPreview = document.getElementById('logoPreview');
                    const logoPlaceholder = document.getElementById('logoPlaceholder');
                    const removeLogo = document.getElementById('removeLogo');
                    
                    logoPreview.src = org.logo_url;
                    logoPreview.style.display = 'block';
                    logoPlaceholder.style.display = 'none';
                    removeLogo.style.display = 'block';
                } else {
                    // Garantir que está no estado inicial
                    const logoPreview = document.getElementById('logoPreview');
                    const logoPlaceholder = document.getElementById('logoPlaceholder');
                    const removeLogo = document.getElementById('removeLogo');
                    
                    logoPreview.style.display = 'none';
                    logoPlaceholder.style.display = 'block';
                    removeLogo.style.display = 'none';
                }
                
                console.log('✅ Configurações carregadas:', org);
            } else {
                console.warn('⚠️ Erro ao carregar configurações:', data.message);
                showAlert('Aviso: ' + (data.message || 'Configurações não encontradas'), 'info');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar configurações:', error);
            showAlert('Erro ao carregar configurações da organização', 'warning');
        });
    }
    
    function loadSites() {
        // Implementar carregamento dos sites
    }
    
    function loadRbacMatrix() {
        // Fazer request para buscar a matriz RBAC
        fetch('/config/rbac-matrix')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRbacMatrix(data.data);
            } else {
                console.error('Erro ao carregar matriz RBAC:', data.message);
                document.getElementById('rbacMatrix').innerHTML = `
                    <tr><td colspan="100%" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Erro ao carregar matriz RBAC: ${data.message}
                    </td></tr>
                `;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('rbacMatrix').innerHTML = `
                <tr><td colspan="100%" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Erro ao carregar matriz RBAC
                </td></tr>
            `;
        });
    }
    
    // Variáveis globais para a matriz RBAC
    let currentRbacMatrix = null;
    let rbacChanges = {};
    
    function renderRbacMatrix(data) {
        currentRbacMatrix = data;
        const tbody = document.getElementById('rbacMatrix').querySelector('tbody');
        
        if (!data.roles || !data.permissions) {
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center">Dados incompletos da matriz RBAC</td></tr>';
            return;
        }
        
        // Agrupar permissões por módulo
        const permissionsByModule = {};
        data.permissions.forEach(perm => {
            if (!permissionsByModule[perm.module]) {
                permissionsByModule[perm.module] = [];
            }
            permissionsByModule[perm.module].push(perm);
        });
        
        // Criar cabeçalho
        let html = `
            <tr class="table-primary">
                <th style="width: 300px;">Permissão</th>
                ${data.roles.map(role => `
                    <th class="text-center" style="width: 120px;">
                        <div class="d-flex flex-column align-items-center">
                            <strong>${role.name}</strong>
                            <small class="text-muted">${getRoleIcon(role.name)}</small>
                        </div>
                    </th>
                `).join('')}
            </tr>
        `;
        
        // Criar linhas por módulo
        Object.keys(permissionsByModule).forEach(module => {
            // Cabeçalho do módulo
            html += `
                <tr class="table-secondary">
                    <td colspan="${data.roles.length + 1}">
                        <strong><i class="${getModuleIcon(module)} mr-2"></i>${getModuleName(module)}</strong>
                    </td>
                </tr>
            `;
            
            // Permissões do módulo
            permissionsByModule[module].forEach(permission => {
                html += `
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <strong>${permission.description}</strong>
                                <small class="text-muted">${permission.key}</small>
                            </div>
                        </td>
                        ${data.roles.map(role => {
                            const hasPermission = data.matrix[role.id] && data.matrix[role.id].includes(permission.key);
                            const isAdminProtected = role.name === 'administrador' && permission.key.startsWith('config.');
                            
                            return `
                                <td class="text-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" 
                                               class="custom-control-input rbac-checkbox" 
                                               id="rbac_${role.id}_${permission.id}"
                                               data-role-id="${role.id}"
                                               data-permission-key="${permission.key}"
                                               ${hasPermission ? 'checked' : ''}
                                               ${isAdminProtected ? 'disabled title="Admin sempre mantém permissões de config"' : ''}
                                               onchange="onRbacChange(${role.id}, '${permission.key}', this.checked)">
                                        <label class="custom-control-label" for="rbac_${role.id}_${permission.id}"></label>
                                    </div>
                                </td>
                            `;
                        }).join('')}
                    </tr>
                `;
            });
        });
        
        tbody.innerHTML = html;
        
        // Limpar mudanças pendentes
        rbacChanges = {};
        updateSaveButtonState();
    }
    
    // Ícones para roles
    function getRoleIcon(roleName) {
        const icons = {
            'administrador': '👑',
            'seguranca': '🛡️',  
            'recepcao': '📞',
            'rh': '👥',
            'porteiro': '🚪'
        };
        return icons[roleName] || '👤';
    }
    
    // Ícones para módulos
    function getModuleIcon(module) {
        const icons = {
            'config': 'fas fa-cogs',
            'reports': 'fas fa-chart-bar',
            'access': 'fas fa-sign-in-alt',
            'audit': 'fas fa-history',
            'users': 'fas fa-users',
            'privacy': 'fas fa-shield-alt'
        };
        return icons[module] || 'fas fa-circle';
    }
    
    // Nomes para módulos
    function getModuleName(module) {
        const names = {
            'config': 'CONFIGURAÇÕES',
            'reports': 'RELATÓRIOS',
            'access': 'CONTROLE DE ACESSO',
            'audit': 'AUDITORIA',
            'users': 'GESTÃO DE USUÁRIOS', 
            'privacy': 'PRIVACIDADE (LGPD)'
        };
        return names[module] || module.toUpperCase();
    }
    
    // Handler para mudanças nos checkboxes
    function onRbacChange(roleId, permissionKey, isChecked) {
        if (!rbacChanges[roleId]) {
            rbacChanges[roleId] = new Set(currentRbacMatrix.matrix[roleId] || []);
        }
        
        if (isChecked) {
            rbacChanges[roleId].add(permissionKey);
        } else {
            rbacChanges[roleId].delete(permissionKey);
        }
        
        updateSaveButtonState();
    }
    
    // Atualizar estado do botão salvar
    function updateSaveButtonState() {
        const hasChanges = Object.keys(rbacChanges).length > 0;
        const saveBtn = document.querySelector('button[onclick="saveRbacMatrix()"]');
        
        if (hasChanges) {
            saveBtn.classList.remove('btn-success');
            saveBtn.classList.add('btn-warning');
            saveBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Salvar Alterações *';
        } else {
            saveBtn.classList.remove('btn-warning'); 
            saveBtn.classList.add('btn-success');
            saveBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Salvar Alterações';
        }
    }
    
    function loadAuthSettings() {
        fetch('/config/auth-policies')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const policies = data.data;
                    
                    // Preencher campos do formulário
                    document.getElementById('passwordMinLength').value = policies.password_min_length || 8;
                    document.getElementById('passwordExpiryDays').value = policies.password_expiry_days || 90;
                    document.getElementById('sessionTimeout').value = policies.session_timeout_minutes || 1440;
                    document.getElementById('require2fa').checked = policies.require_2fa || false;
                    document.getElementById('enableSso').checked = policies.enable_sso || false;
                    document.getElementById('ssoProvider').value = policies.sso_provider || '';
                    document.getElementById('ssoClientId').value = policies.sso_client_id || '';
                    document.getElementById('ssoIssuer').value = policies.sso_issuer || '';
                    
                    // Mostrar/ocultar campos SSO
                    toggleSsoFields();
                } else {
                    showAlert('Erro ao carregar configurações de autenticação: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao carregar configurações de autenticação', 'danger');
            });
    }
    
    function saveAuthPolicies() {
        const form = document.getElementById('authPoliciesForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Preparar dados
        const formData = {
            password_min_length: parseInt(document.getElementById('passwordMinLength').value),
            password_expiry_days: parseInt(document.getElementById('passwordExpiryDays').value),
            session_timeout_minutes: parseInt(document.getElementById('sessionTimeout').value),
            require_2fa: document.getElementById('require2fa').checked,
            enable_sso: document.getElementById('enableSso').checked,
            sso_provider: document.getElementById('ssoProvider').value || null,
            sso_client_id: document.getElementById('ssoClientId').value || null,
            sso_issuer: document.getElementById('ssoIssuer').value || null
        };
        
        // Indicador de carregamento
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
        submitBtn.disabled = true;
        
        // Enviar dados
        fetch('/config/auth-policies', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                showAlert('Políticas de autenticação salvas com sucesso!', 'success');
            } else {
                showAlert(data.message || 'Erro ao salvar políticas', 'danger');
            }
        })
        .catch(error => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar políticas de autenticação', 'danger');
        });
    }
    
    function loadAuditLogs() {
        const filters = {
            user_id: document.getElementById('filterUser').value,
            entity: document.getElementById('filterEntity').value,
            action: document.getElementById('filterAction').value,
            date_start: document.getElementById('filterDateStart').value,
            date_end: document.getElementById('filterDateEnd').value
        };
        
        // Construir query string
        const queryParams = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                queryParams.append(key, filters[key]);
            }
        });
        
        // Indicador de carregamento
        const tableBody = document.querySelector('#auditTable tbody');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando logs...</td></tr>';
        
        fetch(`/config/audit?${queryParams.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const logs = data.data.logs || [];
                    let html = '';
                    
                    if (logs.length === 0) {
                        html = '<tr><td colspan="6" class="text-center text-muted">Nenhum log encontrado</td></tr>';
                    } else {
                        logs.forEach(log => {
                            const timestamp = new Date(log.timestamp).toLocaleString('pt-BR');
                            const details = log.dados_depois ? JSON.stringify(JSON.parse(log.dados_depois)) : '';
                            
                            html += `
                                <tr>
                                    <td>${timestamp}</td>
                                    <td>${log.usuario_nome || 'Sistema'}</td>
                                    <td><span class="badge badge-${getActionBadgeClass(log.acao)}">${log.acao}</span></td>
                                    <td>${log.entidade}</td>
                                    <td><small>${details.length > 100 ? details.substring(0, 100) + '...' : details}</small></td>
                                    <td>${log.ip_address || ''}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    tableBody.innerHTML = html;
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar logs: ' + data.message + '</td></tr>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar logs de auditoria</td></tr>';
            });
    }
    
    function getActionBadgeClass(action) {
        switch(action) {
            case 'create': return 'success';
            case 'update': return 'warning';
            case 'delete': return 'danger';
            default: return 'info';
        }
    }
    
    function toggleSsoFields() {
        const checkbox = document.getElementById('enableSso');
        const fields = document.getElementById('ssoFields');
        fields.style.display = checkbox.checked ? 'block' : 'none';
    }
    
    function handleLogoUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Verificar se é uma imagem válida
            if (!file.type.startsWith('image/')) {
                showAlert('Por favor, selecione apenas arquivos de imagem (JPG, PNG)', 'warning');
                input.value = ''; // Limpar seleção
                return;
            }
            
            // Verificar tamanho máximo (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showAlert('O arquivo deve ter no máximo 2MB', 'warning');
                input.value = ''; // Limpar seleção
                return;
            }
            
            // Mostrar preview temporário enquanto carrega
            const logoPreview = document.getElementById('logoPreview');
            const logoPlaceholder = document.getElementById('logoPlaceholder');
            
            // Criar URL temporária para preview
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                logoPreview.style.display = 'block';
                logoPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
            
            // Criar FormData para upload
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('action', 'upload_logo');
            
            // Adicionar token CSRF
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
            
            // Mostrar indicador de progresso
            const progressBar = document.getElementById('logoUploadProgress');
            progressBar.style.display = 'block';
            const progress = progressBar.querySelector('.progress-bar');
            progress.style.width = '50%';
            
            // Enviar via AJAX
            fetch('/config', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                progress.style.width = '100%';
                setTimeout(() => {
                    progressBar.style.display = 'none';
                    progress.style.width = '0%';
                }, 1000);
                
                if (data.success) {
                    // Atualizar preview com URL real
                    logoPreview.src = data.data.url + '?' + new Date().getTime(); // Cache bust
                    
                    // Mostrar botão de remover
                    const removeBtn = document.getElementById('removeLogo');
                    if (removeBtn) removeBtn.style.display = 'block';
                    
                    // Atualizar label do input
                    const fileLabel = input.nextElementSibling;
                    fileLabel.textContent = file.name;
                    
                    showAlert('Logo atualizada com sucesso!', 'success');
                } else {
                    showAlert(data.error || 'Erro ao fazer upload da logo', 'danger');
                    // Reverter preview em caso de erro
                    logoPreview.style.display = 'none';
                    logoPlaceholder.style.display = 'block';
                }
            })
            .catch(error => {
                progressBar.style.display = 'none';
                progress.style.width = '0%';
                console.error('Erro:', error);
                showAlert('Erro ao fazer upload da logo', 'danger');
                // Reverter preview em caso de erro
                logoPreview.style.display = 'none';
                logoPlaceholder.style.display = 'block';
            });
        }
    }
    
    function removeLogo() {
        if (!confirm('Tem certeza que deseja remover a logo?')) {
            return;
        }
        
        // Enviar requisição para remover logo
        const formData = new FormData();
        formData.append('action', 'remove_logo');
        
        fetch('/config', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Resetar preview da logo para default
                const logoPreview = document.getElementById('logoPreview');
                const logoPlaceholder = document.getElementById('logoPlaceholder');
                const removeBtn = document.getElementById('removeLogo');
                
                logoPreview.style.display = 'none';
                logoPlaceholder.style.display = 'block';
                removeBtn.style.display = 'none';
                
                showAlert('Logo removida com sucesso!', 'success');
            } else {
                showAlert(data.error || 'Erro ao remover logo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao remover logo', 'danger');
        });
    }
    
    // Validação CNPJ em tempo real
    function validateCNPJ(cnpj) {
        const cnpjInput = document.getElementById('cnpj');
        const cnpjError = document.getElementById('cnpjError');
        
        if (!cnpj || cnpj.trim() === '') {
            cnpjInput.classList.remove('is-invalid', 'is-valid');
            cnpjError.textContent = '';
            return;
        }
        
        // Validar no servidor
        fetch('/config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=validate_cnpj&cnpj=${encodeURIComponent(cnpj)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.valid) {
                cnpjInput.classList.remove('is-invalid');
                cnpjInput.classList.add('is-valid');
                cnpjError.textContent = '';
                
                // Aplicar formatação se retornada
                if (data.data.formatted) {
                    cnpjInput.value = data.data.formatted;
                }
            } else {
                cnpjInput.classList.remove('is-valid');
                cnpjInput.classList.add('is-invalid');
                cnpjError.textContent = 'CNPJ inválido';
            }
        })
        .catch(error => {
            console.error('Erro na validação CNPJ:', error);
        });
    }
    
    // Salvar configurações da organização
    function saveOrganizationSettings() {
        const form = document.getElementById('organizationForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Validação básica
        const companyName = document.getElementById('companyName').value.trim();
        if (!companyName) {
            showAlert('Nome da empresa é obrigatório', 'warning');
            return;
        }
        
        // Preparar dados
        const formData = {
            action: 'save_organization',
            company_name: companyName,
            cnpj: document.getElementById('cnpj').value.trim(),
            timezone: document.getElementById('timezone').value,
            locale: document.getElementById('locale').value
        };
        
        // Indicador de carregamento
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitBtn.disabled = true;
        
        // Enviar dados
        fetch('/config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: Object.keys(formData).map(key => 
                encodeURIComponent(key) + '=' + encodeURIComponent(formData[key])
            ).join('&')
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                showAlert('Configurações salvas com sucesso!', 'success');
                
                // Recarregar dados para confirmar salvamento
                setTimeout(() => {
                    loadOrganizationSettings();
                }, 1000);
            } else {
                showAlert(data.message || 'Erro ao salvar configurações', 'danger');
            }
        })
        .catch(error => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar configurações', 'danger');
        });
    }
    
    // Função auxiliar para mostrar alertas
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        // Inserir direto no body para garantir que apareça acima de tudo
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-remove após 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                alerts[alerts.length - 1].remove(); // Remove o último alerta
            }
        }, 5000);
    }
    
    // ========== USERS MANAGEMENT ==========
    
    let currentUsers = [];
    let availableRoles = [];
    let editingUserId = null;
    
    // Carregar usuários do sistema
    function loadUsers() {
        const tableBody = document.getElementById('usersTableBody');
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando usuários...</td></tr>';
        
        fetchWithCSRF('/config/users', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUsers = data.users || [];
                availableRoles = data.roles || [];
                renderUsersTable();
                populateRoleSelect();
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar usuários</td></tr>';
                console.error('Erro:', data.message);
            }
        })
        .catch(error => {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar usuários</td></tr>';
            console.error('Erro:', error);
        });
    }
    
    // Renderizar tabela de usuários
    function renderUsersTable() {
        const tableBody = document.getElementById('usersTableBody');
        
        if (currentUsers.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Nenhum usuário cadastrado</td></tr>';
            return;
        }
        
        const rows = currentUsers.map(user => {
            const statusBadge = user.ativo ? 
                '<span class="badge badge-success">Ativo</span>' : 
                '<span class="badge badge-secondary">Inativo</span>';
                
            const lastLogin = user.ultimo_login ? 
                new Date(user.ultimo_login).toLocaleDateString('pt-BR') : 
                '<span class="text-muted">Nunca</span>';
                
            const roleInfo = availableRoles.find(r => r.id == user.role_id);
            const roleName = roleInfo ? roleInfo.name : user.perfil || 'N/A';
            
            return `
                <tr>
                    <td>${user.id}</td>
                    <td>${user.nome}</td>
                    <td>${user.email}</td>
                    <td>
                        <span class="badge badge-info">${roleName}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>${lastLogin}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleUserStatus(${user.id})" title="${user.ativo ? 'Desativar' : 'Ativar'}">
                                <i class="fas fa-${user.ativo ? 'user-slash' : 'user-check'}"></i>
                            </button>
                            ${user.id != 1 ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="resetUserPassword(${user.id})" title="Reset senha">
                                <i class="fas fa-key"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        tableBody.innerHTML = rows;
    }
    
    // Preencher select de roles
    function populateRoleSelect() {
        const select = document.getElementById('userRole');
        const currentOptions = select.innerHTML;
        
        let options = '<option value="">Selecione um perfil...</option>';
        availableRoles.forEach(role => {
            options += `<option value="${role.id}">${role.name} - ${role.description}</option>`;
        });
        
        select.innerHTML = options;
    }
    
    // Abrir modal para novo usuário
    function openUserModal(userId = null) {
        editingUserId = userId;
        
        // Reset form
        document.getElementById('userForm').reset();
        document.getElementById('userActive').checked = true;
        document.getElementById('sendEmailNotification').checked = true;
        document.getElementById('userAuditInfo').style.display = 'none';
        
        if (userId) {
            // Modo edição
            const user = currentUsers.find(u => u.id == userId);
            if (user) {
                document.getElementById('userModalTitle').textContent = 'Editar Usuário';
                document.getElementById('userName').value = user.nome;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userRole').value = user.role_id || '';
                document.getElementById('userActive').checked = user.ativo;
                
                // Mostrar informações de auditoria
                document.getElementById('userAuditInfo').style.display = 'block';
                document.getElementById('userCreatedAt').textContent = new Date(user.data_criacao).toLocaleString('pt-BR');
                document.getElementById('userLastLogin').textContent = user.ultimo_login ? 
                    new Date(user.ultimo_login).toLocaleString('pt-BR') : 'Nunca';
                
                // Senha não obrigatória na edição
                document.getElementById('userPassword').required = false;
                document.getElementById('userPassword').placeholder = 'Deixe em branco para manter a senha atual';
                
                // Esconder opção de envio de email na edição
                document.getElementById('sendEmailGroup').style.display = 'none';
            }
        } else {
            // Modo criação
            document.getElementById('userModalTitle').textContent = 'Novo Usuário';
            document.getElementById('userPassword').required = true;
            document.getElementById('userPassword').placeholder = 'Mínimo 6 caracteres';
            document.getElementById('sendEmailGroup').style.display = 'block';
        }
        
        $('#userModal').modal('show');
    }
    
    // Salvar usuário
    function saveUser() {
        const form = document.getElementById('userForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const userData = {
            nome: document.getElementById('userName').value,
            email: document.getElementById('userEmail').value,
            role_id: document.getElementById('userRole').value,
            senha: document.getElementById('userPassword').value,
            ativo: document.getElementById('userActive').checked,
            send_email: document.getElementById('sendEmailNotification').checked
        };
        
        const saveBtn = document.getElementById('saveUserBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Salvando...';
        saveBtn.disabled = true;
        
        const url = editingUserId ? `/config/users/${editingUserId}` : '/config/users';
        const method = editingUserId ? 'PUT' : 'POST';
        
        fetchWithCSRF(url, {
            method: method,
            body: JSON.stringify(userData)
        })
        .then(response => response.json())
        .then(data => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
            if (data.success) {
                $('#userModal').modal('hide');
                showAlert(editingUserId ? 'Usuário atualizado com sucesso!' : 'Usuário criado com sucesso!', 'success');
                loadUsers(); // Recarregar lista
            } else {
                showAlert('Erro: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar usuário', 'danger');
        });
    }
    
    // Editar usuário
    function editUser(userId) {
        openUserModal(userId);
    }
    
    // Alternar status do usuário
    function toggleUserStatus(userId) {
        const user = currentUsers.find(u => u.id == userId);
        if (!user) return;
        
        const action = user.ativo ? 'desativar' : 'ativar';
        const confirmMessage = `Tem certeza que deseja ${action} o usuário "${user.nome}"?`;
        
        if (!confirm(confirmMessage)) return;
        
        fetchWithCSRF(`/config/users/${userId}/toggle-status`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(`Usuário ${action}do com sucesso!`, 'success');
                loadUsers(); // Recarregar lista
            } else {
                showAlert('Erro: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao alterar status do usuário', 'danger');
        });
    }
    
    // Reset password do usuário
    function resetUserPassword(userId) {
        const user = currentUsers.find(u => u.id == userId);
        if (!user) return;
        
        const confirmMessage = `Tem certeza que deseja resetar a senha do usuário "${user.nome}"?\nUma nova senha será gerada e enviada por email.`;
        
        if (!confirm(confirmMessage)) return;
        
        fetchWithCSRF(`/config/users/${userId}/reset-password`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Senha resetada com sucesso! Nova senha enviada por email.', 'success');
            } else {
                showAlert('Erro: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao resetar senha', 'danger');
        });
    }
    
    // Gerar senha aleatória
    function generatePassword() {
        const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*';
        let password = '';
        for (let i = 0; i < 8; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('userPassword').value = password;
    }
    
    // Alternar visibilidade da senha
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('userPassword');
        const toggleIcon = document.getElementById('passwordToggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            toggleIcon.className = 'fas fa-eye';
        }
    }
    
    // ========== SITES MANAGEMENT ==========
    
    let currentSites = [];
    let editingSiteId = null;
    
    // Helper para requisições com CSRF
    function fetchWithCSRF(url, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Default options
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        };
        
        // Merge options
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };
        
        return fetch(url, mergedOptions);
    }
    
    // Carregar sites na inicialização
    function loadSites() {
        fetch('/config?action=sites')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentSites = data.data;
                renderSitesTable(currentSites);
            } else {
                console.error('Erro ao carregar sites:', data.message);
                document.getElementById('sitesTableBody').innerHTML = `
                    <tr><td colspan="6" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Erro ao carregar sites: ${data.message}
                    </td></tr>
                `;
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            document.getElementById('sitesTableBody').innerHTML = `
                <tr><td colspan="6" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Erro de conexão
                </td></tr>
            `;
        });
    }
    
    // Renderizar tabela de sites
    function renderSitesTable(sites) {
        const tbody = document.getElementById('sitesTableBody');
        
        if (sites.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i><br>
                        Nenhum local cadastrado. Clique em "Novo Local" para começar.
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = sites.map(site => `
            <tr>
                <td>
                    <strong>${site.name}</strong>
                    <small class="d-block text-muted">ID: ${site.id}</small>
                </td>
                <td>
                    <span class="text-muted">${site.address || 'Não informado'}</span>
                </td>
                <td>
                    <span class="badge badge-info">${site.total_capacity || 0} pessoas</span>
                </td>
                <td>
                    <span class="badge badge-secondary">${site.sectors_count || 0} setores</span>
                </td>
                <td>
                    <span class="badge badge-${site.active ? 'success' : 'secondary'}">
                        ${site.active ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editSite(${site.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="manageSectors(${site.id})" title="Setores">
                            <i class="fas fa-list"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteSite(${site.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    // Modal para criar/editar site
    function showSiteModal(siteId = null) {
        editingSiteId = siteId;
        const modalTitle = siteId ? 'Editar Local' : 'Novo Local';
        const site = siteId ? currentSites.find(s => s.id == siteId) : {};
        
        document.getElementById('siteModalTitle').textContent = modalTitle;
        document.getElementById('siteName').value = site.name || '';
        document.getElementById('siteAddress').value = site.address || '';
        document.getElementById('siteCapacity').value = site.capacity || 0;
        document.getElementById('siteActive').checked = site.active !== false;
        
        $('#siteModal').modal('show');
    }
    
    function editSite(siteId) {
        showSiteModal(siteId);
    }
    
    // Salvar site
    function saveSite() {
        const form = document.getElementById('siteForm');
        const submitBtn = document.getElementById('saveSiteBtn');
        const originalText = submitBtn.innerHTML;
        
        // Validação
        const name = document.getElementById('siteName').value.trim();
        if (!name) {
            showAlert('Nome do local é obrigatório', 'warning');
            return;
        }
        
        const capacity = parseInt(document.getElementById('siteCapacity').value) || 0;
        if (capacity < 0) {
            showAlert('Capacidade deve ser maior ou igual a zero', 'warning');
            return;
        }
        
        // Preparar dados
        const siteData = {
            name: name,
            address: document.getElementById('siteAddress').value.trim(),
            capacity: capacity,
            active: document.getElementById('siteActive').checked
        };
        
        // Loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitBtn.disabled = true;
        
        // Endpoint e método
        const url = editingSiteId ? `/config?action=sites&id=${editingSiteId}` : '/config?action=sites';
        const method = editingSiteId ? 'PUT' : 'POST';
        
        fetchWithCSRF(url, {
            method: method,
            body: JSON.stringify(siteData)
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                showAlert(editingSiteId ? 'Local atualizado!' : 'Local criado com sucesso!', 'success');
                $('#siteModal').modal('hide');
                loadSites(); // Recarregar lista
            } else {
                showAlert(data.message || 'Erro ao salvar local', 'danger');
            }
        })
        .catch(error => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar local', 'danger');
        });
    }
    
    // Excluir site
    function deleteSite(siteId) {
        const site = currentSites.find(s => s.id == siteId);
        if (!site) return;
        
        if (confirm(`Tem certeza que deseja excluir o local "${site.name}"?`)) {
            fetchWithCSRF(`/config?action=sites&id=${siteId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Local excluído com sucesso!', 'success');
                    loadSites();
                } else {
                    showAlert(data.message || 'Erro ao excluir local', 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao excluir local', 'danger');
            });
        }
    }
    
    // ========== SETORES MANAGEMENT ==========
    
    let currentSectors = [];
    let managingSiteId = null;
    let editingSectorId = null;
    
    function manageSectors(siteId) {
        managingSiteId = siteId;
        const site = currentSites.find(s => s.id == siteId);
        
        document.getElementById('sectorsModalTitle').textContent = `Setores - ${site.name}`;
        
        loadSectors(siteId);
        $('#sectorsModal').modal('show');
    }
    
    // Carregar setores de um site
    function loadSectors(siteId) {
        document.getElementById('sectorsTableBody').innerHTML = `
            <tr><td colspan="4" class="text-center">
                <i class="fas fa-spinner fa-spin mr-2"></i>Carregando setores...
            </td></tr>
        `;
        
        fetch(`/config?action=sectors&site_id=${siteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentSectors = data.data;
                renderSectorsTable(currentSectors);
            } else {
                console.error('Erro ao carregar setores:', data.message);
                document.getElementById('sectorsTableBody').innerHTML = `
                    <tr><td colspan="4" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Erro ao carregar setores: ${data.message}
                    </td></tr>
                `;
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            document.getElementById('sectorsTableBody').innerHTML = `
                <tr><td colspan="4" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Erro de conexão
                </td></tr>
            `;
        });
    }
    
    // Renderizar tabela de setores
    function renderSectorsTable(sectors) {
        const tbody = document.getElementById('sectorsTableBody');
        
        if (sectors.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        <i class="fas fa-list fa-2x mb-2"></i><br>
                        Nenhum setor cadastrado. Clique em "Novo Setor" para começar.
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = sectors.map(sector => `
            <tr>
                <td>
                    <strong>${sector.name}</strong>
                    <small class="d-block text-muted">ID: ${sector.id}</small>
                </td>
                <td>
                    <span class="badge badge-info">${sector.capacity || 0} pessoas</span>
                </td>
                <td>
                    <span class="badge badge-${sector.active ? 'success' : 'secondary'}">
                        ${sector.active ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editSector(${sector.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteSector(${sector.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    // Modal para criar/editar setor
    function showSectorModal(sectorId = null) {
        editingSectorId = sectorId;
        const modalTitle = sectorId ? 'Editar Setor' : 'Novo Setor';
        const sector = sectorId ? currentSectors.find(s => s.id == sectorId) : {};
        
        document.getElementById('sectorModalTitle').textContent = modalTitle;
        document.getElementById('sectorName').value = sector.name || '';
        document.getElementById('sectorCapacity').value = sector.capacity || 0;
        document.getElementById('sectorActive').checked = sector.active !== false;
        
        $('#sectorModal').modal('show');
    }
    
    function editSector(sectorId) {
        showSectorModal(sectorId);
    }
    
    // Salvar setor
    function saveSector() {
        const submitBtn = document.getElementById('saveSectorBtn');
        const originalText = submitBtn.innerHTML;
        
        // Validação
        const name = document.getElementById('sectorName').value.trim();
        if (!name) {
            showAlert('Nome do setor é obrigatório', 'warning');
            return;
        }
        
        const capacity = parseInt(document.getElementById('sectorCapacity').value) || 0;
        if (capacity < 0) {
            showAlert('Capacidade deve ser maior ou igual a zero', 'warning');
            return;
        }
        
        // Preparar dados
        const sectorData = {
            site_id: managingSiteId,
            name: name,
            capacity: capacity,
            active: document.getElementById('sectorActive').checked
        };
        
        // Loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        submitBtn.disabled = true;
        
        // Endpoint e método
        const url = editingSectorId ? `/config?action=sectors&id=${editingSectorId}` : '/config?action=sectors';
        const method = editingSectorId ? 'PUT' : 'POST';
        
        fetchWithCSRF(url, {
            method: method,
            body: JSON.stringify(sectorData)
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            if (data.success) {
                showAlert(editingSectorId ? 'Setor atualizado!' : 'Setor criado com sucesso!', 'success');
                $('#sectorModal').modal('hide');
                loadSectors(managingSiteId); // Recarregar lista
                loadSites(); // Atualizar contadores na tabela principal
            } else {
                showAlert(data.message || 'Erro ao salvar setor', 'danger');
            }
        })
        .catch(error => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar setor', 'danger');
        });
    }
    
    // Excluir setor
    function deleteSector(sectorId) {
        const sector = currentSectors.find(s => s.id == sectorId);
        if (!sector) return;
        
        if (confirm(`Tem certeza que deseja excluir o setor "${sector.name}"?`)) {
            fetchWithCSRF(`/config?action=sectors&id=${sectorId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Setor excluído com sucesso!', 'success');
                    loadSectors(managingSiteId);
                    loadSites(); // Atualizar contadores
                } else {
                    showAlert(data.message || 'Erro ao excluir setor', 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao excluir setor', 'danger');
            });
        }
    }
    
    function showAddSiteModal() {
        showSiteModal();
    }
    
    function showRbacUsers() {
        // Buscar usuários por perfil
        fetch('/config/rbac-users')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsersModal(data.data);
            } else {
                showAlert('Erro ao carregar usuários: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao carregar usuários por perfil', 'danger');
        });
    }
    
    function saveRbacMatrix() {
        // Verificar se há mudanças
        if (Object.keys(rbacChanges).length === 0) {
            showAlert('Nenhuma alteração para salvar', 'info');
            return;
        }
        
        // Preparar dados das mudanças
        const changes = [];
        Object.keys(rbacChanges).forEach(roleId => {
            changes.push({
                role_id: parseInt(roleId),
                permissions: Array.from(rbacChanges[roleId])
            });
        });
        
        const saveBtn = document.querySelector('button[onclick="saveRbacMatrix()"]');
        const originalHtml = saveBtn.innerHTML;
        
        // Loading state
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Salvando...';
        saveBtn.disabled = true;
        
        // Enviar alterações com CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/config/role-permissions', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ changes: changes })
        })
        .then(response => response.json())
        .then(data => {
            saveBtn.innerHTML = originalHtml;
            saveBtn.disabled = false;
            
            if (data.success) {
                showAlert('Matriz RBAC atualizada com sucesso!', 'success');
                
                // Recarregar matriz para refletir mudanças
                setTimeout(() => {
                    loadRbacMatrix();
                }, 1000);
                
            } else {
                showAlert('Erro ao salvar: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            saveBtn.innerHTML = originalHtml;
            saveBtn.disabled = false;
            console.error('Erro:', error);
            showAlert('Erro ao salvar matriz RBAC', 'danger');
        });
    }
    
    // Renderizar modal de usuários
    function renderUsersModal(usersByRole) {
        let modalHtml = `
            <div class="modal fade" id="rbacUsersModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-users mr-2"></i>Usuários por Perfil
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
        `;
        
        Object.values(usersByRole).forEach(roleData => {
            const role = roleData.role;
            const users = roleData.users;
            
            modalHtml += `
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <span class="mr-2">${getRoleIcon(role.name)}</span>
                            <strong>${role.name}</strong>
                            <span class="badge badge-secondary ml-2">${users.length} usuário(s)</span>
                        </h6>
                        <small class="text-muted">${role.description}</small>
                    </div>
                    <div class="card-body">
            `;
            
            if (users.length === 0) {
                modalHtml += '<p class="text-muted mb-0">Nenhum usuário com este perfil</p>';
            } else {
                modalHtml += `
                    <div class="row">
                        ${users.map(user => `
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle text-muted mr-2"></i>
                                    <div>
                                        <strong>${user.name}</strong>
                                        <br><small class="text-muted">${user.email}</small>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }
            
            modalHtml += `
                    </div>
                </div>
            `;
        });
        
        modalHtml += `
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal existente e adicionar novo
        const existingModal = document.getElementById('rbacUsersModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        $('#rbacUsersModal').modal('show');
    }
    
    function exportAuditLogs() {
        const filters = {
            user_id: document.getElementById('filterUser').value,
            entity: document.getElementById('filterEntity').value,
            action: document.getElementById('filterAction').value,
            date_start: document.getElementById('filterDateStart').value,
            date_end: document.getElementById('filterDateEnd').value
        };
        
        // Construir query string
        const queryParams = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                queryParams.append(key, filters[key]);
            }
        });
        
        // Baixar o arquivo
        const url = `/config/audit/export?${queryParams.toString()}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showAlert('Exportação iniciada. O download será iniciado em breve.', 'info');
    }
    </script>

    <!-- Modal: Site -->
    <div class="modal fade" id="siteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="siteModalTitle">Novo Local</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="siteForm" onsubmit="event.preventDefault(); saveSite();">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="siteName">Nome do Local *</label>
                            <input type="text" class="form-control" id="siteName" required maxlength="100"
                                   placeholder="Ex: Matriz São Paulo">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="siteAddress">Endereço</label>
                            <textarea class="form-control" id="siteAddress" rows="3"
                                      placeholder="Endereço completo do local"></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="siteCapacity">Capacidade Total (pessoas)</label>
                            <input type="number" class="form-control" id="siteCapacity" min="0" max="99999" value="0">
                            <small class="form-text text-muted">Capacidade máxima de pessoas no local</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="siteActive" checked>
                                <label class="form-check-label" for="siteActive">
                                    Local ativo (disponível para controle de acesso)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveSiteBtn">
                            <i class="fas fa-save mr-1"></i>Salvar Local
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Setores -->
    <div class="modal fade" id="sectorsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectorsModalTitle">Setores</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Lista de Setores</h6>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showSectorModal()">
                            <i class="fas fa-plus mr-1"></i>Novo Setor
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Nome do Setor</th>
                                    <th>Capacidade</th>
                                    <th>Status</th>
                                    <th width="100">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="sectorsTableBody">
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Carregando setores...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Setor Individual -->
    <div class="modal fade" id="sectorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectorModalTitle">Novo Setor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="sectorForm" onsubmit="event.preventDefault(); saveSector();">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="sectorName">Nome do Setor *</label>
                            <input type="text" class="form-control" id="sectorName" required maxlength="100"
                                   placeholder="Ex: Administração, Produção, etc.">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="sectorCapacity">Capacidade (pessoas)</label>
                            <input type="number" class="form-control" id="sectorCapacity" min="0" max="9999" value="0">
                            <small class="form-text text-muted">Capacidade máxima de pessoas neste setor</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="sectorActive" checked>
                                <label class="form-check-label" for="sectorActive">
                                    Setor ativo (disponível para controle de acesso)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveSectorBtn">
                            <i class="fas fa-save mr-1"></i>Salvar Setor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Gestão de Usuários -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Novo Usuário</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="userForm" onsubmit="event.preventDefault(); saveUser();">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userName">Nome Completo *</label>
                                    <input type="text" class="form-control" id="userName" required maxlength="100"
                                           placeholder="Digite o nome completo">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userEmail">Email *</label>
                                    <input type="email" class="form-control" id="userEmail" required maxlength="150"
                                           placeholder="usuario@empresa.com">
                                    <small class="form-text text-muted">Email será usado para login</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userRole">Perfil/Role *</label>
                                    <select class="form-control" id="userRole" required>
                                        <option value="">Selecione um perfil...</option>
                                        <!-- Será preenchido via JavaScript -->
                                    </select>
                                    <small class="form-text text-muted">Perfil determina as permissões do usuário</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userPassword">Senha *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="userPassword" 
                                               placeholder="Mínimo 6 caracteres">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Gerar senha aleatória">
                                                <i class="fas fa-dice"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()" title="Mostrar/ocultar senha">
                                                <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Mínimo 6 caracteres</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="userActive" checked>
                                        <label class="form-check-label" for="userActive">
                                            <strong>Usuário ativo</strong>
                                        </label>
                                        <small class="form-text text-muted">Usuários inativos não podem fazer login</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" id="sendEmailGroup">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="sendEmailNotification" checked>
                                        <label class="form-check-label" for="sendEmailNotification">
                                            Enviar credenciais por email
                                        </label>
                                        <small class="form-text text-muted">Notifica o usuário sobre a criação da conta</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações de Auditoria (apenas na edição) -->
                        <div id="userAuditInfo" style="display: none;">
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Criado em:</strong> <span id="userCreatedAt">-</span>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Último login:</strong> <span id="userLastLogin">Nunca</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">
                            <i class="fas fa-save mr-1"></i>Salvar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>