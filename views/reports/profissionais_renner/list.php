<?php
// Verificar permissões para botões inline
require_once __DIR__ . '/../../../src/services/AuthorizationService.php';
$authService = new AuthorizationService();
$canEditInline = $authService->hasPermission('relatorios.editar_linha');
$canDeleteInline = $authService->hasPermission('relatorios.excluir_linha');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Profissionais Renner - Sistema de Controle de Acesso</title>
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
                            <h1 class="m-0">Profissionais Renner</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Profissional cadastrado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Profissional atualizado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Relatório de Entradas - Profissionais Renner</h3>
                                    <p class="text-muted mt-1">Visualização de registros de entrada por data</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros para Relatório -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <form method="GET" class="row align-items-end">
                                            <div class="col-md-2">
                                                <label>Data Inicial:</label>
                                                <input type="date" name="data_inicial" class="form-control" value="<?= htmlspecialchars($_GET['data_inicial'] ?? '') ?>" placeholder="Início">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Data Final:</label>
                                                <input type="date" name="data_final" class="form-control" value="<?= htmlspecialchars($_GET['data_final'] ?? '') ?>" placeholder="Fim">
                                            </div>
                                            <div class="col-md-3">
                                                <label >Nome:</label>
                                                <input type="text" name="search" class="form-control" placeholder="Buscar por nome" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label >Setor:</label>
                                                <select name="setor" class="form-control">
                                                    <option value="">Todos</option>
                                                    <?php foreach ($setores as $s): ?>
                                                        <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($s['setor']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label >Status:</label>
                                                <select name="status" class="form-control">
                                                    <option value="">Todos</option>
                                                    <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Na empresa</option>
                                                    <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Saiu</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-filter"></i> Filtrar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informações do Relatório -->
                            <?php if (isset($pagination)): ?>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-6">
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-list"></i> Total: <?= $pagination['total'] ?> registros
                                        <?php if (!empty($_GET['data_inicial']) || !empty($_GET['data_final'])): ?>
                                            | <i class="fas fa-calendar"></i> Período: 
                                            <?= !empty($_GET['data_inicial']) ? date('d/m/Y', strtotime($_GET['data_inicial'])) : '...' ?>
                                            até
                                            <?= !empty($_GET['data_final']) ? date('d/m/Y', strtotime($_GET['data_final'])) : '...' ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="?action=export<?= !empty($_GET['data_inicial']) ? '&data_inicial=' . urlencode($_GET['data_inicial']) : '' ?><?= !empty($_GET['data_final']) ? '&data_final=' . urlencode($_GET['data_final']) : '' ?><?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= !empty($_GET['setor']) ? '&setor=' . urlencode($_GET['setor']) : '' ?><?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-file-csv"></i> Exportar CSV
                                    </a>
                                    <span class="text-muted ml-3">
                                        Página <?= $pagination['page'] ?> de <?= $pagination['totalPages'] ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="18%">Nome</th>
                                            <th width="10%">Setor</th>
                                            <th width="10%">Placa/Veículo</th>
                                            <th width="12%">Data/Hora Entrada</th>
                                            <th width="12%">Saída Intermediária</th>
                                            <th width="12%">Retorno</th>
                                            <th width="12%">Hora de Saída</th>
                                            <?php if ($canEditInline || $canDeleteInline): ?>
                                            <th width="14%">Ações</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($profissionais)): ?>
                                            <tr>
                                                <td colspan="<?= ($canEditInline || $canDeleteInline) ? '8' : '7' ?>" class="text-center py-4">
                                                    <i class="fas fa-info-circle text-muted"></i>
                                                    Nenhum registro encontrado para esta data
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($profissionais as $prof): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($prof['nome']) ?></strong>
                                                    <?php if (!empty($prof['saida_final'])): ?>
                                                        <span class="badge bg-secondary ml-2">Saiu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success ml-2">Na empresa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($prof['setor']) ?></td>
                                                <td>
                                                    <?php 
                                                    $placa = strtoupper(trim($prof['placa_veiculo'] ?? ''));
                                                    if (empty($placa) || $placa === 'APE' || $placa === 'A PÉ'): 
                                                    ?>
                                                        <span class="text-muted"><i class="fas fa-walking"></i> A pé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($placa) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= $prof['data_entrada'] ? date('d/m/Y H:i', strtotime($prof['data_entrada'])) : '-' ?>
                                                </td>
                                                <td>
                                                    <?= $prof['saida'] ? date('d/m/Y H:i', strtotime($prof['saida'])) : '-' ?>
                                                </td>
                                                <td>
                                                    <?= $prof['retorno'] ? date('d/m/Y H:i', strtotime($prof['retorno'])) : '-' ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($prof['saida_final'])): ?>
                                                        <span class="text-danger font-weight-bold"><?= date('d/m/Y H:i', strtotime($prof['saida_final'])) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($canEditInline || $canDeleteInline): ?>
                                                <td class="text-center">
                                                    <?php if ($canEditInline): ?>
                                                    <button class="btn btn-sm btn-warning btn-edit-inline" data-id="<?= $prof['id'] ?>" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($canDeleteInline): ?>
                                                    <button class="btn btn-sm btn-danger btn-delete-inline" data-id="<?= $prof['id'] ?>" data-nome="<?= htmlspecialchars($prof['nome']) ?>" title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <nav aria-label="Paginação do relatório">
                                        <ul class="pagination justify-content-center">
                                            <?php 
                                            $currentPage = $pagination['page'];
                                            $totalPages = $pagination['totalPages'];
                                            $queryParams = $_GET;
                                            ?>
                                            
                                            <!-- Primeira página -->
                                            <?php if ($currentPage > 1): ?>
                                                <?php 
                                                $queryParams['page'] = 1;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-double-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Página anterior -->
                                            <?php if ($currentPage > 1): ?>
                                                <?php 
                                                $queryParams['page'] = $currentPage - 1;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Páginas numéricas -->
                                            <?php 
                                            $start = max(1, $currentPage - 2);
                                            $end = min($totalPages, $currentPage + 2);
                                            for ($i = $start; $i <= $end; $i++): 
                                            ?>
                                                <?php 
                                                $queryParams['page'] = $i;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?= $queryString ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <!-- Próxima página -->
                                            <?php if ($currentPage < $totalPages): ?>
                                                <?php 
                                                $queryParams['page'] = $currentPage + 1;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <!-- Última página -->
                                            <?php if ($currentPage < $totalPages): ?>
                                                <?php 
                                                $queryParams['page'] = $totalPages;
                                                $queryString = http_build_query($queryParams);
                                                ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= $queryString ?>">
                                                        <i class="fas fa-angle-double-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <?php endif; ?>
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
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Editar registro inline
        $('.btn-edit-inline').on('click', function() {
            const id = $(this).data('id');
            
            // Por enquanto, redirecionar para a página de edição
            // No futuro, pode abrir um modal inline
            window.location.href = '/reports/profissionais-renner?action=edit&id=' + id;
        });
        
        // Excluir registro inline
        $('.btn-delete-inline').on('click', function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            
            if (!confirm('Tem certeza que deseja excluir o registro de "' + nome + '"?\\nEsta ação não pode ser desfeita.')) {
                return;
            }
            
            // Desabilitar botão durante a requisição
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: '/reports/profissionais-renner?action=delete_inline_ajax',
                method: 'POST',
                data: {
                    id: id,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        // Remover linha da tabela
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Verificar se a tabela ficou vazia
                            if ($('tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                        
                        // Mostrar mensagem de sucesso
                        const alert = $('<div class="alert alert-success alert-dismissible">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            response.message +
                            '</div>');
                        $('.content-fluid .container-fluid').prepend(alert);
                        
                        // Auto-remover alerta após 5 segundos
                        setTimeout(function() {
                            alert.fadeOut(300, function() { $(this).remove(); });
                        }, 5000);
                    } else {
                        alert('Erro ao excluir: ' + response.message);
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    let message = 'Erro ao excluir o registro.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    alert(message);
                    $btn.prop('disabled', false);
                }
            });
        });
    });
    </script>
</body>
</html>