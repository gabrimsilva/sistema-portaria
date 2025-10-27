<?php
/**
 * View: Formulário de Novo Pré-Cadastro de Prestador de Serviço
 * 
 * @version 2.0.0
 * @status DRAFT
 */

$pageTitle = 'Novo Pré-Cadastro - Prestador de Serviço';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>
                        <i class="fas fa-address-card me-2"></i>
                        Novo Pré-Cadastro - Prestador de Serviço
                    </h2>
                    <p class="text-muted">
                        Cadastro reutilizável com validade de 1 ano
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
                    <form id="form-pre-cadastro" method="POST" action="/pre-cadastros/prestadores?action=save">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        
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
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                            <div class="col-md-6">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa">
                            </div>
                        </div>

                        <!-- Documento -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-id-card me-2"></i>
                            Documento
                        </h5>

                        <div class="row mb-3 align-items-end">
                            <div class="col-md-4">
                                <label for="doc_type" class="form-label">
                                    Tipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="doc_type" name="doc_type" required>
                                    <option value="CPF">CPF</option>
                                    <option value="RG">RG</option>
                                    <option value="CNH">CNH</option>
                                    <option value="Passaporte">Passaporte</option>
                                    <option value="RNE">RNE</option>
                                    <option value="DNI">DNI</option>
                                    <option value="CI">CI</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="doc_number" class="form-label">
                                    Número <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="doc_number" name="doc_number" required>
                                <small class="form-text text-muted" id="doc-hint"></small>
                            </div>
                            <div class="col-md-4" id="div-doc-country">
                                <label for="doc_country" class="form-label">País</label>
                                <input type="text" class="form-control" id="doc_country" name="doc_country" value="Brasil">
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
                                       name="placa_veiculo" placeholder="ABC1234 ou APE">
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
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="valid_until" class="form-label">
                                    Válido até <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                       value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
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
                                      rows="3" placeholder="Informações adicionais..."></textarea>
                        </div>

                        <!-- Captura de Foto (Opcional) -->
                        <h5 class="mb-3 mt-4">
                            <i class="fas fa-camera me-2"></i>
                            Foto para Identificação (Opcional)
                        </h5>

                        <div class="mb-3">
                            <div id="prestador-photo-capture-container"></div>
                            <small class="form-text text-muted">
                                A foto será exibida durante o registro de entrada no Dashboard, facilitando a identificação.
                            </small>
                        </div>

                        <!-- Botões -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="/pre-cadastros/prestadores" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>
                                Salvar Pré-Cadastro
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Nota Informativa -->
            <div class="alert alert-info mt-4">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    O que é um Pré-Cadastro?
                </h6>
                <p class="mb-0">
                    O pré-cadastro armazena os dados básicos do prestador (nome, documento, empresa) 
                    com validade de 1 ano. Quando o prestador chegar, o porteiro só precisa buscar 
                    o nome e adicionar funcionário responsável e setor, agilizando o atendimento.
                </p>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/pre-cadastros-form.js"></script>

<!-- Photo Capture Component -->
<script src="/assets/js/photo-capture.js?v=<?= time() ?>"></script>
<script>
    // Inicializar captura de foto para prestador
    document.addEventListener('DOMContentLoaded', function() {
        const photoCapture = new PhotoCapture('prestador-photo-capture-container');
        
        // Interceptar submit do formulário para fazer upload da foto
        const form = document.getElementById('form-pre-cadastro');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            // Enviar formulário principal (com flag ajax=1)
            const submitUrl = form.action + (form.action.includes('?') ? '&' : '?') + 'ajax=1';
            fetch(submitUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cadastro_id) {
                    console.log('✅ Cadastro salvo com ID:', data.cadastro_id);
                    
                    // Verificar se tem foto capturada
                    const capturedImage = photoCapture.getCapturedImage();
                    
                    if (capturedImage) {
                        console.log('📸 Foto capturada, fazendo upload...');
                        
                        // Fazer upload da foto
                        const photoData = new FormData();
                        photoData.append('cadastro_id', data.cadastro_id);
                        photoData.append('photo', capturedImage);
                        photoData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                        
                        fetch('/pre-cadastros/prestadores?action=upload_foto', {
                            method: 'POST',
                            body: photoData
                        })
                        .then(response => response.json())
                        .then(photoResult => {
                            if (photoResult.success) {
                                console.log('✅ Foto salva com sucesso!');
                                window.location.href = '/pre-cadastros/prestadores?success=1';
                            } else {
                                console.warn('⚠️ Foto não foi salva:', photoResult.message);
                                window.location.href = '/pre-cadastros/prestadores?success=1&photo_warning=1';
                            }
                        })
                        .catch(error => {
                            console.error('❌ Erro ao salvar foto:', error);
                            window.location.href = '/pre-cadastros/prestadores?success=1&photo_warning=1';
                        });
                    } else {
                        console.log('ℹ️ Sem foto capturada, redirecionando...');
                        window.location.href = '/pre-cadastros/prestadores?success=1';
                    }
                } else {
                    alert(data.message || 'Erro ao salvar cadastro');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar solicitação');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>