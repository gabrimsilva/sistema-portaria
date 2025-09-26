<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR Code - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest"></script>
    <style>
        #video {
            width: 100%;
            max-width: 500px;
            height: auto;
            border: 3px solid #007bff;
            border-radius: 10px;
        }
        .scanner-container {
            position: relative;
            display: inline-block;
        }
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #ff0000;
            border-radius: 10px;
            pointer-events: none;
        }
        .result-card {
            display: none;
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
        
        <!-- Main Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <?= LogoService::renderSimpleLogo('renner', 'sidebar'); ?>
                <span class="brand-text font-weight-light">Controle Acesso</span>
            </a>
            
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/visitors" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Visitantes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/employees" class="nav-link">
                                <i class="nav-icon fas fa-id-badge"></i>
                                <p>Funcionários</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/access" class="nav-link active">
                                <i class="nav-icon fas fa-door-open"></i>
                                <p>Controle de Acesso</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/reports" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>Relatórios</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Scanner QR Code</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="/access">Controle de Acesso</a></li>
                                <li class="breadcrumb-item active">Scanner QR</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Scanner de QR Code</h3>
                                </div>
                                
                                <div class="card-body text-center">
                                    <div id="scanner-section">
                                        <p class="text-muted mb-4">Posicione o QR Code do visitante na área demarcada para leitura automática</p>
                                        
                                        <div class="scanner-container mb-3">
                                            <video id="video" autoplay muted></video>
                                            <div class="scanner-overlay"></div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-success" id="start-scanner">
                                                <i class="fas fa-qrcode"></i> Iniciar Scanner
                                            </button>
                                            <button type="button" class="btn btn-danger" id="stop-scanner" style="display: none;">
                                                <i class="fas fa-stop"></i> Parar Scanner
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="result-card card bg-light mt-4" id="result-section">
                                        <div class="card-body">
                                            <h5 class="card-title">QR Code Detectado!</h5>
                                            <p class="card-text" id="qr-result"></p>
                                            
                                            <div class="visitor-info" id="visitor-info" style="display: none;">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3">
                                                        <img id="visitor-photo" src="" alt="Foto" class="img-fluid rounded">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <h6 id="visitor-name"></h6>
                                                        <p class="mb-1"><strong>Empresa:</strong> <span id="visitor-company"></span></p>
                                                        <p class="mb-1"><strong>Visitando:</strong> <span id="visitor-host"></span></p>
                                                        <p class="mb-0"><strong>Status:</strong> <span id="visitor-status" class="badge"></span></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <a href="#" id="register-entry" class="btn btn-success">
                                                        <i class="fas fa-sign-in-alt"></i> Registrar Entrada
                                                    </a>
                                                    <a href="#" id="register-exit" class="btn btn-warning">
                                                        <i class="fas fa-sign-out-alt"></i> Registrar Saída
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-secondary" id="scan-again">
                                                    <i class="fas fa-redo"></i> Escanear Novamente
                                                </button>
                                                <a href="/access" class="btn btn-primary">
                                                    <i class="fas fa-keyboard"></i> Registro Manual
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
    
    <?php
    require_once '../src/services/ErrorHandlerService.php';
    echo ErrorHandlerService::renderComplete(['enableConsoleLog' => true]);
    ?>
    
    <script>
    let codeReader = null;
    let stream = null;
    
    document.getElementById('start-scanner').addEventListener('click', async function() {
        try {
            codeReader = new ZXing.BrowserQRCodeReader();
            
            const videoElement = document.getElementById('video');
            
            // Get camera stream
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment' // Use back camera if available
                } 
            });
            
            videoElement.srcObject = stream;
            
            // Start scanning
            codeReader.decodeFromVideoDevice(undefined, videoElement, (result, err) => {
                if (result) {
                    handleQRResult(result.text);
                }
            });
            
            document.getElementById('start-scanner').style.display = 'none';
            document.getElementById('stop-scanner').style.display = 'inline-block';
            
        } catch (err) {
            if (typeof ErrorHandler !== 'undefined') {
                ErrorHandler.handle(err, 'camera', true);
            } else {
                alert('Erro ao acessar a câmera: ' + err.message);
            }
        }
    });
    
    document.getElementById('stop-scanner').addEventListener('click', function() {
        stopScanner();
    });
    
    document.getElementById('scan-again').addEventListener('click', function() {
        document.getElementById('result-section').style.display = 'none';
        document.getElementById('scanner-section').style.display = 'block';
        document.getElementById('start-scanner').click();
    });
    
    function stopScanner() {
        if (codeReader) {
            codeReader.reset();
        }
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        document.getElementById('start-scanner').style.display = 'inline-block';
        document.getElementById('stop-scanner').style.display = 'none';
    }
    
    async function handleQRResult(qrCode) {
        stopScanner();
        
        document.getElementById('qr-result').textContent = 'Código: ' + qrCode;
        document.getElementById('scanner-section').style.display = 'none';
        document.getElementById('result-section').style.display = 'block';
        
        // Try to find visitor by QR code
        try {
            const response = await fetch('/api/visitor-by-qr.php?qr=' + encodeURIComponent(qrCode));
            const visitor = await response.json();
            
            if (visitor.success) {
                const info = visitor.data;
                document.getElementById('visitor-name').textContent = info.nome;
                document.getElementById('visitor-company').textContent = info.empresa || 'Não informado';
                document.getElementById('visitor-host').textContent = info.pessoa_visitada || 'Não informado';
                
                const statusBadge = document.getElementById('visitor-status');
                statusBadge.textContent = info.status;
                statusBadge.className = 'badge badge-' + (info.status === 'ativo' ? 'success' : 'warning');
                
                if (info.foto) {
                    document.getElementById('visitor-photo').src = '/' + info.foto;
                } else {
                    document.getElementById('visitor-photo').style.display = 'none';
                }
                
                // Set up registration links
                document.getElementById('register-entry').href = '/access?visitor_id=' + info.id + '&action=register';
                document.getElementById('register-exit').href = '/access?visitor_id=' + info.id + '&action=register';
                
                document.getElementById('visitor-info').style.display = 'block';
            } else {
                document.getElementById('qr-result').innerHTML += '<br><span class="text-danger">Visitante não encontrado para este QR Code</span>';
            }
        } catch (error) {
            if (typeof ErrorHandler !== 'undefined') {
                ErrorHandler.handle(error, 'fetch');
            }
            document.getElementById('qr-result').innerHTML += '<br><span class="text-warning">Erro ao buscar informações do visitante</span>';
        }
    }
    </script>
</body>
</html>