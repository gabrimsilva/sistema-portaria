<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title><?= $pageTitle ?? 'Brigada de Incêndio' ?> - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .search-results .list-group-item {
            cursor: pointer;
            border-left: none;
            border-right: none;
        }
        .search-results .list-group-item:hover {
            background-color: #f8f9fa;
        }
        .search-results .list-group-item:first-child {
            border-top: none;
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
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item-text"><?= htmlspecialchars($_SESSION['user_profile'] ?? 'Porteiro') ?></span>
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
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">
                                <i class="fas fa-fire-extinguisher text-danger mr-2"></i>
                                Brigada de Incêndio
                            </h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active">Brigada</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    
                    <!-- Flash Messages -->
                    <?php if (isset($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['flash_success']) ?>
                        </div>
                        <?php unset($_SESSION['flash_success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= htmlspecialchars($_SESSION['flash_error']) ?>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>
                    
                    <!-- Card: Adicionar Brigadista -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-plus mr-2"></i>
                                Adicionar Brigadista
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group position-relative">
                                        <label for="searchProfessional">Buscar Profissional</label>
                                        <input 
                                            type="text" 
                                            id="searchProfessional" 
                                            class="form-control" 
                                            placeholder="Digite o nome do profissional..."
                                            autocomplete="off"
                                        />
                                        <small class="form-text text-muted">
                                            Digite pelo menos 2 caracteres para buscar
                                        </small>
                                        
                                        <!-- Resultados da busca -->
                                        <div id="searchResults" class="search-results list-group" style="display: none;"></div>
                                    </div>
                                    
                                    <!-- Formulário de adição (oculto inicialmente) -->
                                    <form id="formAddBrigadista" method="POST" action="/brigada/add" style="display: none;">
                                        <?= CSRFProtection::getHiddenInput() ?>
                                        <input type="hidden" name="professional_id" id="selectedProfessionalId" />
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Profissional selecionado: <strong id="selectedProfessionalName"></strong>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="ramal">
                                                <i class="fas fa-phone mr-1"></i>
                                                Ramal Interno
                                            </label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                id="ramal" 
                                                name="ramal" 
                                                placeholder="Ex: 5432" 
                                                maxlength="20"
                                            />
                                            <small class="form-text text-muted">
                                                Ramal para contato em caso de emergência (opcional)
                                            </small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus-circle mr-2"></i>
                                            Adicionar à Brigada
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="cancelSelection()">
                                            <i class="fas fa-times mr-2"></i>
                                            Cancelar
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total de Brigadistas</span>
                                            <span class="info-box-number"><?= count($brigadistas ?? []) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card: Lista de Brigadistas -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-2"></i>
                                Brigadistas Ativos
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($brigadistas)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p class="mb-0">Nenhum brigadista cadastrado ainda.</p>
                                    <small>Use o formulário acima para adicionar profissionais à brigada.</small>
                                </div>
                            <?php else: ?>
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px">#</th>
                                            <th style="width: 80px">Foto</th>
                                            <th>Profissional</th>
                                            <th>Setor</th>
                                            <th style="width: 150px">Data de Inclusão</th>
                                            <th style="width: 100px">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($brigadistas as $index => $brigadista): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <div class="photo-upload-cell" data-brigadista-id="<?= (int)$brigadista['professional_id'] ?>">
                                                        <?php if (!empty($brigadista['foto_url'])): ?>
                                                            <img src="/uploads/<?= htmlspecialchars($brigadista['foto_url']) ?>" 
                                                                 class="img-thumbnail photo-preview" 
                                                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                                                 title="Clique para alterar foto">
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary upload-photo-btn" title="Adicionar foto">
                                                                <i class="fas fa-camera"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <input type="file" 
                                                               class="photo-input d-none" 
                                                               accept="image/jpeg,image/jpg,image/png,image/webp"
                                                               data-brigadista-id="<?= (int)$brigadista['professional_id'] ?>">
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user-shield text-danger mr-2"></i>
                                                    <strong><?= htmlspecialchars($brigadista['professional_name']) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($brigadista['sector'] ?? '-') ?></td>
                                                <td>
                                                    <small>
                                                        <?php
                                                        $date = new DateTime($brigadista['created_at']);
                                                        echo $date->format('d/m/Y H:i');
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <form method="POST" action="/brigada/remove" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este brigadista?');">
                                                        <?= CSRFProtection::getHiddenInput() ?>
                                                        <input type="hidden" name="id" value="<?= (int)$brigadista['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Remover">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </section>
        </div>
        
        <!-- Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; 2025 Sistema de Controle de Acesso.</strong>
            Todos os direitos reservados.
            <div class="float-right d-none d-sm-inline-block">
                <b>Versão</b> 1.0.0
            </div>
        </footer>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    // Busca dinâmica de profissionais com debounce
    let searchTimer;
    const searchInput = document.getElementById('searchProfessional');
    const searchResults = document.getElementById('searchResults');
    const formAdd = document.getElementById('formAddBrigadista');
    const selectedIdInput = document.getElementById('selectedProfessionalId');
    const selectedNameSpan = document.getElementById('selectedProfessionalName');
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimer);
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
            return;
        }
        
        searchTimer = setTimeout(async () => {
            try {
                const response = await fetch(`/api/professionals/search?q=${encodeURIComponent(query)}`);
                
                if (!response.ok) {
                    throw new Error('Erro ao buscar profissionais');
                }
                
                const data = await response.json();
                
                searchResults.innerHTML = '';
                
                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="list-group-item text-muted">Nenhum profissional encontrado</div>';
                    searchResults.style.display = 'block';
                    return;
                }
                
                data.forEach(professional => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                        <strong>${professional.name}</strong>
                        <br>
                        <small class="text-muted"><i class="fas fa-building mr-1"></i>${professional.sector || 'Sem setor'}</small>
                    `;
                    
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        selectProfessional(professional.id, professional.name);
                    });
                    
                    searchResults.appendChild(item);
                });
                
                searchResults.style.display = 'block';
                
            } catch (error) {
                console.error('Erro na busca:', error);
                searchResults.innerHTML = '<div class="list-group-item text-danger">Erro ao buscar profissionais</div>';
                searchResults.style.display = 'block';
            }
        }, 300);
    });
    
    function selectProfessional(id, name) {
        selectedIdInput.value = id;
        selectedNameSpan.textContent = name;
        searchInput.value = name;
        searchResults.style.display = 'none';
        formAdd.style.display = 'block';
    }
    
    function cancelSelection() {
        selectedIdInput.value = '';
        selectedNameSpan.textContent = '';
        searchInput.value = '';
        searchResults.style.display = 'none';
        formAdd.style.display = 'none';
    }
    
    // Fechar resultados ao clicar fora
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Upload de foto do brigadista
    document.addEventListener('DOMContentLoaded', function() {
        // Botões de upload de foto
        document.querySelectorAll('.upload-photo-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cell = this.closest('.photo-upload-cell');
                const fileInput = cell.querySelector('.photo-input');
                fileInput.click();
            });
        });
        
        // Clique na foto para alterar
        document.querySelectorAll('.photo-preview').forEach(img => {
            img.addEventListener('click', function() {
                const cell = this.closest('.photo-upload-cell');
                const fileInput = cell.querySelector('.photo-input');
                fileInput.click();
            });
        });
        
        // Processar upload quando arquivo for selecionado
        document.querySelectorAll('.photo-input').forEach(input => {
            input.addEventListener('change', async function() {
                const file = this.files[0];
                if (!file) return;
                
                // Validar tamanho (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('A foto deve ter no máximo 2MB');
                    this.value = '';
                    return;
                }
                
                // Validar tipo
                if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
                    alert('Apenas imagens JPG, PNG ou WEBP são permitidas');
                    this.value = '';
                    return;
                }
                
                const brigadistaId = this.dataset.brigadistaId;
                const cell = this.closest('.photo-upload-cell');
                
                // Criar FormData
                const formData = new FormData();
                formData.append('photo', file);
                formData.append('professional_id', brigadistaId);
                
                // Obter CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                formData.append('csrf_token', csrfToken);
                
                // Mostrar loading
                cell.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>';
                
                try {
                    const response = await fetch('/brigada/upload-foto', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        // Atualizar preview da foto
                        cell.innerHTML = `
                            <img src="/uploads/${result.foto_url}?t=${Date.now()}" 
                                 class="img-thumbnail photo-preview" 
                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                 title="Clique para alterar foto">
                            <input type="file" 
                                   class="photo-input d-none" 
                                   accept="image/jpeg,image/jpg,image/png,image/webp"
                                   data-brigadista-id="${brigadistaId}">
                        `;
                        
                        // Re-adicionar event listener
                        const newImg = cell.querySelector('.photo-preview');
                        const newInput = cell.querySelector('.photo-input');
                        
                        newImg.addEventListener('click', function() {
                            newInput.click();
                        });
                        
                        newInput.addEventListener('change', arguments.callee);
                        
                        // Mostrar mensagem de sucesso
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.innerHTML = `
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check-circle mr-2"></i>
                            Foto atualizada com sucesso!
                        `;
                        const container = document.querySelector('.container-fluid');
                        const firstCard = container.querySelector('.card');
                        if (firstCard) {
                            container.insertBefore(alert, firstCard);
                        } else {
                            container.prepend(alert);
                        }
                        
                        setTimeout(() => alert.remove(), 3000);
                        
                    } else {
                        throw new Error(result.error || 'Erro ao fazer upload da foto');
                    }
                    
                } catch (error) {
                    console.error('Erro no upload:', error);
                    alert('Erro ao fazer upload da foto: ' + error.message);
                    
                    // Restaurar botão
                    cell.innerHTML = `
                        <button type="button" class="btn btn-sm btn-outline-secondary upload-photo-btn" title="Adicionar foto">
                            <i class="fas fa-camera"></i>
                        </button>
                        <input type="file" 
                               class="photo-input d-none" 
                               accept="image/jpeg,image/jpg,image/png,image/webp"
                               data-brigadista-id="${brigadistaId}">
                    `;
                    
                    // Re-adicionar event listeners
                    const newBtn = cell.querySelector('.upload-photo-btn');
                    const newInput = cell.querySelector('.photo-input');
                    
                    newBtn.addEventListener('click', function() {
                        newInput.click();
                    });
                    
                    newInput.addEventListener('change', arguments.callee);
                }
            });
        });
    });
    </script>
</body>
</html>
