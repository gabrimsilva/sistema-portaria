<?php
$pageTitle = 'Configurações do Sistema';
$currentPage = 'config';
include '../views/includes/header.php';
?>

<!-- Estilos CSS customizados para configurações -->
<style>
.config-section {
    display: none;
}
.config-section.active {
    display: block;
}
.nav-pills .nav-link.active {
    background-color: #007bff;
    color: white;
}
.auto-save-indicator {
    font-size: 12px;
    color: #28a745;
    display: none;
}
.rbac-matrix-table {
    font-size: 13px;
}
.rbac-matrix-table th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}
.rbac-matrix-table .permission-checkbox {
    margin: 0;
    transform: scale(1.2);
}
.module-group {
    background-color: #e9ecef;
    font-weight: bold;
    color: #495057;
}
.audit-filters {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.pagination-info {
    color: #6c757d;
    font-size: 14px;
}
.btn-loading {
    position: relative;
    opacity: 0.7;
    pointer-events: none;
}
.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    margin: auto;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.site-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #ffffff;
}
.sector-item {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 8px 12px;
    margin: 4px;
    display: inline-block;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cogs"></i> Configurações do Sistema</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configurações</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Menu Lateral -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Seções</h3>
                        </div>
                        <div class="card-body p-0">
                            <nav class="nav nav-pills nav-sidebar flex-column">
                                <a href="#organization" class="nav-link active" data-section="organization">
                                    <i class="fas fa-building"></i> Organização
                                </a>
                                <a href="#sites" class="nav-link" data-section="sites">
                                    <i class="fas fa-map-marker-alt"></i> Locais/Sites
                                </a>
                                <a href="#rbac" class="nav-link" data-section="rbac">
                                    <i class="fas fa-user-shield"></i> Permissões (RBAC)
                                </a>
                                <a href="#auth" class="nav-link" data-section="auth">
                                    <i class="fas fa-lock"></i> Autenticação
                                </a>
                                <a href="#audit" class="nav-link" data-section="audit">
                                    <i class="fas fa-history"></i> Auditoria
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo Principal -->
                <div class="col-md-9">
                    
                    <!-- Seção: Organização -->
                    <div id="organization" class="config-section active">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-building"></i> Configurações da Organização</h3>
                                <div class="auto-save-indicator">
                                    <i class="fas fa-check-circle"></i> Auto-salvamento ativado
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="organizationForm">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="companyName">Nome da Empresa *</label>
                                                <input type="text" class="form-control" id="companyName" name="company_name" 
                                                       placeholder="Digite o nome da empresa" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="cnpj">CNPJ</label>
                                                <input type="text" class="form-control" id="cnpj" name="cnpj" 
                                                       placeholder="00.000.000/0000-00" data-mask="00.000.000/0000-00">
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
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="logoUrl">URL do Logo</label>
                                                <input type="url" class="form-control" id="logoUrl" name="logo_url" 
                                                       placeholder="https://exemplo.com/logo.png">
                                                <small class="form-text text-muted">
                                                    Recomendado: 200x60px, formato PNG ou SVG
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Sites/Locais -->
                    <div id="sites" class="config-section">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Locais e Sites</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="showAddSiteModal()">
                                        <i class="fas fa-plus"></i> Novo Local
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
                                <h3 class="card-title"><i class="fas fa-user-shield"></i> Matriz de Permissões (RBAC)</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-success btn-sm" onclick="saveRbacMatrix()">
                                        <i class="fas fa-save"></i> Salvar Alterações
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Configure as permissões para cada perfil de usuário. O perfil <strong>Administrador</strong> 
                                    sempre mantém acesso total às configurações.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm rbac-matrix-table" id="rbacMatrix">
                                        <!-- Matriz será carregada via JavaScript -->
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Autenticação -->
                    <div id="auth" class="config-section">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-lock"></i> Políticas de Autenticação</h3>
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
                                                    <label class="custom-control-label" for="require2fa">Exigir 2FA</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12">
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
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar Políticas
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
                                <h3 class="card-title"><i class="fas fa-history"></i> Logs de Auditoria</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-success btn-sm" onclick="exportAuditLogs()">
                                        <i class="fas fa-download"></i> Exportar CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Filtros -->
                                <div class="audit-filters">
                                    <form id="auditFiltersForm" class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label for="filterUser" class="form-label">Usuário</label>
                                                <select class="form-control form-control-sm" id="filterUser" name="user_id">
                                                    <option value="">Todos</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label for="filterEntity" class="form-label">Entidade</label>
                                                <select class="form-control form-control-sm" id="filterEntity" name="entity">
                                                    <option value="">Todas</option>
                                                    <option value="usuarios">Usuários</option>
                                                    <option value="funcionarios">Funcionários</option>
                                                    <option value="visitantes">Visitantes</option>
                                                    <option value="prestadores_servico">Prestadores</option>
                                                    <option value="organization_settings">Organização</option>
                                                    <option value="sites">Sites</option>
                                                    <option value="role_permissions">Permissões</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-2">
                                                <label for="filterAction" class="form-label">Ação</label>
                                                <select class="form-control form-control-sm" id="filterAction" name="action">
                                                    <option value="">Todas</option>
                                                    <option value="create">Criar</option>
                                                    <option value="update">Atualizar</option>
                                                    <option value="delete">Excluir</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-2">
                                                <label for="filterDateStart" class="form-label">De</label>
                                                <input type="date" class="form-control form-control-sm" id="filterDateStart" name="date_start">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-2">
                                                <label for="filterDateEnd" class="form-label">Até</label>
                                                <input type="date" class="form-control form-control-sm" id="filterDateEnd" name="date_end">
                                            </div>
                                        </div>
                                    </form>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="loadAuditLogs()">
                                                <i class="fas fa-search"></i> Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm ml-2" onclick="clearAuditFilters()">
                                                <i class="fas fa-times"></i> Limpar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabela de Logs -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>Usuário</th>
                                                <th>Ação</th>
                                                <th>Entidade</th>
                                                <th>ID</th>
                                                <th>Detalhes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="auditLogsTable">
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <i class="fas fa-spinner fa-spin"></i> Carregando logs...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="pagination-info" id="auditPaginationInfo"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <nav>
                                            <ul class="pagination pagination-sm justify-content-end" id="auditPagination">
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal: Adicionar Site -->
<div class="modal fade" id="addSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Local/Site</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addSiteForm">
                    <div class="form-group">
                        <label for="siteName">Nome do Local *</label>
                        <input type="text" class="form-control" id="siteName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="siteCapacity">Capacidade Máxima</label>
                        <input type="number" class="form-control" id="siteCapacity" name="capacity" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="siteAddress">Endereço</label>
                        <textarea class="form-control" id="siteAddress" name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="siteTimezone">Fuso Horário</label>
                        <select class="form-control" id="siteTimezone" name="timezone">
                            <option value="America/Sao_Paulo">Brasília (GMT-3)</option>
                            <option value="America/Manaus">Manaus (GMT-4)</option>
                            <option value="America/Rio_Branco">Rio Branco (GMT-5)</option>
                            <option value="America/Noronha">Fernando de Noronha (GMT-2)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveSite()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
$(document).ready(function() {
    // Inicialização
    initializeConfig();
    
    // Auto-save para organização
    setupAutoSave();
    
    // Navegação entre seções
    setupNavigation();
    
    // Carregar dados iniciais
    loadOrganizationData();
    loadSites();
    loadRbacMatrix();
    loadAuthPolicies();
    loadAuditLogs();
    loadUsers();
});

// ========== NAVEGAÇÃO ==========
function setupNavigation() {
    $('.nav-sidebar .nav-link').click(function(e) {
        e.preventDefault();
        
        // Remover active de todos
        $('.nav-sidebar .nav-link').removeClass('active');
        $('.config-section').removeClass('active');
        
        // Ativar o clicado
        $(this).addClass('active');
        const section = $(this).data('section');
        $('#' + section).addClass('active');
        
        // Atualizar URL sem recarregar
        window.history.pushState({}, '', '#' + section);
    });
    
    // Gerenciar URL hash
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        $(`.nav-link[data-section="${hash}"]`).click();
    }
}

// ========== ORGANIZAÇÃO ==========
function setupAutoSave() {
    $('#organizationForm input, #organizationForm select').on('change', function() {
        saveOrganizationData();
    });
    
    // Validação de CNPJ em tempo real
    $('#cnpj').on('blur', function() {
        validateCnpj($(this).val());
    });
}

function loadOrganizationData() {
    $.get('/config/organization')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                $('#companyName').val(data.company_name || '');
                $('#cnpj').val(data.cnpj_formatted || data.cnpj || '');
                $('#timezone').val(data.timezone || 'America/Sao_Paulo');
                $('#locale').val(data.locale || 'pt-BR');
                $('#logoUrl').val(data.logo_url || '');
            }
        })
        .fail(function() {
            showAlert('Erro ao carregar dados da organização', 'error');
        });
}

function saveOrganizationData() {
    const formData = {
        company_name: $('#companyName').val(),
        cnpj: $('#cnpj').val().replace(/\D/g, ''),
        timezone: $('#timezone').val(),
        locale: $('#locale').val(),
        logo_url: $('#logoUrl').val() || null
    };
    
    $.ajax({
        url: '/config/organization',
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('.auto-save-indicator').show().delay(2000).fadeOut();
        } else {
            showAlert(response.message || 'Erro ao salvar', 'error');
        }
    })
    .fail(function() {
        showAlert('Erro ao salvar dados da organização', 'error');
    });
}

function validateCnpj(cnpj) {
    if (!cnpj) return;
    
    $.post('/config/validate-cnpj', JSON.stringify({cnpj: cnpj}), function(response) {
        if (response.success) {
            if (response.data.valid) {
                $('#cnpj').removeClass('is-invalid').addClass('is-valid');
                $('#cnpjError').text('');
                if (response.data.formatted) {
                    $('#cnpj').val(response.data.formatted);
                }
            } else {
                $('#cnpj').removeClass('is-valid').addClass('is-invalid');
                $('#cnpjError').text('CNPJ inválido');
            }
        }
    }, 'json');
}

// ========== SITES ==========
function loadSites() {
    $.get('/config/sites')
        .done(function(response) {
            if (response.success) {
                renderSites(response.data);
            }
        })
        .fail(function() {
            $('#sitesList').html('<div class="alert alert-danger">Erro ao carregar sites</div>');
        });
}

function renderSites(sites) {
    if (sites.length === 0) {
        $('#sitesList').html(
            '<div class="text-center text-muted py-4">' +
            '<i class="fas fa-map-marker-alt fa-3x mb-3"></i>' +
            '<p>Nenhum local cadastrado ainda.</p>' +
            '<button class="btn btn-primary" onclick="showAddSiteModal()">Adicionar Primeiro Local</button>' +
            '</div>'
        );
        return;
    }
    
    let html = '';
    sites.forEach(function(site) {
        html += `
            <div class="site-card">
                <div class="row">
                    <div class="col-md-8">
                        <h5>${site.name}</h5>
                        <p class="text-muted mb-1">
                            <i class="fas fa-users"></i> Capacidade: ${site.total_capacity || 0} pessoas
                            <span class="ml-3"><i class="fas fa-building"></i> ${site.sectors_count || 0} setores</span>
                        </p>
                        ${site.address ? `<p class="text-muted mb-0"><i class="fas fa-map-marker-alt"></i> ${site.address}</p>` : ''}
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-info btn-sm" onclick="viewSite(${site.id})">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="editSite(${site.id})">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#sitesList').html(html);
}

function showAddSiteModal() {
    $('#addSiteForm')[0].reset();
    $('#addSiteModal').modal('show');
}

function saveSite() {
    const formData = {
        name: $('#siteName').val(),
        capacity: parseInt($('#siteCapacity').val()) || 0,
        address: $('#siteAddress').val() || null,
        timezone: $('#siteTimezone').val()
    };
    
    $.post('/config/sites', JSON.stringify(formData), function(response) {
        if (response.success) {
            $('#addSiteModal').modal('hide');
            loadSites();
            showAlert('Site criado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro ao criar site', 'error');
        }
    }, 'json')
    .fail(function() {
        showAlert('Erro ao criar site', 'error');
    });
}

// ========== RBAC ==========
function loadRbacMatrix() {
    $.get('/config/rbac-matrix')
        .done(function(response) {
            if (response.success) {
                renderRbacMatrix(response.data);
            }
        })
        .fail(function() {
            $('#rbacMatrix').html('<tr><td colspan="100%" class="text-center text-danger">Erro ao carregar matriz RBAC</td></tr>');
        });
}

function renderRbacMatrix(data) {
    const {roles, permissions, matrix} = data;
    
    // Agrupar permissões por módulo
    const permissionsByModule = {};
    permissions.forEach(function(perm) {
        if (!permissionsByModule[perm.module]) {
            permissionsByModule[perm.module] = [];
        }
        permissionsByModule[perm.module].push(perm);
    });
    
    // Cabeçalho
    let html = '<thead><tr><th style="width: 300px;">Permissões</th>';
    roles.forEach(function(role) {
        html += `<th class="text-center" style="width: 120px;">${role.name}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Linhas por módulo
    Object.keys(permissionsByModule).forEach(function(module) {
        // Linha do módulo
        html += `<tr class="module-group">
            <td colspan="${roles.length + 1}">
                <i class="fas fa-folder"></i> ${module.toUpperCase()}
            </td>
        </tr>`;
        
        // Permissões do módulo
        permissionsByModule[module].forEach(function(permission) {
            html += `<tr>
                <td>
                    <strong>${permission.key}</strong><br>
                    <small class="text-muted">${permission.description}</small>
                </td>`;
            
            roles.forEach(function(role) {
                const hasPermission = matrix[role.id] && matrix[role.id].includes(permission.key);
                const disabled = role.name === 'administrador' && permission.key === 'config.write' ? 'disabled checked' : '';
                
                html += `<td class="text-center">
                    <input type="checkbox" class="permission-checkbox" 
                           data-role="${role.id}" data-permission="${permission.key}"
                           ${hasPermission ? 'checked' : ''} ${disabled}>
                </td>`;
            });
            
            html += '</tr>';
        });
    });
    
    html += '</tbody>';
    $('#rbacMatrix').html(html);
}

function saveRbacMatrix() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    btn.disabled = true;
    
    // Coletar matriz atual
    const updates = {};
    $('.permission-checkbox').each(function() {
        const roleId = $(this).data('role');
        const permission = $(this).data('permission');
        const isChecked = $(this).is(':checked');
        
        if (!updates[roleId]) {
            updates[roleId] = [];
        }
        
        if (isChecked) {
            updates[roleId].push(permission);
        }
    });
    
    // Salvar cada role
    let completed = 0;
    const totalRoles = Object.keys(updates).length;
    
    Object.keys(updates).forEach(function(roleId) {
        $.ajax({
            url: '/config/role-permissions',
            method: 'PUT',
            data: JSON.stringify({
                role_id: roleId,
                permissions: updates[roleId]
            }),
            contentType: 'application/json',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .always(function() {
            completed++;
            if (completed === totalRoles) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showAlert('Matriz RBAC atualizada com sucesso!', 'success');
            }
        });
    });
}

// ========== AUTENTICAÇÃO ==========
function loadAuthPolicies() {
    $.get('/config/auth-policies')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                $('#passwordMinLength').val(data.password_min_length || 8);
                $('#passwordExpiryDays').val(data.password_expiry_days || 90);
                $('#sessionTimeout').val(data.session_timeout_minutes || 1440);
                $('#require2fa').prop('checked', data.require_2fa || false);
                $('#enableSso').prop('checked', data.enable_sso || false);
                $('#ssoProvider').val(data.sso_provider || '');
                $('#ssoClientId').val(data.sso_client_id || '');
                $('#ssoIssuer').val(data.sso_issuer || '');
                
                toggleSsoFields();
            }
        });
}

function toggleSsoFields() {
    const enabled = $('#enableSso').is(':checked');
    $('#ssoFields').toggle(enabled);
}

$('#authPoliciesForm').submit(function(e) {
    e.preventDefault();
    
    const formData = {
        password_min_length: parseInt($('#passwordMinLength').val()),
        password_expiry_days: parseInt($('#passwordExpiryDays').val()),
        session_timeout_minutes: parseInt($('#sessionTimeout').val()),
        require_2fa: $('#require2fa').is(':checked'),
        enable_sso: $('#enableSso').is(':checked'),
        sso_provider: $('#ssoProvider').val() || null,
        sso_client_id: $('#ssoClientId').val() || null,
        sso_issuer: $('#ssoIssuer').val() || null
    };
    
    $.ajax({
        url: '/config/auth-policies',
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            showAlert('Políticas de autenticação atualizadas!', 'success');
        } else {
            showAlert(response.message || 'Erro ao salvar', 'error');
        }
    })
    .fail(function() {
        showAlert('Erro ao salvar políticas', 'error');
    });
});

// ========== AUDITORIA ==========
let currentAuditPage = 1;

function loadUsers() {
    $.get('/config/users')
        .done(function(response) {
            if (response.success) {
                let options = '<option value="">Todos</option>';
                response.data.forEach(function(user) {
                    options += `<option value="${user.id}">${user.nome} (${user.email})</option>`;
                });
                $('#filterUser').html(options);
            }
        });
}

function loadAuditLogs(page = 1) {
    currentAuditPage = page;
    
    const params = new URLSearchParams();
    params.append('page', page);
    params.append('pageSize', 20);
    
    // Adicionar filtros
    const filters = ['user_id', 'entity', 'action', 'date_start', 'date_end'];
    filters.forEach(function(filter) {
        const value = $(`#filter${filter.split('_').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('')}`).val();
        if (value) {
            params.append(filter, value);
        }
    });
    
    $('#auditLogsTable').html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>');
    
    $.get(`/config/audit?${params.toString()}`)
        .done(function(response) {
            if (response.success) {
                renderAuditLogs(response.data.logs);
                renderAuditPagination(response.data.pagination);
            }
        })
        .fail(function() {
            $('#auditLogsTable').html('<tr><td colspan="6" class="text-center text-danger">Erro ao carregar logs</td></tr>');
        });
}

function renderAuditLogs(logs) {
    if (logs.length === 0) {
        $('#auditLogsTable').html('<tr><td colspan="6" class="text-center text-muted">Nenhum log encontrado</td></tr>');
        return;
    }
    
    let html = '';
    logs.forEach(function(log) {
        const changes = getLogChanges(log);
        html += `
            <tr>
                <td>${log.timestamp_formatted}</td>
                <td>${log.usuario_nome || 'Sistema'}</td>
                <td>
                    <span class="badge badge-${getActionColor(log.acao)}">${log.acao}</span>
                </td>
                <td>${log.entidade}</td>
                <td>${log.entidade_id}</td>
                <td>
                    ${changes ? `<small>${changes}</small>` : '-'}
                </td>
            </tr>
        `;
    });
    
    $('#auditLogsTable').html(html);
}

function renderAuditPagination(pagination) {
    $('#auditPaginationInfo').text(
        `Mostrando página ${pagination.current} de ${pagination.total} (${pagination.totalItems} registros)`
    );
    
    let html = '';
    
    // Anterior
    if (pagination.current > 1) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadAuditLogs(${pagination.current - 1})">Anterior</a>
        </li>`;
    }
    
    // Páginas
    for (let i = Math.max(1, pagination.current - 2); i <= Math.min(pagination.total, pagination.current + 2); i++) {
        html += `<li class="page-item ${i === pagination.current ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadAuditLogs(${i})">${i}</a>
        </li>`;
    }
    
    // Próxima
    if (pagination.current < pagination.total) {
        html += `<li class="page-item">
            <a class="page-link" href="#" onclick="loadAuditLogs(${pagination.current + 1})">Próxima</a>
        </li>`;
    }
    
    $('#auditPagination').html(html);
}

function getLogChanges(log) {
    if (log.dados_antes_parsed && log.dados_depois_parsed) {
        const changes = [];
        Object.keys(log.dados_depois_parsed).forEach(function(key) {
            if (log.dados_antes_parsed[key] !== log.dados_depois_parsed[key]) {
                changes.push(`${key}: ${log.dados_antes_parsed[key]} → ${log.dados_depois_parsed[key]}`);
            }
        });
        return changes.slice(0, 2).join(', ') + (changes.length > 2 ? '...' : '');
    }
    return null;
}

function getActionColor(action) {
    switch (action) {
        case 'create': return 'success';
        case 'update': return 'warning';
        case 'delete': return 'danger';
        default: return 'secondary';
    }
}

function clearAuditFilters() {
    $('#auditFiltersForm')[0].reset();
    loadAuditLogs(1);
}

function exportAuditLogs() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
    btn.disabled = true;
    
    const params = new URLSearchParams();
    
    // Adicionar filtros atuais
    const filters = ['user_id', 'entity', 'action', 'date_start', 'date_end'];
    filters.forEach(function(filter) {
        const value = $(`#filter${filter.split('_').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('')}`).val();
        if (value) {
            params.append(filter, value);
        }
    });
    
    // Fazer download
    window.location.href = `/config/audit/export?${params.toString()}`;
    
    // Restaurar botão
    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 2000);
}

// ========== UTILITÁRIOS ==========
function initializeConfig() {
    // Máscaras de input
    $('#cnpj').mask('00.000.000/0000-00');
}

function showAlert(message, type = 'info') {
    const alertClass = type === 'error' ? 'danger' : type;
    const alertHtml = `
        <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    // Inserir no topo da seção ativa
    $('.config-section.active .card-body').first().prepend(alertHtml);
    
    // Auto-remove após 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>

<!-- Máscaras de input -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<?php include '../views/includes/footer.php'; ?>