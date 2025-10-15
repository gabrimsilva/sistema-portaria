<?php
// Verificar se as variáveis estão definidas para evitar erros LSP
$setores = $setores ?? [];
$responsaveis = $responsaveis ?? [];
$pagination = $pagination ?? null;
$prestadores = $prestadores ?? [];

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
    <title>Prestadores de Serviço - Sistema de Controle de Acesso</title>
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
                            <h1 class="m-0">Prestadores de Serviço</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-12">
                                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Relatório de Entradas - Prestadores de Serviço</h3>
                                    <p class="text-muted mt-1">Visualização de registros de entrada por data</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros para Relatório -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <form method="GET" class="row align-items-end" id="report-filters">
                                            <div class="col-md-2">
                                                <label>Data Inicial:</label>
                                                <input type="date" name="data_inicial" class="form-control" value="<?= htmlspecialchars($_GET['data_inicial'] ?? '') ?>" placeholder="Início">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Data Final:</label>
                                                <input type="date" name="data_final" class="form-control" value="<?= htmlspecialchars($_GET['data_final'] ?? '') ?>" placeholder="Fim">
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
                                            <div class="col-md-2">
                                                <label >Status:</label>
                                                <select name="status" class="form-control">
                                                    <option value="">Todos</option>
                                                    <option value="aberto" <?= ($_GET['status'] ?? '') === 'aberto' ? 'selected' : '' ?>>Na empresa</option>
                                                    <option value="finalizado" <?= ($_GET['status'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Saiu</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label >Empresa:</label>
                                                <input type="text" name="empresa" class="form-control" placeholder="Empresa" value="<?= htmlspecialchars($_GET['empresa'] ?? '') ?>">
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
                                        <i class="fas fa-list"></i> Total: <?= $pagination['totalItems'] ?> registros
                                        <?php if (!empty($_GET['data_inicial']) || !empty($_GET['data_final'])): ?>
                                            | <i class="fas fa-calendar"></i> Período: 
                                            <?= !empty($_GET['data_inicial']) ? date('d/m/Y', strtotime($_GET['data_inicial'])) : '...' ?>
                                            até
                                            <?= !empty($_GET['data_final']) ? date('d/m/Y', strtotime($_GET['data_final'])) : '...' ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="?action=export<?= !empty($_GET['data_inicial']) ? '&data_inicial=' . urlencode($_GET['data_inicial']) : '' ?><?= !empty($_GET['data_final']) ? '&data_final=' . urlencode($_GET['data_final']) : '' ?><?= !empty($_GET['setor']) ? '&setor=' . urlencode($_GET['setor']) : '' ?><?= !empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '' ?><?= !empty($_GET['empresa']) ? '&empresa=' . urlencode($_GET['empresa']) : '' ?><?= !empty($_GET['responsavel']) ? '&responsavel=' . urlencode($_GET['responsavel']) : '' ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-file-csv"></i> Exportar CSV
                                    </a>
                                    <span class="text-muted ml-3">
                                        Página <?= $pagination['current'] ?> de <?= $pagination['total'] ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="20%">Nome</th>
                                            <th width="9%">Setor</th>
                                            <th width="9%">Placa/Veículo</th>
                                            <th width="13%">Empresa</th>
                                            <th width="13%">Funcionário Responsável</th>
                                            <th width="9%">CPF</th>
                                            <th width="9%">Data/Hora Entrada</th>
                                            <th width="9%">Hora de Saída</th>
                                            <?php if ($canEditInline || $canDeleteInline): ?>
                                            <th width="9%">Ações</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($prestadores)): ?>
                                            <tr>
                                                <td colspan="<?= ($canEditInline || $canDeleteInline) ? '9' : '8' ?>" class="text-center py-4">
                                                    <i class="fas fa-info-circle text-muted"></i>
                                                    Nenhum registro encontrado para esta data
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($prestadores as $p): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                                    <?php 
                                                    // Determinar status baseado na entrada/saída
                                                    $temSaida = !empty($p['saida']) || !empty($p['saida_final']);
                                                    ?>
                                                    <?php if ($temSaida): ?>
                                                        <span class="badge bg-secondary ml-2">Saiu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success ml-2">Na empresa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($p['setor'] ?? '') ?></td>
                                                <td>
                                                    <?php 
                                                    $placa = strtoupper(trim($p['placa_ou_ape'] ?? ''));
                                                    if ($placa === 'A PÉ' || empty($placa)): 
                                                    ?>
                                                        <span class="text-muted"><i class="fas fa-walking"></i> A pé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($placa) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($p['empresa'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($p['funcionario_responsavel'] ?? '') ?></td>
                                                <td>
                                                    <span class="text-muted"><?= htmlspecialchars($p['cpf']) ?></span>
                                                </td>
                                                <td><?= $p['entrada_at'] ? date('d/m/Y H:i', strtotime($p['entrada_at'])) : '-' ?></td>
                                                <td>
                                                    <?php if (!empty($p['saida'])): ?>
                                                        <span class="text-danger font-weight-bold"><?= date('d/m/Y H:i', strtotime($p['saida'])) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($canEditInline || $canDeleteInline): ?>
                                                <td class="text-center">
                                                    <?php if ($canEditInline): ?>
                                                    <button class="btn btn-sm btn-warning btn-edit-inline" 
                                                            data-id="<?= $p['id'] ?>"
                                                            data-nome="<?= htmlspecialchars($p['nome']) ?>"
                                                            data-cpf="<?= htmlspecialchars($p['cpf']) ?>"
                                                            data-empresa="<?= htmlspecialchars($p['empresa'] ?? '') ?>"
                                                            data-responsavel="<?= htmlspecialchars($p['funcionario_responsavel'] ?? '') ?>"
                                                            data-setor="<?= htmlspecialchars($p['setor'] ?? '') ?>"
                                                            data-placa="<?= htmlspecialchars($p['placa_ou_ape'] ?? '') ?>"
                                                            data-tem-saida="<?= !empty($p['saida']) ? '1' : '0' ?>"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($canDeleteInline): ?>
                                                    <button class="btn btn-sm btn-danger btn-delete-inline" data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome']) ?>" title="Excluir">
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
                            <?php if (isset($pagination) && $pagination['total'] > 1): ?>
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <nav aria-label="Paginação do relatório">
                                        <ul class="pagination justify-content-center">
                                            <?php 
                                            $currentPage = $pagination['current'];
                                            $totalPages = $pagination['total'];
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
                </div>
            </section>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditarPrestador" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Editar Prestador de Serviço
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_prestador_id">
                    
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" class="form-control" id="edit_nome">
                    </div>
                    
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" class="form-control" id="edit_cpf" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Empresa</label>
                        <input type="text" class="form-control" id="edit_empresa">
                    </div>
                    
                    <div class="form-group">
                        <label>Funcionário Responsável</label>
                        <input type="text" class="form-control" id="edit_responsavel">
                    </div>
                    
                    <div class="form-group">
                        <label>Setor</label>
                        <input type="text" class="form-control" id="edit_setor">
                    </div>
                    
                    <div class="form-group">
                        <label>Placa do Veículo / A pé</label>
                        <input type="text" class="form-control" id="edit_placa">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="btnRegistrarSaidaPrestadorModal" style="display:none;">
                        <i class="fas fa-sign-out-alt"></i> Registrar Saída
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="btnSalvarEdicaoPrestador">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <?php
    require_once '../src/services/ErrorHandlerService.php';
    echo ErrorHandlerService::renderComplete();
    ?>
    
    <script>
    $(document).ready(function() {
        // Token CSRF para requisições AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Inicialização de controles para relatórios read-only
        const REPORT_NS = 'prestadores-report';
        
        // Limpeza completa de estado ao navegar para outra seção
        function cleanupReportState() {
            // Cancelar requests AJAX pendentes
            if (window.activeRequests) {
                window.activeRequests.forEach(function(xhr) {
                    if (xhr.readyState !== 4) {
                        xhr.abort();
                    }
                });
                window.activeRequests = [];
            }
            
            // Limpar timers de relatórios
            if (window.reportTimers) {
                window.reportTimers.forEach(clearTimeout);
                window.reportTimers = [];
            }
            
            // Limpar event listeners específicos desta seção
            $(document).off('.' + REPORT_NS);
            
            // Remover estados de loading
            $('.loading').removeClass('loading');
            $('button:disabled').prop('disabled', false);
            
            // Limpar storage temporário de filtros (se houver)
            if (typeof ErrorHandler !== 'undefined' && ErrorHandler.safeSessionStorage) {
                ErrorHandler.safeSessionStorage.removeItem('prestadores-report-filters');
                ErrorHandler.safeSessionStorage.removeItem('prestadores-last-query');
            } else {
                try {
                    sessionStorage.removeItem('prestadores-report-filters');
                    sessionStorage.removeItem('prestadores-last-query');
                } catch(error) {
                    // Silencioso - problemático em modo privado/incógnito
                }
            }
        }
        
        // Limpeza ao desmontar a página
        $(window).on('beforeunload.' + REPORT_NS, cleanupReportState);
        
        // Limpeza ao detectar navegação para outra seção de relatórios
        $(document).on('click.' + REPORT_NS, 'a[href*="/reports/"]', function(e) {
            if (!$(this).attr('href').includes('/prestadores-servico')) {
                cleanupReportState();
            }
        });
        
        // Gestão de formulário de filtros
        $('#report-filters').on('submit.' + REPORT_NS, function(e) {
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            
            // Evitar submissão dupla
            if ($btn.hasClass('loading')) {
                e.preventDefault();
                return false;
            }
            
            $btn.addClass('loading').prop('disabled', true);
            
            // Auto-restaurar após timeout caso haja problema
            setTimeout(function() {
                $btn.removeClass('loading').prop('disabled', false);
            }, 10000);
        });
        
        // Controle de paginação
        $('.pagination a').on('click.' + REPORT_NS, function(e) {
            const $link = $(this);
            if ($link.hasClass('loading')) {
                e.preventDefault();
                return false;
            }
            $link.addClass('loading');
        });
        
        // Preservar filtros na URL limpa (sem parâmetros vazios)
        if (window.location.search) {
            const url = new URL(window.location);
            let hasChanges = false;
            
            // Remover parâmetros vazios da URL
            for (const [key, value] of url.searchParams.entries()) {
                if (!value || value === 'todos' || value === '') {
                    url.searchParams.delete(key);
                    hasChanges = true;
                }
            }
            
            if (hasChanges) {
                history.replaceState({}, '', url.toString());
            }
        }
        
        // Botão Editar - Abre modal
        $('.btn-edit-inline').on('click', function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            const cpf = $(this).data('cpf');
            const empresa = $(this).data('empresa');
            const responsavel = $(this).data('responsavel');
            const setor = $(this).data('setor');
            const placa = $(this).data('placa');
            const temSaida = $(this).data('tem-saida');
            
            // Preencher campos do modal
            $('#edit_prestador_id').val(id);
            $('#edit_nome').val(nome);
            $('#edit_cpf').val(cpf);
            $('#edit_empresa').val(empresa);
            $('#edit_responsavel').val(responsavel);
            $('#edit_setor').val(setor);
            $('#edit_placa').val(placa);
            
            // Mostrar/esconder botão de saída
            if (temSaida === '0' || temSaida === 0) {
                $('#btnRegistrarSaidaPrestadorModal').show();
            } else {
                $('#btnRegistrarSaidaPrestadorModal').hide();
            }
            
            // Abrir modal
            $('#modalEditarPrestador').modal('show');
        });
        
        // Salvar edição
        $('#btnSalvarEdicaoPrestador').on('click', function() {
            const id = $('#edit_prestador_id').val();
            const $btn = $(this);
            
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
            
            $.ajax({
                url: '/prestadores-servico?action=update_ajax',
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                data: {
                    id: id,
                    nome: $('#edit_nome').val(),
                    empresa: $('#edit_empresa').val(),
                    funcionario_responsavel: $('#edit_responsavel').val(),
                    setor: $('#edit_setor').val(),
                    placa_ou_ape: $('#edit_placa').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro ao salvar: ' + response.message);
                        $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                    }
                },
                error: function(xhr) {
                    alert('Erro ao salvar alterações.');
                    $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                }
            });
        });
        
        // Registrar saída pelo modal
        $('#btnRegistrarSaidaPrestadorModal').on('click', function() {
            const id = $('#edit_prestador_id').val();
            const nome = $('#edit_nome').val();
            
            if (!confirm('Confirmar saída de "' + nome + '"?')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registrando...');
            
            $.ajax({
                url: '/prestadores-servico?action=saida',
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                data: { id: id },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Erro ao registrar saída.');
                    $btn.prop('disabled', false).html('<i class="fas fa-sign-out-alt"></i> Registrar Saída');
                }
            });
        });
        
        // Botões inline - Excluir
        $('.btn-delete-inline').on('click', function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            
            if (!confirm('Tem certeza que deseja excluir o registro de "' + nome + '"?\\nEsta ação não pode ser desfeita.')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true);
            
            $.ajax({
                url: '/reports/prestadores-servico?action=delete_inline_ajax',
                method: 'POST',
                data: {
                    id: id,
                    csrf_token: csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            if ($('tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                        
                        const alert = $('<div class="alert alert-success alert-dismissible">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            response.message +
                            '</div>');
                        $('.content-fluid .container-fluid').prepend(alert);
                        
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