<?php
/**
 * Componente de Rodapé LGPD
 * Links rápidos para políticas de privacidade
 * 
 * Uso: <?php require_once __DIR__ . '/../components/lgpd-footer.php'; ?>
 */
?>

<!-- Rodapé LGPD -->
<div class="lgpd-footer-links" style="margin-top: 2rem;">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card border-0" style="background-color: #f8f9fa;">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <!-- Informação Principal -->
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-primary mr-2"></i>
                                    <span class="text-muted">
                                        <strong>LGPD Compliant:</strong> 
                                        Este sistema respeita seus direitos de privacidade conforme a Lei Geral de Proteção de Dados
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Links Rápidos -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-md-end flex-wrap">
                                    <a href="/privacy" class="btn btn-outline-secondary btn-sm mx-1 mb-1" 
                                       title="Política de Privacidade Completa">
                                        <i class="fas fa-file-alt mr-1"></i>
                                        Privacidade
                                    </a>
                                    <a href="/privacy/cookies" class="btn btn-outline-info btn-sm mx-1 mb-1" 
                                       title="Gerenciar Cookies">
                                        <i class="fas fa-cookie-bite mr-1"></i>
                                        Cookies
                                    </a>
                                    <a href="/privacy/portal" class="btn btn-outline-success btn-sm mx-1 mb-1" 
                                       title="Portal do Titular - Exercer Direitos LGPD">
                                        <i class="fas fa-user-shield mr-1"></i>
                                        Direitos
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

<!-- Estilo adicional apenas para este componente -->
<style>
.lgpd-footer-links {
    margin-top: 2rem;
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.lgpd-footer-links .btn {
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.lgpd-footer-links .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Responsividade mobile */
@media (max-width: 768px) {
    .lgpd-footer-links .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .lgpd-footer-links .btn span {
        display: none;
    }
    
    .lgpd-footer-links .card-body {
        text-align: center;
    }
    
    .lgpd-footer-links .d-flex.justify-content-md-end {
        justify-content: center !important;
    }
}
</style>