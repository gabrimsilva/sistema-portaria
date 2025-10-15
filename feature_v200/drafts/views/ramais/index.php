<?php
// ================================================
// VIEW: CONSULTA DE RAMAIS
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para views/ sem aprovação!

$pageTitle = 'Consulta de Ramais';
$currentPage = 'ramais';

// Layout header
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-telephone-fill text-primary me-2"></i>
                        Consulta de Ramais
                    </h2>
                    <p class="text-muted mb-0">Encontre ramais de profissionais e brigadistas</p>
                </div>
                <?php if ($authService->hasPermission('brigada.manage')): ?>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarRamal">
                        <i class="bi bi-plus-circle me-1"></i>
                        Adicionar Ramal
                    </button>
                    <button class="btn btn-outline-secondary" id="btnExportarRamais">
                        <i class="bi bi-download me-1"></i>
                        Exportar CSV
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="inputBuscaRamal" 
                                       placeholder="Nome, setor ou ramal...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filtrar por Setor</label>
                            <select class="form-select" id="selectSetorFiltro">
                                <option value="">Todos os setores</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="selectTipoFiltro">
                                <option value="">Todos</option>
                                <option value="brigadista">Brigadistas</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <span id="totalRamais">0</span> ramais encontrados
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div id="loadingRamais" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2 text-muted">Carregando ramais...</p>
                    </div>
                    
                    <div id="listaRamais" class="table-responsive d-none">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="35%">Nome</th>
                                    <th width="20%">Setor</th>
                                    <th width="20%">Empresa</th>
                                    <th width="15%">Ramal</th>
                                    <th width="10%" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyRamais">
                                <!-- Preenchido via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div id="nenhumRamalEncontrado" class="text-center py-5 d-none">
                        <i class="bi bi-telephone-x display-1 text-muted"></i>
                        <p class="mt-3 text-muted">Nenhum ramal encontrado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Ramal -->
<div class="modal fade" id="modalAdicionarRamal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-telephone-plus me-2"></i>
                    Adicionar Ramal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAdicionarRamal">
                    <div class="mb-3">
                        <label class="form-label">Profissional *</label>
                        <select class="form-select" id="selectProfissional" required>
                            <option value="">Selecione...</option>
                        </select>
                        <div class="form-text">Selecione o profissional para adicionar ramal</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ramal *</label>
                        <input type="text" class="form-control" id="inputRamal" 
                               placeholder="Ex: 1234" maxlength="10" required>
                        <div class="form-text">Apenas números</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarRamal">
                    <i class="bi bi-check-circle me-1"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Ramal -->
<div class="modal fade" id="modalEditarRamal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Ramal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarRamal">
                    <input type="hidden" id="editProfissionalId">
                    <div class="mb-3">
                        <label class="form-label">Profissional</label>
                        <input type="text" class="form-control" id="editNomeProfissional" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ramal *</label>
                        <input type="text" class="form-control" id="editRamal" 
                               placeholder="Ex: 1234" maxlength="10" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAtualizarRamal">
                    <i class="bi bi-check-circle me-1"></i>
                    Atualizar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/ramais.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
