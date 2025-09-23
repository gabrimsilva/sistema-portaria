<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Visitante - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #video {
            width: 100%;
            max-width: 400px;
            height: auto;
        }
        #canvas {
            display: none;
        }
        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            border: 2px solid #ddd;
            border-radius: 8px;
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
                <img src="/logo.jpg" alt="Renner Logo" class="brand-image img-circle elevation-3" style="width: 40px; height: 40px; object-fit: contain;">
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
                            <a href="/visitors" class="nav-link active">
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
                            <a href="/access" class="nav-link">
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
                            <h1 class="m-0">Cadastro de Visitante</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="/visitors">Visitantes</a></li>
                                <li class="breadcrumb-item active">Cadastro</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Formulário -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Dados do Visitante</h3>
                                </div>
                                
                                <form method="POST" action="/visitors?action=save" enctype="multipart/form-data">
                                    <?= CSRFProtection::getHiddenInput() ?>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nome">Nome Completo *</label>
                                                    <input type="text" class="form-control" name="nome" id="nome" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="cpf">CPF/RG</label>
                                                    <input type="text" class="form-control" name="cpf" id="cpf">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="empresa">Empresa</label>
                                                    <input type="text" class="form-control" name="empresa" id="empresa">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="pessoa_visitada">Pessoa a Visitar *</label>
                                                    <input type="text" class="form-control" name="pessoa_visitada" id="pessoa_visitada" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email</label>
                                                    <input type="email" class="form-control" name="email" id="email">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="telefone">Telefone</label>
                                                    <input type="tel" class="form-control" name="telefone" id="telefone">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Foto</label>
                                            <input type="file" class="form-control" name="foto" accept="image/*">
                                            <input type="hidden" name="photo_data" id="photo_data">
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Salvar
                                        </button>
                                        <a href="/visitors" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Voltar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Captura de Foto -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Captura de Foto</h3>
                                </div>
                                
                                <div class="card-body text-center">
                                    <video id="video" autoplay></video>
                                    <canvas id="canvas"></canvas>
                                    
                                    <div id="photo-preview" style="display: none;">
                                        <img id="captured-photo" class="photo-preview" alt="Foto capturada">
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-info" id="start-camera">
                                            <i class="fas fa-camera"></i> Iniciar Câmera
                                        </button>
                                        <button type="button" class="btn btn-success" id="capture-photo" style="display: none;">
                                            <i class="fas fa-camera"></i> Capturar Foto
                                        </button>
                                        <button type="button" class="btn btn-warning" id="retake-photo" style="display: none;">
                                            <i class="fas fa-redo"></i> Nova Foto
                                        </button>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            A foto será utilizada para identificação do visitante
                                        </small>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    let stream;
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let ctx = canvas.getContext('2d');
    
    document.getElementById('start-camera').addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = 'block';
            document.getElementById('start-camera').style.display = 'none';
            document.getElementById('capture-photo').style.display = 'inline-block';
        } catch (err) {
            alert('Erro ao acessar a câmera: ' + err.message);
        }
    });
    
    document.getElementById('capture-photo').addEventListener('click', function() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0);
        
        let photoData = canvas.toDataURL('image/jpeg', 0.8);
        document.getElementById('photo_data').value = photoData;
        
        document.getElementById('captured-photo').src = photoData;
        document.getElementById('photo-preview').style.display = 'block';
        
        video.style.display = 'none';
        document.getElementById('capture-photo').style.display = 'none';
        document.getElementById('retake-photo').style.display = 'inline-block';
        
        // Stop camera
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
    
    document.getElementById('retake-photo').addEventListener('click', function() {
        document.getElementById('photo-preview').style.display = 'none';
        document.getElementById('photo_data').value = '';
        document.getElementById('retake-photo').style.display = 'none';
        document.getElementById('start-camera').style.display = 'inline-block';
    });
    </script>
</body>
</html>