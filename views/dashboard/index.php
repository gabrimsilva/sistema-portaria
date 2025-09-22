<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Controle de Acesso</title>
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
        
        <!-- Main Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <img src="/logo.jpg" alt="Renner Logo" class="brand-image elevation-3" style="width: 33px; height: auto; border-radius: 4px;">
                <span class="brand-text font-weight-light">Controle Acesso</span>
            </a>
            
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/profissionais-renner" class="nav-link">
                                <i class="nav-icon fas fa-user-tie"></i>
                                <p>Profissionais Renner</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/visitantes" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Visitantes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/prestadores-servico" class="nav-link">
                                <i class="nav-icon fas fa-tools"></i>
                                <p>Prestador de Serviços</p>
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
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= $stats['visitors_today'] ?? 0 ?></h3>
                                    <p>Visitantes Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?= $stats['employees_active'] ?? 0 ?></h3>
                                    <p>Funcionários Ativos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?= $stats['entries_today'] ?? 0 ?></h3>
                                    <p>Entradas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?= $stats['exits_today'] ?? 0 ?></h3>
                                    <p>Saídas Hoje</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-sign-out-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botões de Cadastro Rápido -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-plus-circle"></i> Cadastro Rápido
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#modalVisitante">
                                                <i class="fas fa-users"></i> Registrar Visitante
                                            </button>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalProfissional">
                                                <i class="fas fa-user-tie"></i> Registrar Profissional Renner
                                            </button>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalPrestador">
                                                <i class="fas fa-tools"></i> Registrar Prestador de Serviço
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pessoas Atualmente na Empresa -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-building"></i> Pessoas Atualmente na Empresa
                                        <span class="badge badge-info ml-2"><?= count($pessoasNaEmpresa ?? []) ?></span>
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <!-- Filtros Avançados -->
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="filtro-pessoas" placeholder="Buscar por nome, CPF, empresa ou setor...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="limpar-filtros">
                                                        <i class="fas fa-times"></i> Limpar
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted">Digite pelo menos 2 caracteres para filtrar por texto</small>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-control" id="filtro-tipo">
                                                <option value="">🔍 Todos os Tipos</option>
                                                <option value="Visitante">🟢 Visitante</option>
                                                <option value="Profissional Renner">🔵 Profissional Renner</option>
                                                <option value="Prestador">🟡 Prestador de Serviço</option>
                                            </select>
                                            <small class="text-muted">Filtrar por tipo de registro</small>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <?php if (!empty($pessoasNaEmpresa)): ?>
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Tipo</th>
                                                    <th>CPF</th>
                                                    <th>Empresa</th>
                                                    <th>Setor</th>
                                                    <th>Hora de Entrada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pessoasNaEmpresa as $pessoa): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($pessoa['nome']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $badgeClass = '';
                                                        switch ($pessoa['tipo']) {
                                                            case 'Visitante':
                                                                $badgeClass = 'success';
                                                                break;
                                                            case 'Prestador':
                                                                $badgeClass = 'warning';
                                                                break;
                                                            case 'Profissional Renner':
                                                                $badgeClass = 'primary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $badgeClass ?>">
                                                            <?= htmlspecialchars($pessoa['tipo']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= !empty($pessoa['cpf']) ? htmlspecialchars($pessoa['cpf']) : '-' ?></td>
                                                    <td><?= !empty($pessoa['empresa']) ? htmlspecialchars($pessoa['empresa']) : '-' ?></td>
                                                    <td><?= !empty($pessoa['setor']) ? htmlspecialchars($pessoa['setor']) : '-' ?></td>
                                                    <td>
                                                        <?php if ($pessoa['hora_entrada']): ?>
                                                            <i class="fas fa-clock text-muted"></i>
                                                            <?= date('d/m/Y H:i', strtotime($pessoa['hora_entrada'])) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="text-center p-4">
                                            <i class="fas fa-building text-muted" style="font-size: 3rem;"></i>
                                            <p class="text-muted mt-2">Nenhuma pessoa está registrada na empresa no momento.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    
    <!-- Modal para Cadastro de Visitante -->
    <div class="modal fade" id="modalVisitante" tabindex="-1" role="dialog" aria-labelledby="modalVisitanteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="modalVisitanteLabel">
                        <i class="fas fa-users"></i> Cadastrar Visitante
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formVisitante">
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_nome">Nome Completo *</label>
                                    <input type="text" class="form-control" id="visitante_nome" name="nome" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_cpf">CPF</label>
                                    <input type="text" class="form-control" id="visitante_cpf" name="cpf" placeholder="000.000.000-00">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_empresa">Empresa</label>
                                    <input type="text" class="form-control" id="visitante_empresa" name="empresa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_setor">Setor</label>
                                    <input type="text" class="form-control" id="visitante_setor" name="setor">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="visitante_funcionario_responsavel">Funcionário Responsável</label>
                            <input type="text" class="form-control" id="visitante_funcionario_responsavel" name="funcionario_responsavel">
                        </div>
                        <div class="form-group">
                            <label for="visitante_hora_entrada">Hora de Entrada</label>
                            <input type="datetime-local" class="form-control" id="visitante_hora_entrada" name="hora_entrada" required>
                            <small class="form-text text-muted">Hora atual preenchida automaticamente, mas pode ser editada</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnSalvarVisitante">
                        <i class="fas fa-save"></i> Cadastrar Visitante
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para Cadastro de Profissional Renner -->
    <div class="modal fade" id="modalProfissional" tabindex="-1" role="dialog" aria-labelledby="modalProfissionalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="modalProfissionalLabel">
                        <i class="fas fa-user-tie"></i> Cadastrar Profissional Renner
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formProfissional">
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="profissional_nome">Nome Completo *</label>
                                    <input type="text" class="form-control" id="profissional_nome" name="nome" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="profissional_setor">Setor</label>
                                    <input type="text" class="form-control" id="profissional_setor" name="setor">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="profissional_data_entrada">Data/Hora de Entrada</label>
                            <input type="datetime-local" class="form-control" id="profissional_data_entrada" name="data_entrada" required>
                            <small class="form-text text-muted">Hora atual preenchida automaticamente, mas pode ser editada</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarProfissional">
                        <i class="fas fa-save"></i> Cadastrar Profissional
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para Cadastro de Prestador de Serviço -->
    <div class="modal fade" id="modalPrestador" tabindex="-1" role="dialog" aria-labelledby="modalPrestadorLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="modalPrestadorLabel">
                        <i class="fas fa-tools"></i> Cadastrar Prestador de Serviço
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formPrestador">
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prestador_nome">Nome Completo *</label>
                                    <input type="text" class="form-control" id="prestador_nome" name="nome" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prestador_cpf">CPF</label>
                                    <input type="text" class="form-control" id="prestador_cpf" name="cpf" placeholder="000.000.000-00">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prestador_empresa">Empresa</label>
                                    <input type="text" class="form-control" id="prestador_empresa" name="empresa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="prestador_setor">Setor</label>
                                    <input type="text" class="form-control" id="prestador_setor" name="setor">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="prestador_observacao">Observações</label>
                            <textarea class="form-control" id="prestador_observacao" name="observacao" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="prestador_entrada">Data/Hora de Entrada</label>
                            <input type="datetime-local" class="form-control" id="prestador_entrada" name="entrada" required>
                            <small class="form-text text-muted">Hora atual preenchida automaticamente, mas pode ser editada</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" id="btnSalvarPrestador">
                        <i class="fas fa-save"></i> Cadastrar Prestador
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Funções de utilidade
        function showToast(message, type = 'success') {
            const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
            const toastHtml = `
                <div class="toast ${toastClass} text-white" role="alert" aria-live="assertive" aria-atomic="true" data-delay="3000">
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            if (!$('#toast-container').length) {
                $('body').append('<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
            }
            
            const $toast = $(toastHtml);
            $('#toast-container').append($toast);
            $toast.toast('show');
            
            $toast.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
        
        function limparFormulario(formId) {
            $(`#${formId}`)[0].reset();
        }
        
        function adicionarPessoaNaLista(data) {
            // Verifica se há pessoas na lista ou se está vazia
            const tabelaBody = $('.table tbody');
            const emptyMessage = $('.text-center.p-4');
            
            if (emptyMessage.length > 0) {
                // Remove a mensagem de "nenhuma pessoa" e cria a tabela
                emptyMessage.parent().html(`
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>CPF</th>
                                <th>Empresa</th>
                                <th>Setor</th>
                                <th>Hora de Entrada</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                `);
            }
            
            // Define a cor do badge baseado no tipo
            let badgeClass = 'primary';
            if (data.tipo === 'Visitante') badgeClass = 'success';
            else if (data.tipo === 'Prestador') badgeClass = 'warning';
            
            // Formata a data/hora
            const dataFormatada = new Date(data.hora_entrada.replace(' ', 'T')).toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Cria a nova linha
            const novaLinha = `
                <tr>
                    <td><strong>${data.nome}</strong></td>
                    <td><span class="badge badge-${badgeClass}">${data.tipo}</span></td>
                    <td>${data.cpf || '-'}</td>
                    <td>${data.empresa || '-'}</td>
                    <td>${data.setor || '-'}</td>
                    <td><i class="fas fa-clock text-muted"></i> ${dataFormatada}</td>
                </tr>
            `;
            
            // Adiciona a linha no topo da tabela
            $('.table tbody').prepend(novaLinha);
            
            // Atualiza o contador no badge
            const badge = $('.card-title .badge');
            const contadorAtual = parseInt(badge.text()) || 0;
            badge.text(contadorAtual + 1);
        }
        
        // Cadastro de Visitante
        $('#btnSalvarVisitante').click(function() {
            const formData = $('#formVisitante').serialize();
            const nome = $('#visitante_nome').val().trim();
            
            if (!nome) {
                showToast('Nome é obrigatório', 'error');
                return;
            }
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
            
            $.ajax({
                url: '/visitantes?action=save_ajax',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Visitante cadastrado com sucesso!');
                        $('#modalVisitante').modal('hide');
                        limparFormulario('formVisitante');
                        adicionarPessoaNaLista(response.data);
                    } else {
                        showToast(response.message || 'Erro ao cadastrar visitante', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 401) {
                        showToast('Sessão expirada. Você será redirecionado para o login.', 'error');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                    } else {
                        showToast('Erro na comunicação com o servidor', 'error');
                    }
                },
                complete: function() {
                    $('#btnSalvarVisitante').prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Visitante');
                }
            });
        });
        
        // Cadastro de Profissional Renner
        $('#btnSalvarProfissional').click(function() {
            const formData = $('#formProfissional').serialize();
            const nome = $('#profissional_nome').val().trim();
            
            if (!nome) {
                showToast('Nome é obrigatório', 'error');
                return;
            }
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
            
            $.ajax({
                url: '/profissionais-renner?action=save_ajax',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Profissional cadastrado com sucesso!');
                        $('#modalProfissional').modal('hide');
                        limparFormulario('formProfissional');
                        adicionarPessoaNaLista(response.data);
                    } else {
                        showToast(response.message || 'Erro ao cadastrar profissional', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 401) {
                        showToast('Sessão expirada. Você será redirecionado para o login.', 'error');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                    } else {
                        showToast('Erro na comunicação com o servidor', 'error');
                    }
                },
                complete: function() {
                    $('#btnSalvarProfissional').prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Profissional');
                }
            });
        });
        
        // Cadastro de Prestador de Serviço
        $('#btnSalvarPrestador').click(function() {
            const formData = $('#formPrestador').serialize();
            const nome = $('#prestador_nome').val().trim();
            
            if (!nome) {
                showToast('Nome é obrigatório', 'error');
                return;
            }
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
            
            $.ajax({
                url: '/prestadores-servico?action=save_ajax',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Prestador cadastrado com sucesso!');
                        $('#modalPrestador').modal('hide');
                        limparFormulario('formPrestador');
                        adicionarPessoaNaLista(response.data);
                    } else {
                        showToast(response.message || 'Erro ao cadastrar prestador', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 401) {
                        showToast('Sessão expirada. Você será redirecionado para o login.', 'error');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 2000);
                    } else {
                        showToast('Erro na comunicação com o servidor', 'error');
                    }
                },
                complete: function() {
                    $('#btnSalvarPrestador').prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Prestador');
                }
            });
        });
        
        // Função para obter data/hora atual no timezone de São Paulo
        function obterDataHoraAtual() {
            const agora = new Date();
            
            // Usar Intl.DateTimeFormat para obter componentes no timezone de São Paulo
            const formatter = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'America/Sao_Paulo',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
            
            const parts = formatter.formatToParts(agora);
            const date = parts.find(p => p.type === 'year').value + '-' +
                        parts.find(p => p.type === 'month').value + '-' +
                        parts.find(p => p.type === 'day').value;
            const time = parts.find(p => p.type === 'hour').value + ':' +
                        parts.find(p => p.type === 'minute').value;
            
            return `${date}T${time}`;
        }

        // Preencher campos de hora quando modais são abertos
        $('#modalVisitante').on('show.bs.modal', function() {
            $('#visitante_hora_entrada').val(obterDataHoraAtual());
        });

        $('#modalProfissional').on('show.bs.modal', function() {
            $('#profissional_data_entrada').val(obterDataHoraAtual());
        });

        $('#modalPrestador').on('show.bs.modal', function() {
            $('#prestador_entrada').val(obterDataHoraAtual());
        });

        // Limpar formulários ao fechar modals
        $('.modal').on('hidden.bs.modal', function() {
            const modalId = $(this).attr('id');
            if (modalId === 'modalVisitante') {
                limparFormulario('formVisitante');
            } else if (modalId === 'modalProfissional') {
                limparFormulario('formProfissional');
            } else if (modalId === 'modalPrestador') {
                limparFormulario('formPrestador');
            }
        });

        // Função para aplicar filtros combinados
        function aplicarFiltros() {
            const valorTexto = $('#filtro-pessoas').val().toLowerCase();
            const tipoSelecionado = $('#filtro-tipo').val();
            
            $('.table tbody tr').each(function() {
                const linha = $(this);
                const textoLinha = linha.text().toLowerCase();
                const tipoLinha = linha.find('.badge').text().trim();
                
                let mostrarTexto = true;
                let mostrarTipo = true;
                
                // Filtro por texto (se houver)
                if (valorTexto.length >= 2) {
                    mostrarTexto = textoLinha.includes(valorTexto);
                }
                
                // Filtro por tipo (se houver)
                if (tipoSelecionado) {
                    mostrarTipo = tipoLinha === tipoSelecionado;
                }
                
                // Mostrar linha apenas se passar em ambos os filtros
                if (mostrarTexto && mostrarTipo) {
                    linha.show();
                } else {
                    linha.hide();
                }
            });
        }

        // Filtro por texto
        $('#filtro-pessoas').on('keyup', aplicarFiltros);
        
        // Filtro por tipo
        $('#filtro-tipo').on('change', aplicarFiltros);

        // Limpar todos os filtros
        $('#limpar-filtros').on('click', function() {
            $('#filtro-pessoas').val('');
            $('#filtro-tipo').val('');
            $('.table tbody tr').show();
        });
    });
    </script>
</body>
</html>