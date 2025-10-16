<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Importação de Profissionais - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            background-color: #e9ecef;
            border-color: #0056b3;
        }
        .upload-area.dragover {
            background-color: #cfe2ff;
            border-color: #0a58ca;
        }
        .upload-icon {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }
        .file-info {
            display: none;
            margin-top: 20px;
        }
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .stat-card {
            border-left: 4px solid #007bff;
        }
    </style>
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
        
        <!-- Sidebar -->
        <?= NavigationService::renderSidebar() ?>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Importação de Profissionais</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-info stat-card">
                                <div class="inner">
                                    <h3><?= number_format($stats['total'] ?? 0) ?></h3>
                                    <p>Total de Profissionais</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-success stat-card">
                                <div class="inner">
                                    <h3><?= number_format($stats['com_fre'] ?? 0) ?></h3>
                                    <p>Com FRE Cadastrado</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-warning stat-card">
                                <div class="inner">
                                    <h3><?= number_format($stats['com_data_admissao'] ?? 0) ?></h3>
                                    <p>Com Data de Admissão</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instruções -->
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Instruções de Importação</h5>
                        <ul class="mb-0">
                            <li>O arquivo deve ser no formato <strong>CSV</strong> ou <strong>XLSX</strong></li>
                            <li>Colunas obrigatórias: <strong>Nome, Setor, Data de Admissão, FRE</strong></li>
                            <li>Registros com nomes duplicados serão <strong>ignorados</strong></li>
                            <li>Formato da data: <strong>DD/MM/AAAA</strong> (ex: 15/03/2023)</li>
                        </ul>
                    </div>
                    
                    <!-- Área de Upload -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cloud-upload-alt"></i> Upload de Arquivo
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h4>Arraste o arquivo aqui</h4>
                                <p class="text-muted">ou clique para selecionar</p>
                                <input type="file" id="fileInput" accept=".csv,.xlsx,.xls" style="display: none;">
                            </div>
                            
                            <div class="file-info" id="fileInfo">
                                <div class="alert alert-success">
                                    <i class="fas fa-file-alt"></i>
                                    <strong id="fileName"></strong> 
                                    <span class="text-muted">(<span id="fileSize"></span>)</span>
                                    <button type="button" class="close" id="removeFile">
                                        <span>&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview dos Dados -->
                    <div class="card" id="previewCard" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-eye"></i> Preview dos Dados
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="preview-table">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nome</th>
                                            <th>Setor</th>
                                            <th>Data de Admissão</th>
                                            <th>FRE</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-lg" id="btnImport">
                                    <i class="fas fa-download"></i> Importar Dados
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg ml-2" id="btnCancel">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resultado da Importação -->
                    <div class="card" id="resultCard" style="display: none;">
                        <div class="card-header bg-success">
                            <h3 class="card-title">
                                <i class="fas fa-check-circle"></i> Resultado da Importação
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Importados</span>
                                            <span class="info-box-number" id="importedCount">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-exclamation"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Ignorados (Duplicados)</span>
                                            <span class="info-box-number" id="skippedCount">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Erros</span>
                                            <span class="info-box-number" id="errorCount">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalhes dos Erros -->
                            <div id="errorDetailsCard" style="display: none;" class="mt-3">
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Detalhes dos Erros</h5>
                                    <div id="errorDetailsList" class="mt-2" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Erros serão inseridos aqui via JavaScript -->
                                    </div>
                                    <small class="text-muted">Mostrando até 50 primeiros erros</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="/importacao" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Nova Importação
                                </a>
                                <a href="/profissionais-renner" class="btn btn-info ml-2">
                                    <i class="fas fa-list"></i> Ver Profissionais
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        
        <footer class="main-footer">
            <strong>&copy; 2024 Sistema de Controle de Acesso</strong> - Todos os direitos reservados.
        </footer>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        $(document).ready(function() {
            let uploadedFile = null;
            let tempFileName = null;
            
            // Drag and Drop
            const uploadArea = $('#uploadArea');
            const fileInput = $('#fileInput');
            
            uploadArea.on('click', function() {
                fileInput.click();
            });
            
            uploadArea.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            });
            
            fileInput.on('change', function() {
                if (this.files.length > 0) {
                    handleFile(this.files[0]);
                }
            });
            
            $('#removeFile').on('click', function() {
                resetUpload();
            });
            
            $('#btnCancel').on('click', function() {
                resetUpload();
            });
            
            $('#btnImport').on('click', function() {
                processImport();
            });
            
            function handleFile(file) {
                const validExtensions = ['csv', 'xlsx', 'xls'];
                const extension = file.name.split('.').pop().toLowerCase();
                
                if (!validExtensions.includes(extension)) {
                    alert('Formato inválido! Use CSV ou XLSX');
                    return;
                }
                
                uploadedFile = file;
                
                $('#fileName').text(file.name);
                $('#fileSize').text(formatFileSize(file.size));
                $('#fileInfo').fadeIn();
                uploadArea.hide();
                
                uploadFile(file);
            }
            
            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));
                
                $.ajax({
                    url: '/importacao?action=upload',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            tempFileName = response.data.temp_file;
                            showPreview(file);
                        } else {
                            alert('Erro: ' + response.message);
                            resetUpload();
                        }
                    },
                    error: function() {
                        alert('Erro ao fazer upload do arquivo');
                        resetUpload();
                    }
                });
            }
            
            function showPreview(file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const content = e.target.result;
                    const lines = content.split('\n').filter(line => line.trim() !== '');
                    
                    if (lines.length < 2) {
                        alert('Arquivo vazio ou inválido');
                        resetUpload();
                        return;
                    }
                    
                    const previewBody = $('#previewBody');
                    previewBody.empty();
                    
                    const header = lines[0].replace(/^\uFEFF/, '').split(',');
                    
                    const nomeIndex = header.findIndex(col => col.toLowerCase().includes('nome'));
                    const setorIndex = header.findIndex(col => col.toLowerCase().includes('setor'));
                    const dataIndex = header.findIndex(col => col.toLowerCase().includes('admis'));
                    const freIndex = header.findIndex(col => col.toLowerCase().includes('fre'));
                    
                    const maxPreview = Math.min(lines.length - 1, 10);
                    
                    for (let i = 1; i <= maxPreview; i++) {
                        const cols = lines[i].split(',');
                        
                        const nome = cols[nomeIndex] || '-';
                        const setor = cols[setorIndex] || '-';
                        const data = cols[dataIndex] || '-';
                        const fre = cols[freIndex] || '-';
                        
                        previewBody.append(`
                            <tr>
                                <td>${i}</td>
                                <td>${nome.trim()}</td>
                                <td>${setor.trim()}</td>
                                <td>${data.trim()}</td>
                                <td>${fre.trim()}</td>
                            </tr>
                        `);
                    }
                    
                    if (lines.length > 11) {
                        previewBody.append(`
                            <tr class="table-info">
                                <td colspan="5" class="text-center">
                                    <strong>... e mais ${lines.length - 11} registros</strong>
                                </td>
                            </tr>
                        `);
                    }
                    
                    $('#previewCard').fadeIn();
                };
                
                reader.onerror = function() {
                    alert('Erro ao ler o arquivo');
                    resetUpload();
                };
                
                reader.readAsText(file);
            }
            
            function processImport() {
                if (!tempFileName) {
                    alert('Nenhum arquivo carregado');
                    return;
                }
                
                $('#btnImport').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importando...');
                
                $.ajax({
                    url: '/importacao?action=process',
                    type: 'POST',
                    data: {
                        temp_file: tempFileName,
                        csrf_token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            showResult(response.data);
                        } else {
                            alert('Erro: ' + response.message);
                            $('#btnImport').prop('disabled', false).html('<i class="fas fa-download"></i> Importar Dados');
                        }
                    },
                    error: function() {
                        alert('Erro ao processar importação');
                        $('#btnImport').prop('disabled', false).html('<i class="fas fa-download"></i> Importar Dados');
                    }
                });
            }
            
            function showResult(data) {
                $('#previewCard').hide();
                $('#fileInfo').hide();
                
                $('#importedCount').text(data.imported || 0);
                $('#skippedCount').text(data.skipped || 0);
                $('#errorCount').text(data.errors || 0);
                
                // Mostrar detalhes dos erros se houver
                if (data.errors > 0 && data.error_details && data.error_details.length > 0) {
                    const errorList = $('#errorDetailsList');
                    errorList.empty();
                    
                    const ul = $('<ul class="mb-0"></ul>');
                    data.error_details.forEach(function(error) {
                        ul.append(`<li>${error}</li>`);
                    });
                    errorList.append(ul);
                    
                    $('#errorDetailsCard').fadeIn();
                }
                
                $('#resultCard').fadeIn();
            }
            
            function resetUpload() {
                uploadedFile = null;
                tempFileName = null;
                fileInput.val('');
                $('#fileInfo').hide();
                $('#previewCard').hide();
                uploadArea.show();
                $('#btnImport').prop('disabled', false).html('<i class="fas fa-download"></i> Importar Dados');
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }
        });
    </script>
</body>
</html>
