<?php
/**
 * View: Formulário de Edição de Pré-Cadastro de Prestador
 * 
 * @version 2.0.0
 * @var array $cadastro Dados do cadastro carregados pelo controller
 */

$pageTitle = 'Editar Pré-Cadastro - Prestador de Serviço';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>
                        <i class="fas fa-edit me-2"></i>
                        Editar Pré-Cadastro - Prestador de Serviço
                    </h2>
                    <p class="text-muted">
                        Atualize os dados do cadastro reutilizável
                    </p>
                </div>
                <a href="/pre-cadastros/prestadores" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['flash_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="card">
                <div class="card-body">
                    <form id="form-pre-cadastro" method="POST" action="/pre-cadastros/prestadores?action=update">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($cadastro['id']) ?>">
                        
                        <!-- Dados Pessoais -->
                        <h5 class="mb-3">
                            <i class="fas fa-user me-2"></i>
                            Dados Pessoais
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">
                                    Nome Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= htmlspecialchars($cadastro['nome']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="empresa" class="form-label">
                                    Empresa <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="empresa" name="empresa" 
                                       value="<?= htmlspecialchars($cadastro['empresa'] ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Documento -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-id-card me-2"></i>
                            Documento
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="doc_type" class="form-label">
                                    Tipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="doc_type" name="doc_type" required>
                                    <option value="CPF" <?= ($cadastro['doc_type'] === 'CPF') ? 'selected' : '' ?>>CPF</option>
                                    <option value="RG" <?= ($cadastro['doc_type'] === 'RG') ? 'selected' : '' ?>>RG</option>
                                    <option value="CNH" <?= ($cadastro['doc_type'] === 'CNH') ? 'selected' : '' ?>>CNH</option>
                                    <option value="Passaporte" <?= ($cadastro['doc_type'] === 'Passaporte') ? 'selected' : '' ?>>Passaporte</option>
                                    <option value="RNE" <?= ($cadastro['doc_type'] === 'RNE') ? 'selected' : '' ?>>RNE</option>
                                    <option value="DNI" <?= ($cadastro['doc_type'] === 'DNI') ? 'selected' : '' ?>>DNI</option>
                                    <option value="CI" <?= ($cadastro['doc_type'] === 'CI') ? 'selected' : '' ?>>CI</option>
                                    <option value="Outros" <?= ($cadastro['doc_type'] === 'Outros') ? 'selected' : '' ?>>Outros</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="doc_number" class="form-label">
                                    Número <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="doc_number" name="doc_number" 
                                       value="<?= htmlspecialchars($cadastro['doc_number'] ?? '') ?>" required>
                                <small class="form-text text-muted" id="doc-hint"></small>
                            </div>
                            <div class="col-md-4" id="div-doc-country">
                                <label for="doc_country" class="form-label">País</label>
                                <input type="text" class="form-control" id="doc_country" name="doc_country" 
                                       value="<?= htmlspecialchars($cadastro['doc_country'] ?? 'Brasil') ?>">
                            </div>
                        </div>

                        <!-- Veículo -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-car me-2"></i>
                            Veículo (Opcional)
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="placa_veiculo" class="form-label">Placa</label>
                                <input type="text" class="form-control text-uppercase" id="placa_veiculo" 
                                       name="placa_veiculo" placeholder="ABC1234 ou APE"
                                       value="<?= htmlspecialchars($cadastro['placa_veiculo'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Validade -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Período de Validade
                        </h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="valid_from" class="form-label">
                                    Válido de <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="valid_from" name="valid_from" 
                                       value="<?= htmlspecialchars($cadastro['valid_from']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="valid_until" class="form-label">
                                    Válido até <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                       value="<?= htmlspecialchars($cadastro['valid_until']) ?>" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" id="btn-default-validity" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-magic me-2"></i>
                                    Padrão (+1 ano)
                                </button>
                            </div>
                        </div>

                        <!-- Observações -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-sticky-note me-2"></i>
                            Observações
                        </h5>

                        <div class="mb-3">
                            <textarea class="form-control" id="observacoes" name="observacoes" 
                                      rows="3" placeholder="Informações adicionais..."><?= htmlspecialchars($cadastro['observacoes'] ?? '') ?></textarea>
                        </div>

                        <!-- Botões -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="/pre-cadastros/prestadores" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Atualizar Cadastro
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="alert alert-warning mt-4">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    Atenção
                </h6>
                <p class="mb-0">
                    Alterações neste cadastro serão aplicadas aos futuros registros de entrada. 
                    Registros anteriores não serão afetados.
                </p>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/pre-cadastros-form.js"></script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
