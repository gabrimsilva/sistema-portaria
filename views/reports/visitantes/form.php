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
                                
                                <form method="POST" action="/reports/visitantes?action=<?= isset($visitante) ? 'update' : 'save' ?>">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <?php if (isset($visitante)): ?>
                                        <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <?php if (isset($error)): ?>
                                            <div class="alert alert-danger">
                                                <?= htmlspecialchars($error) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="form-group">
                                            <label for="nome">Nome Completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?= htmlspecialchars($visitante['nome'] ?? '') ?>" required>
                                        </div>
                                        
                                        <!-- Campos de Documento -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="doc_type">Tipo de Documento</label>
                                                    <select class="form-control" id="doc_type" name="doc_type">
                                                        <option value="">CPF (padrão)</option>
                                                        <option value="RG" <?= ($visitante['doc_type'] ?? '') === 'RG' ? 'selected' : '' ?>>RG</option>
                                                        <option value="CNH" <?= ($visitante['doc_type'] ?? '') === 'CNH' ? 'selected' : '' ?>>CNH</option>
                                                        <option value="PASSAPORTE" <?= ($visitante['doc_type'] ?? '') === 'PASSAPORTE' ? 'selected' : '' ?>>Passaporte</option>
                                                        <option value="RNE" <?= ($visitante['doc_type'] ?? '') === 'RNE' ? 'selected' : '' ?>>RNE (Registro Nacional de Estrangeiro)</option>
                                                        <option value="DNI" <?= ($visitante['doc_type'] ?? '') === 'DNI' ? 'selected' : '' ?>>DNI (Documento Nacional de Identidad)</option>
                                                        <option value="CI" <?= ($visitante['doc_type'] ?? '') === 'CI' ? 'selected' : '' ?>>CI (Cédula de Identidad)</option>
                                                        <option value="OUTROS" <?= ($visitante['doc_type'] ?? '') === 'OUTROS' ? 'selected' : '' ?>>Outros</option>
                                                    </select>
                                                    <small class="text-muted">Deixe vazio para CPF</small>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <label for="doc_number">Número do Documento *</label>
                                                    <input type="text" class="form-control" id="doc_number" name="doc_number" 
                                                           value="<?= htmlspecialchars($visitante['doc_number'] ?? '') ?>" required>
                                                    <small class="text-muted">Máscara automática por tipo</small>
                                                </div>
                                            </div>
                                            <div id="country_container" class="col-md-3" style="display: <?= !empty($visitante['doc_country']) ? 'block' : 'none' ?>;">
                                                <div class="form-group">
                                                    <label for="doc_country">País</label>
                                                    <input type="text" class="form-control" id="doc_country" name="doc_country" 
                                                           value="<?= htmlspecialchars($visitante['doc_country'] ?? 'Brasil') ?>" placeholder="Brasil">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Campo CPF oculto para compatibilidade -->
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
                                        <a href="/reports/visitantes" class="btn btn-secondary">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="/assets/js/document-validator.js"></script>
    
    <script>
    $(document).ready(function() {
        let previousValue = '';
        
        // ========================================
        // CONTROLE DE TIPO DE DOCUMENTO
        // ========================================
        
        // Função para mostrar/ocultar campo de país
        function toggleCountryField() {
            const docType = $('#doc_type').val();
            const internationalDocs = ['PASSAPORTE', 'RNE', 'DNI', 'CI', 'OUTROS'];
            
            if (internationalDocs.includes(docType)) {
                $('#country_container').show();
            } else {
                $('#country_container').hide();
                $('#doc_country').val('Brasil');
            }
        }
        
        // Aplicar máscara de documento dinamicamente
        function applyDocumentMask() {
            const docType = $('#doc_type').val() || 'CPF';
            const $docNumber = $('#doc_number');
            
            // Remover máscara anterior
            $docNumber.unmask();
            
            // Aplicar nova máscara
            if (docType === 'CPF' || docType === '') {
                $docNumber.mask('000.000.000-00', {reverse: true});
            } else if (docType === 'RG') {
                $docNumber.mask('00.000.000-A', {
                    translation: {
                        'A': {pattern: /[0-9Xx]/, optional: true}
                    }
                });
            } else if (docType === 'CNH') {
                $docNumber.mask('00000000000');
            } else {
                // Documentos internacionais: sem máscara (alfanuméricos)
                $docNumber.attr('maxlength', 20);
            }
            
            // Sincronizar com campo CPF oculto se for CPF
            if (docType === 'CPF' || docType === '') {
                $('#cpf').val($docNumber.val());
            }
        }
        
        // Evento: mudança de tipo de documento
        $('#doc_type').on('change', function() {
            toggleCountryField();
            applyDocumentMask();
        });
        
        // Evento: digitação no número do documento
        $('#doc_number').on('input', function() {
            const docType = $('#doc_type').val() || 'CPF';
            
            // Sincronizar com campo CPF oculto se for CPF
            if (docType === 'CPF' || docType === '') {
                $('#cpf').val($(this).val());
            }
        });
        
        // Inicializar estado do campo país e máscara
        toggleCountryField();
        applyDocumentMask();
        
        // ========================================
        // CONTROLE DE CHECKBOX "A PÉ"
        // ========================================
        
        $('#ape_checkbox').change(function() {
            if ($(this).is(':checked')) {
                previousValue = $('#placa_veiculo').val() !== 'APE' ? $('#placa_veiculo').val() : '';
                $('#placa_veiculo').val('APE').prop('readonly', true);
            } else {
                $('#placa_veiculo').val(previousValue).prop('readonly', false);
            }
        });
        
        // Verificar estado inicial
        if ($('#placa_veiculo').val() === 'APE') {
            $('#ape_checkbox').prop('checked', true);
            $('#placa_veiculo').prop('readonly', true);
        }
    });
    </script>
</body>
</html>