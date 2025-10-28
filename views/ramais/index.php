<?php
require_once __DIR__ . '/../../src/services/NavigationService.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Telefônica - Ramais</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .ramal-card {
            border-left: 3px solid #007bff;
            transition: all 0.2s;
        }
        .ramal-card:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        .ramal-card .card-body {
            padding: 0.75rem;
        }
        .area-section {
            margin-bottom: 1.25rem;
        }
        .area-section h4 {
            font-size: 1rem;
            margin-bottom: 0.75rem !important;
        }
        .badge-interno {
            background-color: #28a745;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        .badge-externo {
            background-color: #17a2b8;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
        .ramal-numero {
            font-size: 0.95rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.4rem !important;
        }
        .card-title {
            font-size: 0.9rem !important;
            margin-bottom: 0.4rem !important;
        }
        .search-box {
            margin-bottom: 1rem;
        }
        .stats-card {
            border-top: 2px solid #007bff;
        }
        .small-box {
            margin-bottom: 1rem;
        }
        .small-box .inner h3 {
            font-size: 2rem;
        }
        .small-box .inner p {
            font-size: 0.85rem;
        }
        .btn-group-vertical .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
        .content-header h1 {
            font-size: 1.5rem;
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
                        <h1><i class="fas fa-phone"></i> Lista Telefônica</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Ramais</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <div class="container-fluid">
                
                <!-- DEBUG TEMPORÁRIO -->
                <div class="alert alert-info" style="background-color: #fff3cd; border-color: #ffc107;">
                    <strong>🔍 DEBUG:</strong><br>
                    User ID: <?= $_SESSION['user_id'] ?? 'NÃO DEFINIDO' ?><br>
                    User Profile: <?= $_SESSION['user_profile'] ?? 'NÃO DEFINIDO' ?><br>
                    Can Edit: <?= isset($canEdit) ? ($canEdit ? 'TRUE' : 'FALSE') : 'VARIÁVEL NÃO DEFINIDA' ?>
                </div>
                <!-- FIM DEBUG -->
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-triangle"></i> <?= $_SESSION['error'] ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['warning'] ?>
                    </div>
                    <?php unset($_SESSION['warning']); ?>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info stats-card">
                            <div class="inner">
                                <h3><?= $stats['total'] ?></h3>
                                <p>Total de Ramais</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success stats-card">
                            <div class="inner">
                                <h3><?= $stats['internos'] ?></h3>
                                <p>Ramais Internos</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary stats-card">
                            <div class="inner">
                                <h3><?= $stats['externos'] ?></h3>
                                <p>Telefones Externos</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning stats-card">
                            <div class="inner">
                                <h3><?= $stats['areas'] ?></h3>
                                <p>Áreas/Setores</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-sitemap"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Barra de Ações e Busca -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group search-box">
                            <input type="text" class="form-control" id="searchInput" placeholder="Buscar por nome, área ou ramal...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php if ($canEdit): ?>
                    <div class="col-md-4 text-right">
                        <a href="/ramais/novo" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Novo Ramal
                        </a>
                        <a href="/ramais/importar" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Importar Excel
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Lista de Ramais Agrupados por Área -->
                <?php if (empty($ramaisPorArea)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhum ramal cadastrado ainda.
                        <?php if ($canEdit): ?>
                            <a href="/ramais/importar" class="alert-link">Importe um arquivo Excel</a> para começar.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($ramaisPorArea as $area => $ramais): ?>
                    <div class="area-section" data-area="<?= htmlspecialchars($area) ?>">
                        <h4 class="mb-3">
                            <i class="fas fa-building text-primary"></i> 
                            <strong><?= htmlspecialchars($area) ?></strong>
                            <span class="badge badge-secondary"><?= count($ramais) ?></span>
                        </h4>
                        
                        <div class="row">
                            <?php foreach ($ramais as $ramal): ?>
                            <div class="col-md-4 col-lg-3 col-xl-2 mb-2 ramal-item" 
                                 data-nome="<?= htmlspecialchars(strtolower($ramal['nome'])) ?>"
                                 data-ramal="<?= htmlspecialchars($ramal['ramal']) ?>">
                                <div class="card ramal-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title mb-2">
                                                    <i class="fas fa-user text-muted"></i> 
                                                    <?= htmlspecialchars($ramal['nome']) ?>
                                                </h5>
                                                <p class="ramal-numero mb-2">
                                                    <i class="fas fa-phone-alt"></i> 
                                                    <?= htmlspecialchars($ramal['ramal']) ?>
                                                </p>
                                                <span class="badge badge-<?= $ramal['tipo'] === 'interno' ? 'interno' : 'externo' ?>">
                                                    <?= $ramal['tipo'] === 'interno' ? 'Ramal Interno' : 'Telefone Externo' ?>
                                                </span>
                                                <?php if (!empty($ramal['observacoes'])): ?>
                                                <p class="text-muted small mt-2 mb-0">
                                                    <i class="fas fa-info-circle"></i> 
                                                    <?= htmlspecialchars($ramal['observacoes']) ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($canEdit): ?>
                                            <div class="btn-group-vertical">
                                                <a href="/ramais/editar?id=<?= $ramal['id'] ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger btn-delete" 
                                                        data-id="<?= $ramal['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($ramal['nome']) ?>"
                                                        title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
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

<?php if ($canEdit): ?>
<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o ramal de <strong id="deleteNome"></strong>?</p>
                <p class="text-muted small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="/ramais/excluir" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Busca em tempo real
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm.length === 0) {
            $('.ramal-item').show();
            $('.area-section').show();
            return;
        }
        
        $('.ramal-item').each(function() {
            const nome = $(this).data('nome');
            const ramal = $(this).data('ramal').toString().toLowerCase();
            const area = $(this).closest('.area-section').data('area').toString().toLowerCase();
            
            if (nome.includes(searchTerm) || ramal.includes(searchTerm) || area.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Ocultar áreas sem resultados
        $('.area-section').each(function() {
            const visibleItems = $(this).find('.ramal-item:visible').length;
            if (visibleItems === 0) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
    
    <?php if ($canEdit): ?>
    // Confirmação de exclusão
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        const nome = $(this).data('nome');
        
        $('#deleteId').val(id);
        $('#deleteNome').text(nome);
        $('#deleteModal').modal('show');
    });
    <?php endif; ?>
});
</script>

</body>
</html>
