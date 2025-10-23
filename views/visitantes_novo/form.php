<?php
// Incluir FormService para componentes padronizados
require_once '../../src/services/FormService.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($visitante) ? 'Editar' : 'Novo' ?> Visitante - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile']) ?></span>
                        <div class="dropdown-divider"></div>
                        <a href="/logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i>Sair
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- 🎯 NavigationService: Navegação Unificada -->
        <?= NavigationService::renderSidebar() ?>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= isset($visitante) ? 'Editar' : 'Novo' ?> Visitante</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Dados do Visitante</h3>
                                </div>
                                
                                <form method="POST" action="/visitantes?action=<?= isset($visitante) ? 'update' : 'save' ?>">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <?php if (isset($visitante)): ?>
                                        <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <?php if (isset($error)): ?>
                                            <?= FormService::renderAlert($error, 'danger') ?>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label for="nome">Nome *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?= htmlspecialchars($visitante['nome'] ?? '') ?>" required>
                                        </div>
                                        
                                        <!-- Seletor de Tipo de Documento -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="doc_type">Tipo de Documento</label>
                                                    <select class="form-control" id="doc_type" name="doc_type">
                                                        <option value="">CPF (padrão)</option>
                                                        <optgroup label="🇧🇷 Documentos Brasileiros">
                                                            <option value="CPF" <?= ($visitante['doc_type'] ?? '') === 'CPF' ? 'selected' : '' ?>>CPF</option>
                                                            <option value="RG" <?= ($visitante['doc_type'] ?? '') === 'RG' ? 'selected' : '' ?>>RG</option>
                                                            <option value="CNH" <?= ($visitante['doc_type'] ?? '') === 'CNH' ? 'selected' : '' ?>>CNH</option>
                                                        </optgroup>
                                                        <optgroup label="🌎 Documentos Internacionais">
                                                            <option value="Passaporte" <?= ($visitante['doc_type'] ?? '') === 'Passaporte' ? 'selected' : '' ?>>Passaporte</option>
                                                            <option value="RNE" <?= ($visitante['doc_type'] ?? '') === 'RNE' ? 'selected' : '' ?>>RNE</option>
                                                            <option value="DNI" <?= ($visitante['doc_type'] ?? '') === 'DNI' ? 'selected' : '' ?>>DNI</option>
                                                            <option value="CI" <?= ($visitante['doc_type'] ?? '') === 'CI' ? 'selected' : '' ?>>CI</option>
                                                            <option value="Outros" <?= ($visitante['doc_type'] ?? '') === 'Outros' ? 'selected' : '' ?>>Outros</option>
                                                        </optgroup>
                                                    </select>
                                                    <small class="form-text text-muted">Deixe vazio para CPF</small>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="doc_number">Número do Documento *</label>
                                                    <input type="text" class="form-control" id="doc_number" name="doc_number" 
                                                           value="<?= htmlspecialchars($visitante['doc_number'] ?? '') ?>" 
                                                           placeholder="Digite o número" required>
                                                    <small class="form-text text-muted">Máscara automática por tipo</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3" id="country_container">
                                                <div class="form-group">
                                                    <label for="doc_country">País</label>
                                                    <input type="text" class="form-control" id="doc_country" name="doc_country" 
                                                           value="<?= htmlspecialchars($visitante['doc_country'] ?? '') ?>" 
                                                           placeholder="Brasil">
                                                    <small class="form-text text-muted">Nome do país</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Campo CPF legado (hidden, preenchido via JS ou direto se vazio) -->
                                        <input type="hidden" id="cpf" name="cpf" value="<?= htmlspecialchars($visitante['cpf'] ?? '') ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="empresa">Empresa</label>
                                                    <input type="text" class="form-control" id="empresa" name="empresa" 
                                                           value="<?= htmlspecialchars($visitante['empresa'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="funcionario_responsavel">Funcionário Responsável</label>
                                                    <input type="text" class="form-control" id="funcionario_responsavel" name="funcionario_responsavel" 
                                                           value="<?= htmlspecialchars($visitante['funcionario_responsavel'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="placa_veiculo">Placa de Veículo *</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="placa_veiculo" name="placa_veiculo" 
                                                       value="<?= htmlspecialchars($visitante['placa_veiculo'] ?? '') ?>" 
                                                       placeholder="ABC-1234" style="text-transform: uppercase;" required>
                                                <span class="input-group-text">
                                                    <input type="checkbox" id="ape_checkbox" <?= ($visitante['placa_veiculo'] ?? '') === 'APE' ? 'checked' : '' ?>>
                                                    <label for="ape_checkbox" class="ml-1 mb-0">A pé</label>
                                                </span>
                                            </div>
                                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="setor">Setor</label>
                                                    <input type="text" class="form-control" id="setor" name="setor" 
                                                           value="<?= htmlspecialchars($visitante['setor'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="hora_entrada">Hora de Entrada</label>
                                                    <input type="datetime-local" class="form-control" id="hora_entrada" name="hora_entrada" 
                                                           value="<?= $visitante['hora_entrada'] ? date('Y-m-d\TH:i', strtotime($visitante['hora_entrada'])) : '' ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="hora_saida">Hora de Saída</label>
                                                    <input type="datetime-local" class="form-control" id="hora_saida" name="hora_saida" 
                                                           value="<?= $visitante['hora_saida'] ? date('Y-m-d\TH:i', strtotime($visitante['hora_saida'])) : '' ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                        <a href="/visitantes" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // ==================== SELETOR DE TIPO DE DOCUMENTO ====================
        function setupDocumentSelector() {
            const $docType = $('#doc_type');
            const $docNumber = $('#doc_number');
            const $docCountry = $('#doc_country');
            const $countryContainer = $('#country_container');
            const $cpfLegacy = $('#cpf');
            
            console.log('🔍 MODO EDIÇÃO - Valores iniciais:');
            console.log('  doc_type:', $docType.val());
            console.log('  doc_number:', $docNumber.val());
            console.log('  doc_country:', $docCountry.val());
            
            // Função centralizada: normalizar tipo vazio como CPF
            function getEffectiveDocType() {
                return ($docType.val() || 'CPF').toUpperCase();
            }
            
            function applyDocumentMask() {
                const docType = getEffectiveDocType(); // Usar função centralizada
                console.log('🎯 applyDocumentMask chamado - docType:', docType, 'valor atual:', $docNumber.val());
                let value;
                
                // Tipo CPF (incluindo quando vazio/padrão)
                if (docType === 'CPF') {
                    // Máscara CPF: 000.000.000-00
                    value = $docNumber.val().replace(/\D/g, ''); // Só remover não-dígitos para CPF
                    value = value.substring(0, 11);
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                    value = value.replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
                    // SEMPRE sincronizar com campo CPF legado quando tipo = CPF ou vazio
                    $cpfLegacy.val(value.replace(/\D/g, ''));
                } else if (docType === 'RG') {
                    // Máscara RG: 00.000.000-0
                    value = $docNumber.val().replace(/[^0-9X]/gi, '').toUpperCase().substring(0, 9);
                    value = value.replace(/(\d{2})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d{1})$/, '$1-$2');
                } else if (docType === 'CNH') {
                    value = $docNumber.val().replace(/\D/g, '').substring(0, 11);
                } else {
                    // Documentos internacionais: permitir letras e números
                    value = $docNumber.val().replace(/[^A-Z0-9]/gi, '').toUpperCase();
                }
                
                console.log('  ➡️ Valor após máscara:', value);
                $docNumber.val(value);
                
                // Mostrar campo país para documentos internacionais
                const isInternational = ['PASSAPORTE', 'PASSPORT', 'RNE', 'DNI', 'CI', 'OUTRO', 'OUTROS'].indexOf(docType) !== -1;
                console.log('  🌍 É documento internacional?', isInternational);
                if (isInternational) {
                    $countryContainer.show();
                } else {
                    $countryContainer.hide();
                    $docCountry.val('');
                }
            }
            
            // ⚠️ CRÍTICO: Remover TODOS os event listeners automáticos que aplicam máscara
            // Isso evita que máscaras sejam aplicadas em modo edição
            $docType.off('change input paste keyup blur');
            $docNumber.off('change input paste keyup blur');
            
            // Apenas aplicar máscara quando usuário MUDAR o tipo manualmente
            $docType.on('change', applyDocumentMask);
            // REMOVIDO: $docNumber.on('input paste keyup blur', applyDocumentMask);
            
            // Garantir sincronização antes do submit
            $('form').on('submit', function() {
                applyDocumentMask(); // Forçar sincronização final
            });
            
            // ⚠️ IMPORTANTE: Só aplicar máscara inicial em modo NOVO (não em modo edição)
            // Se houver valor em doc_number no carregamento, estamos editando - NÃO aplicar máscara
            const isEditMode = $docNumber.val().trim() !== '';
            if (!isEditMode) {
                applyDocumentMask(); // Só aplicar se for novo registro
            } else {
                console.log('⚠️ MODO EDIÇÃO detectado - mantendo valores originais sem aplicar máscara');
                // Em modo edição, apenas mostrar/ocultar campo país
                const docType = getEffectiveDocType();
                const isInternational = ['PASSAPORTE', 'PASSPORT', 'RNE', 'DNI', 'CI', 'OUTRO', 'OUTROS'].indexOf(docType) !== -1;
                if (isInternational) {
                    $countryContainer.show();
                }
            }
        }
        
        setupDocumentSelector();
        
        // ==================== CONTROLE PLACA "A PÉ" ====================
        let previousValue = '';
        
        $('#ape_checkbox').change(function() {
            if ($(this).is(':checked')) {
                previousValue = $('#placa_veiculo').val() !== 'APE' ? $('#placa_veiculo').val() : '';
                $('#placa_veiculo').val('APE').prop('readonly', true);
            } else {
                $('#placa_veiculo').val(previousValue).prop('readonly', false);
            }
        });
        
        if ($('#placa_veiculo').val() === 'APE') {
            $('#ape_checkbox').prop('checked', true);
            $('#placa_veiculo').prop('readonly', true);
        }
    });
    </script>
</body>
</html>