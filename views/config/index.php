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

/* Melhorias para RBAC Matrix */
.rbac-matrix-table .permission-row:hover {
    background-color: #f1f3f4;
}

.rbac-matrix-table .permission-info {
    padding: 8px 0;
}

.rbac-matrix-table .checkbox-cell {
    padding: 12px 8px;
    vertical-align: middle;
}

.rbac-matrix-table .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

.rbac-matrix-table .custom-control-input:disabled:checked ~ .custom-control-label::before {
    background-color: #ffc107;
    border-color: #ffc107;
}

/* Cards de estatísticas */
.border-left-primary {
    border-left: 4px solid #007bff !important;
}

.text-gray-800 {
    color: #495057 !important;
}

/* Modal de usuários */
#rbacUsersModal .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e3e6f0;
}

#rbacUsersModal .list-group-item {
    border-left: none;
    border-right: none;
}

/* Indicador de alterações */
#rbacChanges {
    border-left: 4px solid #ffc107;
    margin-bottom: 20px;
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
                                                <div class="logo-preview-container" 
                                                     style="border: 1px dashed #ccc; padding: 15px; text-align: center; min-height: 80px; background: #f9f9f9;">
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
                        <!-- Cards de Estatísticas -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div id="rbacStats">
                                    <!-- Estatísticas serão carregadas aqui -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-shield"></i> Matriz de Permissões (RBAC)</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-info btn-sm mr-2" onclick="showRbacUsers()">
                                        <i class="fas fa-users"></i> Ver Usuários por Perfil
                                    </button>
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
                                    <br><small class="mt-2 d-block">
                                        <strong>Dica:</strong> Clique nos checkboxes para alterar permissões. As alterações são salvas em lote.
                                    </small>
                                </div>
                                
                                <!-- Indicador de alterações pendentes -->
                                <div id="rbacChanges" class="alert alert-warning" style="display:none;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span id="rbacChangesCount">0</span> alteração(ões) pendente(s). 
                                    <strong>Clique em "Salvar Alterações" para aplicar.</strong>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm rbac-matrix-table" id="rbacMatrix">
                                        <tbody>
                                            <tr>
                                                <td colspan="100%" class="text-center">
                                                    <i class="fas fa-spinner fa-spin"></i> Carregando matriz RBAC...
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
                <button type="button" class="btn btn-primary" id="saveSiteBtn" onclick="saveSite()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Site -->
<div class="modal fade" id="editSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Local/Site</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editSiteForm">
                    <div class="form-group">
                        <label for="editSiteName">Nome do Local *</label>
                        <input type="text" class="form-control" id="editSiteName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editSiteCapacity">Capacidade Máxima</label>
                        <input type="number" class="form-control" id="editSiteCapacity" name="capacity" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="editSiteAddress">Endereço</label>
                        <textarea class="form-control" id="editSiteAddress" name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editSiteTimezone">Fuso Horário</label>
                        <select class="form-control" id="editSiteTimezone" name="timezone">
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
                <button type="button" class="btn btn-primary" id="updateSiteBtn" onclick="updateSite()">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Detalhes do Site -->
<div class="modal fade" id="siteDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Local</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Nome do Local</h6>
                        <p id="siteDetailName" class="h5"></p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">Capacidade Total</h6>
                        <p><span id="siteDetailCapacity" class="h5"></span> pessoas</p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted">Fuso Horário</h6>
                        <p id="siteDetailTimezone"></p>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="text-muted">Endereço</h6>
                        <p id="siteDetailAddress"></p>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Setores</h6>
                    <button type="button" class="btn btn-success btn-sm" id="addSectorBtn">
                        <i class="fas fa-plus"></i> Adicionar Setor
                    </button>
                </div>
                <div id="siteDetailsSectors">
                    <!-- Setores serão carregados aqui -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Setor -->
<div class="modal fade" id="addSectorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Setor</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addSectorForm">
                    <div class="form-group">
                        <label for="sectorName">Nome do Setor *</label>
                        <input type="text" class="form-control" id="sectorName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="sectorCapacity">Capacidade Máxima</label>
                        <input type="number" class="form-control" id="sectorCapacity" name="capacity" min="0" value="0">
                        <small class="form-text text-muted">Número de pessoas que podem estar neste setor</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSectorBtn" onclick="saveSector()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Setor -->
<div class="modal fade" id="editSectorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Setor</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editSectorForm">
                    <div class="form-group">
                        <label for="editSectorName">Nome do Setor *</label>
                        <input type="text" class="form-control" id="editSectorName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editSectorCapacity">Capacidade Máxima</label>
                        <input type="number" class="form-control" id="editSectorCapacity" name="capacity" min="0" value="0">
                        <small class="form-text text-muted">Número de pessoas que podem estar neste setor</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="updateSectorBtn" onclick="updateSector()">
                    <i class="fas fa-save"></i> Salvar Alterações
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
let autoSaveTimeout;
let isSaving = false;

function setupAutoSave() {
    // Auto-save com debounce para não sobrecarregar o servidor
    $('#organizationForm input, #organizationForm select').on('input change', function() {
        clearTimeout(autoSaveTimeout);
        showSavingIndicator();
        
        autoSaveTimeout = setTimeout(() => {
            if (!isSaving) {
                saveOrganizationData();
            }
        }, 1500); // Aguarda 1.5s após parar de digitar
    });
    
    // Máscara de CNPJ em tempo real
    $('#cnpj').on('input', function() {
        const value = $(this).val().replace(/\D/g, '');
        const masked = value
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
        $(this).val(masked);
    });
    
    // Validação de CNPJ em tempo real (ao sair do campo)
    $('#cnpj').on('blur', function() {
        if ($(this).val().trim()) {
            validateCnpj($(this).val());
        }
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
                
                // Carregar preview do logo se existir
                if (data.logo_url) {
                    showLogoPreview(data.logo_url);
                }
                
                // Mostrar indicador de dados carregados
                $('.auto-save-indicator').text('Dados carregados').show().delay(1000).fadeOut();
            }
        })
        .fail(function() {
            showAlert('Erro ao carregar dados da organização', 'error');
        });
}

function saveOrganizationData() {
    if (isSaving) return; // Prevenir chamadas múltiplas
    
    isSaving = true;
    const formData = {
        company_name: $('#companyName').val(),
        cnpj: $('#cnpj').val().replace(/\D/g, ''),
        timezone: $('#timezone').val(),
        locale: $('#locale').val(),
        logo_url: getCurrentLogoUrl() // Função para pegar URL atual do logo
    };
    
    // Validação mínima antes de salvar
    if (!formData.company_name.trim()) {
        showAlert('Nome da empresa é obrigatório', 'warning');
        isSaving = false;
        hideSavingIndicator();
        return;
    }
    
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
            showSavedIndicator();
        } else {
            showAlert(response.message || 'Erro ao salvar', 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao salvar dados da organização';
        showAlert(message, 'error');
    })
    .always(function() {
        isSaving = false;
        hideSavingIndicator();
    });
}

// ========== FEEDBACK VISUAL ==========
function showSavingIndicator() {
    $('.auto-save-indicator')
        .removeClass('text-success text-danger')
        .addClass('text-warning')
        .html('<i class="fas fa-spinner fa-spin"></i> Salvando...')
        .show();
}

function showSavedIndicator() {
    $('.auto-save-indicator')
        .removeClass('text-warning text-danger')
        .addClass('text-success')
        .html('<i class="fas fa-check-circle"></i> Salvo automaticamente')
        .show()
        .delay(3000)
        .fadeOut();
}

function hideSavingIndicator() {
    setTimeout(() => {
        if (!isSaving) {
            $('.auto-save-indicator').fadeOut();
        }
    }, 500);
}

// ========== UPLOAD DE LOGO ==========
let currentLogoUrl = null;

function handleLogoUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validações
    const maxSize = 2 * 1024 * 1024; // 2MB
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    
    if (file.size > maxSize) {
        showAlert('Arquivo muito grande. Máximo 2MB permitido.', 'error');
        input.value = '';
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        showAlert('Formato não suportado. Use PNG ou JPG.', 'error');
        input.value = '';
        return;
    }
    
    // Atualizar label do arquivo
    $(input).next('.custom-file-label').text(file.name);
    
    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function(e) {
        showLogoPreview(e.target.result, false);
    };
    reader.readAsDataURL(file);
    
    // Upload do arquivo
    uploadLogoFile(file);
}

function uploadLogoFile(file) {
    const formData = new FormData();
    formData.append('logo', file);
    
    // Mostrar progress
    $('#logoUploadProgress').show();
    $('.progress-bar').css('width', '0%');
    
    $.ajax({
        url: '/config/organization/logo',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        },
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    $('.progress-bar').css('width', percentComplete + '%');
                }
            });
            return xhr;
        }
    })
    .done(function(response) {
        if (response.success) {
            currentLogoUrl = response.data.url;
            showLogoPreview(response.data.url, true);
            showAlert('Logo carregado com sucesso!', 'success');
            // Salvar automaticamente a organização com novo logo
            saveOrganizationData();
        } else {
            showAlert(response.message || 'Erro ao fazer upload', 'error');
        }
    })
    .fail(function() {
        showAlert('Erro ao fazer upload do logo', 'error');
    })
    .always(function() {
        $('#logoUploadProgress').fadeOut();
    });
}

function showLogoPreview(url, isUploaded = true) {
    $('#logoPreview').attr('src', url).show();
    $('#logoPlaceholder').hide();
    $('#removeLogo').show();
    
    if (isUploaded) {
        currentLogoUrl = url;
    }
}

function removeLogo() {
    if (confirm('Tem certeza que deseja remover o logo?')) {
        currentLogoUrl = null;
        $('#logoPreview').hide();
        $('#logoPlaceholder').show();
        $('#removeLogo').hide();
        $('#logoUpload').val('');
        $('.custom-file-label').text('Escolher arquivo...');
        
        // Salvar automaticamente sem logo
        saveOrganizationData();
    }
}

function getCurrentLogoUrl() {
    return currentLogoUrl;
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
                        <button class="btn btn-danger btn-sm" onclick="deleteSite(${site.id}, '${site.name}')">
                            <i class="fas fa-trash"></i> Excluir
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
        name: $('#siteName').val().trim(),
        capacity: parseInt($('#siteCapacity').val()) || 0,
        address: $('#siteAddress').val().trim() || null,
        timezone: $('#siteTimezone').val()
    };
    
    // Validação
    if (!formData.name) {
        showAlert('Nome do local é obrigatório', 'warning');
        return;
    }
    
    // Desabilitar botão durante salvamento
    const $saveBtn = $('#saveSiteBtn');
    $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
    
    $.ajax({
        url: '/config/sites',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#addSiteModal').modal('hide');
            loadSites();
            showAlert('Local criado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro ao criar local', 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao criar local';
        showAlert(message, 'error');
    })
    .always(function() {
        $saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
    });
}

let currentEditingSite = null;

function editSite(siteId) {
    // Buscar dados do site
    $.get('/config/sites')
        .done(function(response) {
            if (response.success) {
                const site = response.data.find(s => s.id == siteId);
                if (site) {
                    currentEditingSite = site;
                    
                    // Preencher modal de edição
                    $('#editSiteName').val(site.name);
                    $('#editSiteCapacity').val(site.capacity || 0);
                    $('#editSiteAddress').val(site.address || '');
                    $('#editSiteTimezone').val(site.timezone || 'America/Sao_Paulo');
                    
                    $('#editSiteModal').modal('show');
                }
            }
        })
        .fail(function() {
            showAlert('Erro ao carregar dados do local', 'error');
        });
}

function updateSite() {
    if (!currentEditingSite) return;
    
    const formData = {
        name: $('#editSiteName').val().trim(),
        capacity: parseInt($('#editSiteCapacity').val()) || 0,
        address: $('#editSiteAddress').val().trim() || null,
        timezone: $('#editSiteTimezone').val()
    };
    
    // Validação
    if (!formData.name) {
        showAlert('Nome do local é obrigatório', 'warning');
        return;
    }
    
    // Desabilitar botão durante salvamento
    const $updateBtn = $('#updateSiteBtn');
    $updateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
    
    $.ajax({
        url: `/config/sites?id=${currentEditingSite.id}`,
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#editSiteModal').modal('hide');
            loadSites();
            showAlert('Local atualizado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro ao atualizar local', 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao atualizar local';
        showAlert(message, 'error');
    })
    .always(function() {
        $updateBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar Alterações');
        currentEditingSite = null;
    });
}

function viewSite(siteId) {
    // Carregar detalhes do site com setores
    Promise.all([
        $.get('/config/sites'),
        $.get(`/config/sectors?site_id=${siteId}`)
    ])
    .then(function([sitesResponse, sectorsResponse]) {
        if (sitesResponse.success && sectorsResponse.success) {
            const site = sitesResponse.data.find(s => s.id == siteId);
            const sectors = sectorsResponse.data;
            
            if (site) {
                showSiteDetailsModal(site, sectors);
            }
        }
    })
    .catch(function() {
        showAlert('Erro ao carregar detalhes do local', 'error');
    });
}

function showSiteDetailsModal(site, sectors) {
    // Preencher informações do site
    $('#siteDetailName').text(site.name);
    $('#siteDetailCapacity').text(site.total_capacity || 0);
    $('#siteDetailAddress').text(site.address || 'Não informado');
    $('#siteDetailTimezone').text(site.timezone || 'America/Sao_Paulo');
    
    // Renderizar setores
    let sectorsHtml = '';
    if (sectors.length === 0) {
        sectorsHtml = '<div class="text-muted">Nenhum setor cadastrado ainda.</div>';
    } else {
        sectors.forEach(function(sector) {
            sectorsHtml += `
                <div class="sector-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${sector.name}</strong>
                        ${sector.capacity ? `<span class="text-muted ml-2">(${sector.capacity} pessoas)</span>` : ''}
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary mr-1" onclick="editSector(${sector.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSector(${sector.id}, '${sector.name}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
    }
    $('#siteDetailsSectors').html(sectorsHtml);
    
    // Configurar botão para adicionar setor
    $('#addSectorBtn').attr('onclick', `showAddSectorModal(${site.id})`);
    
    $('#siteDetailsModal').modal('show');
}

function deleteSite(siteId, siteName) {
    if (confirm(`Tem certeza que deseja excluir o local "${siteName}"?\n\nEsta ação também excluirá todos os setores relacionados e não pode ser desfeita.`)) {
        $.ajax({
            url: `/config/sites?id=${siteId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            if (response.success) {
                loadSites();
                showAlert('Local excluído com sucesso!', 'success');
            } else {
                showAlert(response.message || 'Erro ao excluir local', 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'Erro ao excluir local';
            showAlert(message, 'error');
        });
    }
}

// ========== SETORES ==========
let currentSiteForSector = null;
let currentEditingSector = null;

function showAddSectorModal(siteId) {
    currentSiteForSector = siteId;
    $('#addSectorForm')[0].reset();
    $('#addSectorModal').modal('show');
}

function saveSector() {
    if (!currentSiteForSector) return;
    
    const formData = {
        site_id: currentSiteForSector,
        name: $('#sectorName').val().trim(),
        capacity: parseInt($('#sectorCapacity').val()) || 0
    };
    
    // Validação
    if (!formData.name) {
        showAlert('Nome do setor é obrigatório', 'warning');
        return;
    }
    
    // Desabilitar botão durante salvamento
    const $saveBtn = $('#saveSectorBtn');
    $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
    
    $.ajax({
        url: '/config/sectors',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#addSectorModal').modal('hide');
            // Recarregar detalhes do site se estiver aberto
            if ($('#siteDetailsModal').hasClass('show')) {
                viewSite(currentSiteForSector);
            } else {
                loadSites(); // Atualizar contagem de setores
            }
            showAlert('Setor criado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro ao criar setor', 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao criar setor';
        showAlert(message, 'error');
    })
    .always(function() {
        $saveBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
        currentSiteForSector = null;
    });
}

function editSector(sectorId) {
    // Buscar dados do setor
    // Como não temos endpoint específico para um setor, vamos pegar de todos os setores
    const siteId = currentSiteForSector; // Preciso pegar o site atual
    
    $.get(`/config/sectors?site_id=${siteId}`)
        .done(function(response) {
            if (response.success) {
                const sector = response.data.find(s => s.id == sectorId);
                if (sector) {
                    currentEditingSector = sector;
                    
                    // Preencher modal de edição  
                    $('#editSectorName').val(sector.name);
                    $('#editSectorCapacity').val(sector.capacity || 0);
                    
                    $('#editSectorModal').modal('show');
                }
            }
        })
        .fail(function() {
            showAlert('Erro ao carregar dados do setor', 'error');
        });
}

function updateSector() {
    if (!currentEditingSector) return;
    
    const formData = {
        name: $('#editSectorName').val().trim(),
        capacity: parseInt($('#editSectorCapacity').val()) || 0
    };
    
    // Validação
    if (!formData.name) {
        showAlert('Nome do setor é obrigatório', 'warning');
        return;
    }
    
    // Desabilitar botão durante salvamento
    const $updateBtn = $('#updateSectorBtn');
    $updateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
    
    $.ajax({
        url: `/config/sectors?id=${currentEditingSector.id}`,
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#editSectorModal').modal('hide');
            // Recarregar vista do site
            viewSite(currentEditingSector.site_id);
            showAlert('Setor atualizado com sucesso!', 'success');
        } else {
            showAlert(response.message || 'Erro ao atualizar setor', 'error');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'Erro ao atualizar setor';
        showAlert(message, 'error');
    })
    .always(function() {
        $updateBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar Alterações');
        currentEditingSector = null;
    });
}

function deleteSector(sectorId, sectorName) {
    if (confirm(`Tem certeza que deseja excluir o setor "${sectorName}"?\n\nEsta ação não pode ser desfeita.`)) {
        $.ajax({
            url: `/config/sectors?id=${sectorId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function(response) {
            if (response.success) {
                // Recarregar vista do site se estiver aberta
                if ($('#siteDetailsModal').hasClass('show') && currentSiteForSector) {
                    viewSite(currentSiteForSector);
                } else {
                    loadSites(); // Atualizar contagem de setores
                }
                showAlert('Setor excluído com sucesso!', 'success');
            } else {
                showAlert(response.message || 'Erro ao excluir setor', 'error');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'Erro ao excluir setor';
            showAlert(message, 'error');
        });
    }
}

// ========== RBAC ==========
let originalRbacMatrix = {}; // Armazenar matriz original para detectar alterações
let rbacChangesCount = 0;

function loadRbacMatrix() {
    $.get('/config/rbac-matrix')
        .done(function(response) {
            if (response.success) {
                // Armazenar matriz original
                originalRbacMatrix = JSON.parse(JSON.stringify(response.data.matrix));
                renderRbacMatrix(response.data);
                renderRbacStats(response.data);
                setupRbacChangeTracking();
            }
        })
        .fail(function() {
            $('#rbacMatrix').html('<tbody><tr><td colspan="100%" class="text-center text-danger">Erro ao carregar matriz RBAC</td></tr></tbody>');
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
    
    // Cabeçalho com ícones para cada role
    let html = '<thead><tr><th style="width: 350px;">Permissões</th>';
    roles.forEach(function(role) {
        const roleIcon = getRoleIcon(role.name);
        html += `<th class="text-center" style="width: 140px;">
            <div>${roleIcon} <strong>${role.name}</strong></div>
            <div><small class="text-muted">${role.description}</small></div>
        </th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Linhas por módulo
    Object.keys(permissionsByModule).forEach(function(module) {
        // Linha do módulo
        html += `<tr class="module-group">
            <td colspan="${roles.length + 1}">
                <i class="fas fa-folder-open text-primary"></i> <strong>${getModuleName(module)}</strong>
            </td>
        </tr>`;
        
        // Permissões do módulo
        permissionsByModule[module].forEach(function(permission) {
            html += `<tr class="permission-row">
                <td>
                    <div class="permission-info">
                        <strong class="text-dark">${permission.key}</strong>
                        <br><small class="text-muted">${permission.description}</small>
                    </div>
                </td>`;
            
            roles.forEach(function(role) {
                const hasPermission = matrix[role.id] && matrix[role.id].includes(permission.key);
                const isProtected = role.name === 'administrador' && 
                                  (permission.key === 'config.write' || permission.key.startsWith('config.'));
                const disabled = isProtected ? 'disabled' : '';
                const checked = hasPermission ? 'checked' : '';
                
                html += `<td class="text-center checkbox-cell">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="permission-checkbox custom-control-input" 
                               id="perm_${role.id}_${permission.key.replace(/\./g, '_')}"
                               data-role="${role.id}" data-permission="${permission.key}"
                               ${checked} ${disabled}>
                        <label class="custom-control-label" for="perm_${role.id}_${permission.key.replace(/\./g, '_')}"></label>
                        ${isProtected ? '<i class="fas fa-lock text-warning ml-1" title="Protegido - Admin sempre tem acesso"></i>' : ''}
                    </div>
                </td>`;
            });
            
            html += '</tr>';
        });
    });
    
    html += '</tbody>';
    $('#rbacMatrix').html(html);
}

function getRoleIcon(roleName) {
    const icons = {
        'administrador': '<i class="fas fa-crown text-warning"></i>',
        'porteiro': '<i class="fas fa-door-open text-info"></i>',
        'seguranca': '<i class="fas fa-shield-alt text-success"></i>',
        'recepcao': '<i class="fas fa-concierge-bell text-primary"></i>',
        'rh': '<i class="fas fa-user-tie text-secondary"></i>'
    };
    return icons[roleName] || '<i class="fas fa-user text-muted"></i>';
}

function getModuleName(module) {
    const names = {
        'config': 'Configurações do Sistema',
        'reports': 'Relatórios e Análises',
        'access': 'Controle de Acesso',
        'audit': 'Auditoria e Logs',
        'users': 'Gerenciamento de Usuários',
        'privacy': 'Privacidade e LGPD'
    };
    return names[module] || module.toUpperCase();
}

function renderRbacStats(data) {
    const {roles, permissions} = data;
    
    let html = '<div class="row">';
    
    roles.forEach(function(role) {
        const rolePermissions = data.matrix[role.id] || [];
        const percentageAllowed = Math.round((rolePermissions.length / permissions.length) * 100);
        const roleIcon = getRoleIcon(role.name);
        
        html += `
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="card border-left-primary h-100">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">${roleIcon}</div>
                        <div class="h6 font-weight-bold text-primary text-uppercase mb-1">
                            ${role.name}
                        </div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            ${rolePermissions.length}
                        </div>
                        <small class="text-muted">
                            de ${permissions.length} permissões (${percentageAllowed}%)
                        </small>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $('#rbacStats').html(html);
}

function setupRbacChangeTracking() {
    $('#rbacMatrix').on('change', '.permission-checkbox', function() {
        updateRbacChangesCounter();
    });
}

function updateRbacChangesCounter() {
    let changes = 0;
    $('.permission-checkbox').each(function() {
        const roleId = $(this).data('role');
        const permission = $(this).data('permission');
        const isChecked = $(this).is(':checked');
        const wasChecked = originalRbacMatrix[roleId] && originalRbacMatrix[roleId].includes(permission);
        
        if (isChecked !== wasChecked) {
            changes++;
        }
    });
    
    rbacChangesCount = changes;
    
    if (changes > 0) {
        $('#rbacChangesCount').text(changes);
        $('#rbacChanges').show();
    } else {
        $('#rbacChanges').hide();
    }
}

function saveRbacMatrix() {
    if (rbacChangesCount === 0) {
        showAlert('Nenhuma alteração para salvar.', 'info');
        return;
    }
    
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
    let errors = 0;
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
        .done(function(response) {
            if (!response.success) {
                errors++;
            }
        })
        .fail(function() {
            errors++;
        })
        .always(function() {
            completed++;
            if (completed === totalRoles) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (errors === 0) {
                    showAlert(`${rbacChangesCount} alteração(ões) salva(s) com sucesso!`, 'success');
                    // Atualizar matriz original e resetar contador
                    originalRbacMatrix = {};
                    $('.permission-checkbox').each(function() {
                        const roleId = $(this).data('role');
                        const permission = $(this).data('permission');
                        const isChecked = $(this).is(':checked');
                        
                        if (!originalRbacMatrix[roleId]) {
                            originalRbacMatrix[roleId] = [];
                        }
                        
                        if (isChecked) {
                            originalRbacMatrix[roleId].push(permission);
                        }
                    });
                    updateRbacChangesCounter();
                } else {
                    showAlert(`Algumas alterações não foram salvas (${errors} erro(s))`, 'error');
                }
            }
        });
    });
}

function showRbacUsers() {
    $.get('/config/rbac-users')
        .done(function(response) {
            if (response.success) {
                renderRbacUsersModal(response.data);
            } else {
                showAlert('Erro ao carregar usuários por perfil', 'error');
            }
        })
        .fail(function() {
            showAlert('Erro ao carregar usuários por perfil', 'error');
        });
}

function renderRbacUsersModal(data) {
    const {roles, usersByRole} = data;
    
    let html = `
        <div class="modal fade" id="rbacUsersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-users"></i> Usuários por Perfil
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
    `;
    
    roles.forEach(function(role) {
        const users = usersByRole[role.id] || [];
        const roleIcon = getRoleIcon(role.name);
        
        html += `
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            ${roleIcon} ${role.name}
                            <span class="badge badge-primary ml-2">${users.length}</span>
                        </h6>
                        <small class="text-muted">${role.description}</small>
                    </div>
                    <div class="card-body">
                        ${users.length === 0 ? 
                            '<p class="text-muted text-center">Nenhum usuário neste perfil</p>' :
                            '<div class="list-group list-group-flush">'
                        }
        `;
        
        users.forEach(function(user) {
            const statusClass = user.ativo ? 'success' : 'secondary';
            const statusText = user.ativo ? 'Ativo' : 'Inativo';
            const lastLogin = user.ultimo_login ? 
                new Date(user.ultimo_login).toLocaleDateString('pt-BR') : 
                'Nunca';
            
            html += `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>${user.nome}</strong>
                            <br><small class="text-muted">${user.email}</small>
                            <br><small class="text-muted">Último login: ${lastLogin}</small>
                        </div>
                        <span class="badge badge-${statusClass}">${statusText}</span>
                    </div>
                </div>
            `;
        });
        
        if (users.length > 0) {
            html += '</div>';
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente se houver
    $('#rbacUsersModal').remove();
    
    // Adicionar e mostrar novo modal
    $('body').append(html);
    $('#rbacUsersModal').modal('show');
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