<?php
// ================================================
// COMPONENT: MODAL ENTRADA RETROATIVA
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para views/ sem aprovação!
// Este modal deve ser incluído nas views de Profissionais Renner
?>

<!-- Modal: Entrada Retroativa -->
<div class="modal fade" id="modalEntradaRetroativa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Registrar Entrada Retroativa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Alerta de permissão -->
                <div class="alert alert-warning d-flex align-items-center mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>Atenção!</strong> Esta ação registra uma entrada no passado e requer justificativa detalhada. 
                        Todas as entradas retroativas são auditadas.
                    </div>
                </div>

                <form id="formEntradaRetroativa">
                    <input type="hidden" id="retroProfissionalId">

                    <!-- Dados do Profissional (readonly) -->
                    <div class="card mb-3 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Profissional</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Nome</label>
                                    <p class="mb-0" id="retroNomeProfissional"><strong>-</strong></p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Setor</label>
                                    <p class="mb-0" id="retroSetorProfissional">-</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Documento</label>
                                    <p class="mb-0" id="retroDocProfissional">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data e Hora da Entrada -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data da Entrada *</label>
                            <input type="date" class="form-control" id="retroDataEntrada" required>
                            <div class="form-text">Deve ser uma data no passado</div>
                            <div class="invalid-feedback">Data obrigatória e deve ser no passado</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora da Entrada *</label>
                            <input type="time" class="form-control" id="retroHoraEntrada" required>
                            <div class="form-text">Horário estimado da entrada</div>
                        </div>
                    </div>

                    <!-- Validação de Conflitos -->
                    <div id="alertaConflito" class="alert alert-danger d-none">
                        <i class="bi bi-exclamation-octagon me-2"></i>
                        <strong>Conflito detectado:</strong>
                        <span id="mensagemConflito"></span>
                    </div>

                    <!-- Motivo/Justificativa -->
                    <div class="mb-3">
                        <label class="form-label">Motivo da Entrada Retroativa *</label>
                        <textarea class="form-control" id="retroMotivo" rows="4" 
                                  placeholder="Descreva detalhadamente o motivo da entrada retroativa. Exemplo: Colaborador esqueceu de registrar entrada na segunda-feira pela manhã devido a urgência no setor."
                                  minlength="10" maxlength="500" required></textarea>
                        <div class="form-text">
                            <span id="contadorCaracteres">0</span>/500 caracteres (mínimo 10)
                        </div>
                        <div class="invalid-feedback">Motivo obrigatório com no mínimo 10 caracteres</div>
                    </div>

                    <!-- Checkbox de confirmação -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="retroConfirmacao" required>
                        <label class="form-check-label" for="retroConfirmacao">
                            Confirmo que as informações estão corretas e que esta entrada retroativa é necessária e justificada.
                        </label>
                    </div>
                </form>

                <!-- Preview da Ação -->
                <div id="previewRetroativa" class="card border-primary d-none">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-eye me-2"></i>Preview da Entrada
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Será registrado:</strong></p>
                        <ul class="mb-0">
                            <li>Profissional: <span id="previewNome"></span></li>
                            <li>Data/Hora: <span id="previewDataHora"></span></li>
                            <li>Motivo: <span id="previewMotivo"></span></li>
                            <li>Registrado por: <strong><?php echo $_SESSION['user_name'] ?? 'Usuário atual'; ?></strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btnRegistrarRetroativa">
                    <i class="bi bi-clock-history me-1"></i>Registrar Entrada Retroativa
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/entrada-retroativa.js"></script>
