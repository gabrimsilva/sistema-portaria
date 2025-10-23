<?php
/**
 * View: Lista de Pré-Cadastros de Visitantes
 * 
 * @version 2.0.0
 * @status DRAFT
 */

$pageTitle = 'Pré-Cadastros - Visitantes';
require_once __DIR__ . '/../../../views/partials/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Header com Botão de Novo -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>
                <i class="fas fa-address-card me-2"></i>
                Pré-Cadastros de Visitantes
            </h2>
            <p class="text-muted">
                Cadastros reutilizáveis com validade de 1 ano
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/pre-cadastros/visitantes?action=new" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>
                Novo Pré-Cadastro
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                    <h2 class="display-4 font-weight-bold mb-2"><?= $stats['total'] ?? 0 ?></h2>
                    <p class="mb-0"><strong>Total</strong></p>
                    <small>Cadastros ativos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h2 class="display-4 font-weight-bold mb-2"><?= $stats['validos'] ?? 0 ?></h2>
                    <p class="mb-0"><strong>Válidos</strong></p>
                    <small>Prontos para uso</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <h2 class="display-4 font-weight-bold mb-2"><?= $stats['expirando'] ?? 0 ?></h2>
                    <p class="mb-0"><strong>Expirando</strong></p>
                    <small>Próximos 30 dias</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                    <h2 class="display-4 font-weight-bold mb-2"><?= $stats['expirados'] ?? 0 ?></h2>
                    <p class="mb-0"><strong>Expirados</strong></p>
                    <small>Precisam renovação</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="form-filtros" class="row g-3">
                <div class="col-md-3">
                    <label for="filtro-status" class="form-label">Status</label>
                    <select id="filtro-status" class="form-select">
                        <option value="all">Todos</option>
                        <option value="valido">Válidos</option>
                        <option value="expirando">Expirando</option>
                        <option value="expirado">Expirados</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="filtro-busca" class="form-label">Buscar</label>
                    <input type="text" id="filtro-busca" class="form-control" 
                           placeholder="Nome, documento ou empresa...">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="btn-limpar-filtros" class="btn btn-secondary w-100">
                        <i class="fas fa-eraser me-2"></i>
                        Limpar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Cadastros -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="table-cadastros" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Documento</th>
                            <th>Placa</th>
                            <th>Validade</th>
                            <th>Status</th>
                            <th>Entradas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cadastros">
                        <!-- Preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Renovar Cadastro -->
<div class="modal fade" id="modal-renovar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-redo me-2"></i>
                    Renovar Cadastro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>
                    Deseja renovar este cadastro por mais <strong>1 ano</strong>?
                </p>
                <p class="text-muted mb-0">
                    Nova validade: <strong id="nova-validade"></strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-renovacao" class="btn btn-warning">
                    <i class="fas fa-redo me-2"></i>
                    Renovar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript para listagem (implementado em pre-cadastros.js)
document.addEventListener('DOMContentLoaded', function() {
    PreCadastros.init('visitante');
});
</script>

<?php require_once __DIR__ . '/../../../views/partials/footer.php'; ?>
