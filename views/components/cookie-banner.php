<?php
/**
 * Banner de Cookies LGPD
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Componente para gerenciamento de consentimento de cookies
 * conforme documentação LGPD do sistema
 */
?>

<div id="cookie-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-content">
        <div class="container-fluid">
            <div class="row align-items-center">
                <!-- Ícone e Título -->
                <div class="col-auto">
                    <i class="fas fa-cookie-bite cookie-icon"></i>
                </div>
                
                <!-- Conteúdo Principal -->
                <div class="col">
                    <div class="cookie-text">
                        <h5 class="mb-2">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Política de Cookies e Privacidade
                        </h5>
                        <p class="mb-0">
                            Este sistema utiliza cookies essenciais para seu funcionamento e cookies opcionais 
                            para melhorar sua experiência. Respeitamos sua privacidade conforme a 
                            <strong>Lei Geral de Proteção de Dados (LGPD)</strong>.
                        </p>
                        <div class="cookie-details mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                Cookies essenciais são necessários para segurança e funcionamento básico.
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Botões de Ação -->
                <div class="col-auto">
                    <div class="cookie-actions">
                        <div class="btn-group-vertical btn-group-sm d-none d-md-flex">
                            <button type="button" class="btn btn-outline-light btn-sm mb-1" 
                                    onclick="CookieConsent.showPreferences()">
                                <i class="fas fa-cog mr-1"></i>
                                Gerenciar Cookies
                            </button>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-secondary" 
                                        onclick="CookieConsent.rejectOptional()">
                                    <i class="fas fa-times mr-1"></i>
                                    Apenas Essenciais
                                </button>
                                <button type="button" class="btn btn-success" 
                                        onclick="CookieConsent.acceptAll()">
                                    <i class="fas fa-check mr-1"></i>
                                    Aceitar Todos
                                </button>
                            </div>
                        </div>
                        
                        <!-- Versão Mobile -->
                        <div class="d-md-none">
                            <button type="button" class="btn btn-outline-light btn-sm mb-2 btn-block" 
                                    onclick="CookieConsent.showPreferences()">
                                <i class="fas fa-cog mr-1"></i>
                                Configurar
                            </button>
                            <div class="btn-group btn-group-sm btn-block">
                                <button type="button" class="btn btn-secondary" 
                                        onclick="CookieConsent.rejectOptional()">
                                    Essenciais
                                </button>
                                <button type="button" class="btn btn-success" 
                                        onclick="CookieConsent.acceptAll()">
                                    Aceitar Todos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Link para Política Completa -->
        <div class="cookie-footer mt-3">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <small class="text-muted">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            Leia nossa 
                            <a href="/privacy" target="_blank" class="text-light">
                                <u>Política de Privacidade completa</u>
                            </a>
                            para mais informações sobre tratamento de dados.
                        </small>
                    </div>
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
/* Estilos do Banner de Cookies */
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    color: white;
    z-index: 9999;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
    padding: 20px 0;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cookie-banner-content {
    max-width: 100%;
}

.cookie-icon {
    font-size: 2.5rem;
    color: #f39c12;
    margin-right: 15px;
}

.cookie-text h5 {
    color: white;
    font-weight: 600;
}

.cookie-text p {
    line-height: 1.5;
    margin-bottom: 0;
}

.cookie-actions .btn {
    min-width: 120px;
    font-weight: 500;
}

.cookie-actions .btn-outline-light {
    border-color: rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.9);
}

.cookie-actions .btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

.cookie-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 10px;
}

.cookie-footer a {
    color: #f8f9fa !important;
    transition: all 0.3s ease;
}

.cookie-footer a:hover {
    color: #f39c12 !important;
    text-decoration: none !important;
}

/* Responsividade */
@media (max-width: 768px) {
    .cookie-banner {
        padding: 15px 0;
    }
    
    .cookie-icon {
        font-size: 2rem;
        margin-right: 10px;
    }
    
    .cookie-text h5 {
        font-size: 1.1rem;
    }
    
    .cookie-text p {
        font-size: 0.9rem;
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