<?php
// ================================================
// COMPONENT: WIDGET CADASTROS EXPIRANDO
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para views/ sem aprovação!
// Este widget deve ser incluído no Dashboard
?>

<!-- Widget: Cadastros Expirando -->
<div class="card shadow-sm mb-4" id="widgetCadastrosExpirando">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
            Cadastros Próximos de Expirar
        </h5>
        <button class="btn btn-sm btn-outline-secondary" id="btnRecarregarExpirando">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
    <div class="card-body p-0">
        <!-- Loading -->
        <div id="loadingExpirando" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2 text-muted small">Verificando cadastros...</p>
        </div>

        <!-- Tabs de Tipo -->
        <ul class="nav nav-tabs px-3 pt-3 d-none" id="tabsExpirando">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#visitantesExpirando">
                    <i class="bi bi-person me-1"></i>Visitantes
                    <span class="badge bg-warning text-dark ms-1" id="badgeVisitantesExp">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#prestadoresExpirando">
                    <i class="bi bi-briefcase me-1"></i>Prestadores
                    <span class="badge bg-warning text-dark ms-1" id="badgePrestadoresExp">0</span>
                </a>
            </li>
        </ul>

        <!-- Conteúdo das Tabs -->
        <div class="tab-content d-none" id="conteudoExpirando">
            <!-- Tab: Visitantes -->
            <div class="tab-pane fade show active" id="visitantesExpirando">
                <div class="list-group list-group-flush" id="listaVisitantesExp">
                    <!-- Preenchido via JavaScript -->
                </div>
            </div>

            <!-- Tab: Prestadores -->
            <div class="tab-pane fade" id="prestadoresExpirando">
                <div class="list-group list-group-flush" id="listaPrestadoresExp">
                    <!-- Preenchido via JavaScript -->
                </div>
            </div>
        </div>

        <!-- Sem cadastros expirando -->
        <div id="semCadastrosExpirando" class="text-center py-5 d-none">
            <i class="bi bi-check-circle-fill text-success display-4"></i>
            <p class="mt-3 text-muted">Nenhum cadastro próximo de expirar</p>
            <small class="text-muted">Todos os cadastros estão dentro da validade</small>
        </div>
    </div>
    <div class="card-footer bg-white text-center d-none" id="footerExpirando">
        <a href="#" class="text-decoration-none" id="linkVerTodosExpirando">
            Ver todos os cadastros expirando
            <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<style>
/* Estilos específicos do widget */
#widgetCadastrosExpirando .list-group-item {
    border-left: 4px solid transparent;
    transition: all 0.2s;
}

#widgetCadastrosExpirando .list-group-item:hover {
    background-color: #f8f9fa;
    border-left-color: #ffc107;
}

#widgetCadastrosExpirando .dias-restantes {
    font-size: 1.1rem;
    font-weight: bold;
}

#widgetCadastrosExpirando .dias-critico {
    color: #dc3545;
}

#widgetCadastrosExpirando .dias-alerta {
    color: #ffc107;
}
</style>

<script src="/assets/js/widget-cadastros-expirando.js"></script>
