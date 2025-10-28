<?php
require_once __DIR__ . '/../../src/services/NavigationService.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Ramais</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .drop-zone {
            border: 3px dashed #007bff;
            border-radius: 10px;
            padding: 50px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .drop-zone:hover, .drop-zone.dragover {
            background-color: #f0f8ff;
            border-color: #0056b3;
        }
        .file-preview {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    
    <?= NavigationService::renderSidebar() ?>
    
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-file-excel"></i> Importar Ramais</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item"><a href="/ramais">Ramais</a></li>
                            <li class="breadcrumb-item active">Importar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        
                        <!-- Instruções -->
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle"></i> Como Importar
                                </h3>
                            </div>
                            <div class="card-body">
                                <h5><i class="fas fa-1"></i> Formato do Arquivo</h5>
                                <p>O arquivo Excel deve ter <strong>3 colunas</strong> na seguinte ordem:</p>
                                <ol>
                                    <li><strong>Área/Setor</strong> - Ex: Marketing, TI, Comercial</li>
                                    <li><strong>Nome</strong> - Nome completo da pessoa</li>
                                    <li><strong>Ramal</strong> - Número do ramal ou telefone</li>
                                </ol>
                                
                                <h5 class="mt-3"><i class="fas fa-2"></i> Tipos Aceitos</h5>
                                <ul>
                                    <li><strong>Excel</strong>: .xlsx, .xls</li>
                                    <li><strong>CSV</strong>: .csv (separado por vírgula)</li>
                                </ul>
                                
                                <h5 class="mt-3"><i class="fas fa-3"></i> Exemplo</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Área</th>
                                                <th>Nome</th>
                                                <th>Ramal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Marketing</td>
                                                <td>João Silva</td>
                                                <td>3401</td>
                                            </tr>
                                            <tr>
                                                <td>TI</td>
                                                <td>Maria Santos</td>
                                                <td>(41) 98765-4321</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulário de Upload -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-upload"></i> 
                                    Upload do Arquivo
                                </h3>
                            </div>
                            
                            <form method="POST" action="/ramais/processar-importacao" enctype="multipart/form-data" id="importForm">
                                <div class="card-body">
                                    
                                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                                    
                                    <!-- Drop Zone -->
                                    <div class="drop-zone" id="dropZone">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h4>Arraste o arquivo aqui</h4>
                                        <p class="text-muted">ou clique para selecionar</p>
                                        <input type="file" 
                                               name="arquivo" 
                                               id="arquivo" 
                                               accept=".xlsx,.xls,.csv"
                                               style="display: none;"
                                               required>
                                    </div>
                                    
                                    <!-- Preview do arquivo -->
                                    <div id="filePreview" class="file-preview" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file-excel fa-2x text-success mr-3"></i>
                                                <strong id="fileName"></strong>
                                                <span class="text-muted ml-2" id="fileSize"></span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" id="removeFile">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Opções -->
                                    <div class="mt-3">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" 
                                                   class="custom-control-input" 
                                                   id="tem_cabecalho" 
                                                   name="tem_cabecalho" 
                                                   value="1"
                                                   checked>
                                            <label class="custom-control-label" for="tem_cabecalho">
                                                A primeira linha é cabeçalho (ignorar)
                                            </label>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success" id="btnImport">
                                        <i class="fas fa-file-import"></i> 
                                        Importar Ramais
                                    </button>
                                    <a href="/ramais" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> 
                                        Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Sistema de Controle de Acesso
        </div>
        <strong>&copy; 2025</strong> Todos os direitos reservados.
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    const dropZone = $('#dropZone');
    const fileInput = $('#arquivo');
    const filePreview = $('#filePreview');
    const importForm = $('#importForm');
    
    // Click na drop zone abre seletor de arquivo
    dropZone.on('click', function() {
        fileInput.click();
    });
    
    // Drag & Drop
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    dropZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    dropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            showFilePreview(files[0]);
        }
    });
    
    // Arquivo selecionado
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            showFilePreview(this.files[0]);
        }
    });
    
    // Mostrar preview do arquivo
    function showFilePreview(file) {
        const fileName = file.name;
        const fileSize = (file.size / 1024).toFixed(2) + ' KB';
        
        $('#fileName').text(fileName);
        $('#fileSize').text('(' + fileSize + ')');
        
        dropZone.hide();
        filePreview.show();
    }
    
    // Remover arquivo
    $('#removeFile').on('click', function() {
        fileInput.val('');
        dropZone.show();
        filePreview.hide();
    });
    
    // Validar antes de enviar
    importForm.on('submit', function(e) {
        if (!fileInput[0].files.length) {
            e.preventDefault();
            alert('Por favor, selecione um arquivo para importar.');
            return false;
        }
        
        $('#btnImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importando...');
    });
});
</script>

</body>
</html>
