<?php
/**
 * Componente de Rodapé LGPD
 * Links rápidos para políticas de privacidade
 * 
 * Uso: <?php require_once __DIR__ . '/../components/lgpd-footer.php'; ?>
 */
?>

<!-- 🛡️ CSS Dedicado LGPD Footer - Force Reload -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/lgpd-footer.css?v=20250929<?= microtime(true) ?>">

<!-- Rodapé LGPD -->
<div class="lgpd-footer-links">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <!-- Botão Fechar - Posicionado Absolutamente -->
                        <button type="button" class="btn btn-link text-muted p-1 lgpd-close-btn" 
                                onclick="document.querySelector('.lgpd-footer-links').style.display='none'"
                                title="Fechar footer LGPD"
                                aria-label="Fechar footer LGPD">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <div class="row align-items-center">
                            <!-- Informação Principal -->
                            <div class="col-md-8 mb-2 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-primary mr-2"></i>
                                    <span class="text-muted">
                                        <strong>LGPD Compliant:</strong> 
                                        Este sistema respeita seus direitos de privacidade conforme a Lei Geral de Proteção de Dados
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Links Rápidos -->
                            <div class="col-md-4">
                                <div class="d-flex justify-content-md-end justify-content-center flex-wrap" style="margin-right: 10px;">
                                    <a href="/privacy" class="btn btn-outline-secondary btn-sm mx-1 mb-1" 
                                       title="Política de Privacidade Completa"
                                       aria-label="Acessar Política de Privacidade">
                                        <i class="fas fa-file-alt mr-1"></i>
                                        <span>Privacidade</span>
                                    </a>
                                    <a href="/privacy/cookies" class="btn btn-outline-info btn-sm mx-1 mb-1" 
                                       title="Página de Cookies"
                                       aria-label="Gerenciar Cookies">
                                        <i class="fas fa-cookie-bite mr-1"></i>
                                        <span>Cookies</span>
                                    </a>
                                    <!-- 🍪 NOVO: Link Persistente Gerenciar Cookies -->
                                    <button type="button" class="btn btn-warning btn-sm mx-1 mb-1" 
                                            onclick="if (typeof CookieConsent !== 'undefined') { CookieConsent.showPreferences(); } else { alert('Sistema de cookies não disponível'); }"
                                            title="Gerenciar Preferências de Cookies - Modal Rápido"
                                            aria-label="Gerenciar Preferências de Cookies">
                                        <i class="fas fa-cog mr-1"></i>
                                        <span>Gerenciar</span>
                                    </button>
                                    <a href="/privacy/portal" class="btn btn-outline-success btn-sm mx-1 mb-1" 
                                       title="Portal do Titular - Exercer Direitos LGPD"
                                       aria-label="Portal dos Direitos LGPD">
                                        <i class="fas fa-user-shield mr-1"></i>
                                        <span>Direitos</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
