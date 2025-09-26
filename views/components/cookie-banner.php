<?php
/**
 * Banner de Cookies LGPD
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Componente para gerenciamento de consentimento de cookies
 * conforme documentação LGPD do sistema
 */
?>

<div id="cookie-banner" class="cookie-banner-compact" style="display: none;">
    <div class="container-fluid">
        <div class="row align-items-center py-2">
            <!-- Ícone -->
            <div class="col-auto">
                <i class="fas fa-cookie-bite text-warning mr-2"></i>
            </div>
            
            <!-- Texto Compacto -->
            <div class="col">
                <span class="cookie-text-compact">
                    Este sistema utiliza cookies essenciais para seu funcionamento e cookies opcionais para melhorar sua experiência. 
                    Respeitamos sua privacidade conforme a <strong>Lei Geral de Proteção de Dados (LGPD)</strong>.
                    <a href="/privacy" target="_blank" class="text-info ml-1">Política de Privacidade completa</a>
                </span>
            </div>
            
            <!-- Botões Compactos -->
            <div class="col-auto">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-info btn-sm" 
                            onclick="CookieConsent.showPreferences()" 
                            title="Gerenciar Cookies">
                        <i class="fas fa-cog"></i>
                        <span class="d-none d-sm-inline ml-1">Gerenciar</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" 
                            onclick="CookieConsent.rejectOptional()" 
                            title="Apenas Essenciais">
                        <i class="fas fa-times"></i>
                        <span class="d-none d-md-inline ml-1">Essenciais</span>
                    </button>
                    <button type="button" class="btn btn-success btn-sm" 
                            onclick="CookieConsent.acceptAll()" 
                            title="Aceitar Todos">
                        <i class="fas fa-check"></i>
                        <span class="d-none d-md-inline ml-1">Aceitar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Preferências de Cookies -->
<div class="modal fade" id="cookiePreferencesModal" tabindex="-1" role="dialog" 
     aria-labelledby="cookiePreferencesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="cookiePreferencesModalLabel">
                    <i class="fas fa-cookie-bite mr-2"></i>
                    Preferências de Cookies
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Sobre os Cookies:</strong> 
                    Utilizamos cookies para garantir segurança, personalizar sua experiência e analisar 
                    o desempenho do sistema conforme nossa Política de Privacidade.
                </div>
                
                <!-- Cookies Essenciais -->
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-shield-alt mr-2 text-danger"></i>
                                Cookies Essenciais
                            </h6>
                            <span class="badge badge-danger">Obrigatórios</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            Necessários para o funcionamento básico do sistema, incluindo autenticação, 
                            segurança e proteção contra ataques.
                        </p>
                        <ul class="list-unstyled mb-0">
                            <li><strong>PHPSESSID:</strong> Mantém sua sessão ativa</li>
                            <li><strong>csrf_token:</strong> Proteção contra ataques</li>
                            <li><strong>auth_token:</strong> Verificação de autenticação</li>
                            <li><strong>audit_id:</strong> Logs de auditoria (5 anos)</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Cookies de Funcionalidade -->
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-cog mr-2 text-primary"></i>
                                Cookies de Funcionalidade
                            </h6>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="functionalCookies" name="cookie_preferences[]" value="functional">
                                <label class="custom-control-label" for="functionalCookies"></label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            Melhoram sua experiência lembrando suas preferências e configurações.
                        </p>
                        <ul class="list-unstyled mb-0">
                            <li><strong>language_pref:</strong> Idioma preferido</li>
                            <li><strong>theme_mode:</strong> Tema claro/escuro</li>
                            <li><strong>dashboard_layout:</strong> Layout personalizado</li>
                        </ul>
                        <small class="text-muted">Duração: 30 dias</small>
                    </div>
                </div>
                
                <!-- Cookies de Performance -->
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line mr-2 text-success"></i>
                                Cookies de Performance
                            </h6>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="performanceCookies" name="cookie_preferences[]" value="performance">
                                <label class="custom-control-label" for="performanceCookies"></label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            Coletam informações sobre uso do sistema para melhorar performance 
                            e identificar problemas técnicos.
                        </p>
                        <ul class="list-unstyled mb-0">
                            <li><strong>analytics_session:</strong> Análise de performance</li>
                            <li><strong>error_tracking:</strong> Identificação de problemas</li>
                        </ul>
                        <small class="text-muted">Duração: 90 dias</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="CookieConsent.savePreferences()">
                    <i class="fas fa-save mr-1"></i>
                    Salvar Preferências
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos do Banner de Cookies Compacto */
.cookie-banner-compact {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background-color: #4a90e2;
    color: white;
    z-index: 9999;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    font-size: 0.85rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cookie-text-compact {
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.95);
}

.cookie-text-compact strong {
    color: white;
}

.cookie-text-compact a {
    color: #b3d9ff !important;
    text-decoration: underline;
}

.cookie-text-compact a:hover {
    color: white !important;
    text-decoration: none;
}

.cookie-banner-compact .btn-group .btn {
    border-radius: 4px;
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    font-weight: 500;
}

.cookie-banner-compact .btn-outline-info {
    border-color: rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.9);
}

.cookie-banner-compact .btn-outline-info:hover {
    background-color: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
}

.cookie-banner-compact .btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.cookie-banner-compact .btn-secondary:hover {
    background-color: #545b62;
    border-color: #4e555b;
}

.cookie-banner-compact .btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.cookie-banner-compact .btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

/* Responsividade para banner compacto */
@media (max-width: 768px) {
    .cookie-banner-compact {
        font-size: 0.8rem;
        position: relative;
        top: auto;
    }
    
    .cookie-text-compact {
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .cookie-banner-compact .btn-group {
        width: 100%;
        justify-content: center;
    }
    
    .cookie-banner-compact .btn-group .btn {
        flex: 1;
        min-width: auto;
    }
}

@media (max-width: 576px) {
    .cookie-banner-compact .btn-group .btn span {
        display: none !important;
    }
    
    .cookie-banner-compact .btn-group .btn {
        padding: 0.25rem 0.4rem;
    }
}

/* Modal de Preferências */
.modal-content {
    border: none;
    border-radius: 8px;
    overflow: hidden;
}

.modal-header.bg-primary {
    border-bottom: none;
}

.card {
    border: 1px solid #e9ecef;
    border-radius: 6px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.custom-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #28a745;
    border-color: #28a745;
}
</style>