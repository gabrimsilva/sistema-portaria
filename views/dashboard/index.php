<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Dashboard - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
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
        
        <!-- 🎯 NavigationService: Navegação Unificada -->
        <?= NavigationService::renderSidebar() ?>
        
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
                                    <h3><?= isset($stats['visitors_today']) ? $stats['visitors_today'] : 0 ?></h3>
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
                                    <h3><?= isset($stats['employees_active']) ? $stats['employees_active'] : 0 ?></h3>
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
                                    <h3><?= isset($stats['entries_today']) ? $stats['entries_today'] : 0 ?></h3>
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
                                    <h3><?= isset($stats['exits_today']) ? $stats['exits_today'] : 0 ?></h3>
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
                                        <span class="badge badge-info ml-2"><?= isset($pessoasNaEmpresa) ? count($pessoasNaEmpresa) : 0 ?></span>
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
                                        <?php if (isset($pessoasNaEmpresa) && !empty($pessoasNaEmpresa)): ?>
                                        <table class="table table-hover text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Nome</th>
                                                    <th>Tipo</th>
                                                    <th>CPF</th>
                                                    <th>Empresa</th>
                                                    <th>Setor</th>
                                                    <th>Responsável</th>
                                                    <th>Placa</th>
                                                    <th>Hora de Entrada</th>
                                                    <th width="100">Ações</th>
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
                                                        <?php if (($pessoa['tipo'] === 'Visitante' || $pessoa['tipo'] === 'Prestador') && !empty($pessoa['funcionario_responsavel'])): ?>
                                                            <i class="fas fa-user-tie text-primary mr-1"></i>
                                                            <?= htmlspecialchars($pessoa['funcionario_responsavel']) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($pessoa['placa_veiculo'])): ?>
                                                            <i class="fas fa-car text-muted mr-1"></i>
                                                            <?= htmlspecialchars($pessoa['placa_veiculo']) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($pessoa['hora_entrada']): ?>
                                                            <i class="fas fa-clock text-muted"></i>
                                                            <?= date('d/m/Y H:i', strtotime($pessoa['hora_entrada'])) ?>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info btn-editar" 
                                                                data-id="<?= $pessoa['id'] ?>" 
                                                                data-tipo="<?= htmlspecialchars($pessoa['tipo']) ?>"
                                                                data-nome="<?= htmlspecialchars($pessoa['nome']) ?>"
                                                                data-cpf="<?= htmlspecialchars($pessoa['cpf'] ?? '') ?>"
                                                                data-empresa="<?= htmlspecialchars($pessoa['empresa'] ?? '') ?>"
                                                                data-setor="<?= htmlspecialchars($pessoa['setor'] ?? '') ?>"
                                                                data-placa_veiculo="<?= htmlspecialchars($pessoa['placa_veiculo'] ?? '') ?>"
                                                data-funcionario_responsavel="<?= htmlspecialchars($pessoa['funcionario_responsavel'] ?? '') ?>"
                                                                title="Editar registro">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
                                    <label for="visitante_cpf">CPF *</label>
                                    <input type="text" class="form-control" id="visitante_cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
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
                                    <label for="visitante_setor">Setor *</label>
                                    <input type="text" class="form-control" id="visitante_setor" name="setor" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_funcionario_responsavel">Funcionário Responsável</label>
                                    <input type="text" class="form-control" id="visitante_funcionario_responsavel" name="funcionario_responsavel">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visitante_placa_veiculo">Placa de Veículo *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="visitante_placa_veiculo" name="placa_veiculo" placeholder="ABC-1234" style="text-transform: uppercase;" maxlength="8" required>
                                        <span class="input-group-text">
                                            <input type="checkbox" id="visitante_ape_checkbox">
                                            <label for="visitante_ape_checkbox" class="ml-1 mb-0">A pé</label>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                                </div>
                            </div>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formProfissional">
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profissional_nome">Nome Completo *</label>
                                    <input type="text" class="form-control" id="profissional_nome" name="nome" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="profissional_setor">Setor *</label>
                                    <input type="text" class="form-control" id="profissional_setor" name="setor" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="profissional_placa_veiculo">Placa de Veículo *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="profissional_placa_veiculo" name="placa_veiculo" placeholder="ABC-1234" style="text-transform: uppercase;" maxlength="8" required>
                                <span class="input-group-text">
                                    <input type="checkbox" id="profissional_ape_checkbox">
                                    <label for="profissional_ape_checkbox" class="ml-1 mb-0">A pé</label>
                                </span>
                            </div>
                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
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
                                    <label for="prestador_cpf">CPF *</label>
                                    <input type="text" class="form-control" id="prestador_cpf" name="cpf" placeholder="000.000.000-00" maxlength="14" required>
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
                                    <label for="prestador_setor">Setor *</label>
                                    <input type="text" class="form-control" id="prestador_setor" name="setor" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="prestador_placa_veiculo">Placa de Veículo *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="prestador_placa_veiculo" name="placa_veiculo" placeholder="ABC-1234" style="text-transform: uppercase;" maxlength="8" required>
                                <span class="input-group-text">
                                    <input type="checkbox" id="prestador_ape_checkbox">
                                    <label for="prestador_ape_checkbox" class="ml-1 mb-0">A pé</label>
                                </span>
                            </div>
                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                        </div>
                        <div class="form-group">
                            <label for="prestador_funcionario_responsavel">Funcionário Responsável *</label>
                            <input type="text" class="form-control" id="prestador_funcionario_responsavel" name="funcionario_responsavel" required>
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
    
    <!-- Modal para Edição de Registros -->
    <div class="modal fade" id="modalEditar" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="modalEditarLabel">
                        <i class="fas fa-edit"></i> Editar Registro
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditar">
                        <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" id="edit_tipo_original" name="tipo_original">
                        
                        <div class="form-group">
                            <label>Tipo de Registro</label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <span id="edit_tipo_display"></span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="edit_nome">Nome Completo *</label>
                                    <input type="text" class="form-control" id="edit_nome" name="nome" required>
                                </div>
                            </div>
                            <div id="campo_cpf" class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_cpf">CPF</label>
                                    <input type="text" class="form-control" id="edit_cpf" name="cpf" placeholder="000.000.000-00" maxlength="14">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div id="campo_empresa" class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_empresa">Empresa</label>
                                    <input type="text" class="form-control" id="edit_empresa" name="empresa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_setor">Setor</label>
                                    <input type="text" class="form-control" id="edit_setor" name="setor">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campo Placa de Veículo - visível para Visitantes, Prestadores e Profissionais Renner -->
                        <div id="campo_placa_veiculo" class="form-group" style="display: none;">
                            <label for="edit_placa_veiculo">Placa de Veículo</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="edit_placa_veiculo" name="placa_veiculo" placeholder="ABC-1234" style="text-transform: uppercase;" maxlength="8">
                                <span class="input-group-text">
                                    <input type="checkbox" id="edit_ape_checkbox">
                                    <label for="edit_ape_checkbox" class="ml-1 mb-0">A pé</label>
                                </span>
                            </div>
                            <small class="form-text text-muted">Marque "A pé" se não houver veículo</small>
                        </div>
                        
                        <!-- Campos específicos para visitantes -->
                        <div id="campo_funcionario_responsavel" class="form-group" style="display: none;">
                            <label for="edit_funcionario_responsavel">Funcionário Responsável</label>
                            <input type="text" class="form-control" id="edit_funcionario_responsavel" name="funcionario_responsavel">
                        </div>
                        
                        <!-- Campos específicos para Profissional Renner -->
                        <div id="campos_profissional_renner" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_saida">Saída Intermediária</label>
                                        <input type="datetime-local" class="form-control" id="edit_saida" name="saida">
                                        <small class="form-text text-muted">Primeira saída durante o expediente</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="edit_retorno">Retorno</label>
                                        <input type="datetime-local" class="form-control" id="edit_retorno" name="retorno">
                                        <small class="form-text text-muted">Retorno após saída intermediária</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="campo_hora_saida" class="form-group" style="display: none;">
                            <label for="edit_hora_saida">Hora de Saída</label>
                            <input type="datetime-local" class="form-control" id="edit_hora_saida" name="hora_saida">
                            <small class="form-text text-muted">Deixe em branco se a pessoa ainda não saiu da empresa</small>
                        </div>
                        
                        <!-- Campo específico para prestadores -->
                        <div id="campo_observacao" class="form-group" style="display: none;">
                            <label for="edit_observacao">Observações</label>
                            <textarea class="form-control" id="edit_observacao" name="observacao" rows="3"></textarea>
                        </div>
                        

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" id="btnSalvarEdicao">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <?php
    require_once '../src/services/ErrorHandlerService.php';
    echo ErrorHandlerService::renderComplete();
    ?>
    
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
        
        function atualizarLinhaNaTabela(data) {
            // Função para atualizar registro existente SEM afetar contadores
            const linha = $(`.btn-editar[data-id="${data.id}"]`).closest('tr');
            if (linha.length > 0) {
                // Verificar se a pessoa registrou saída (deve ser removida da lista)
                let temSaida = false;
                
                // Verificar cada tipo de saída possível nos dados retornados pelo controller
                if (data.hora_saida && data.hora_saida !== null && data.hora_saida !== '') {
                    temSaida = true; // Visitante com saída
                } else if (data.saida_final && data.saida_final !== null && data.saida_final !== '') {
                    temSaida = true; // Profissional Renner com saída final
                } else if (data.saida && data.saida !== null && data.saida !== '' && !data.hasOwnProperty('setor')) {
                    // Prestador com saída (só remove se NÃO for Profissional Renner)
                    // Profissionais Renner têm campo 'setor', Prestadores não têm
                    temSaida = true; 
                }
                
                // Se a pessoa saiu, remover da tabela e atualizar contadores
                if (temSaida) {
                    showToast(`${data.nome} saiu da empresa`, 'success');
                    
                    linha.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Atualizar contador de pessoas na empresa
                        const badge = $('.card-title .badge');
                        const contadorAtual = parseInt(badge.text()) || 0;
                        if (contadorAtual > 0) {
                            badge.text(contadorAtual - 1);
                        }
                        
                        // Atualizar contador de saídas hoje
                        const saidasCard = $('.small-box').eq(3).find('.inner h3');
                        const saidasAtual = parseInt(saidasCard.text()) || 0;
                        saidasCard.text(saidasAtual + 1);
                    });
                    
                    return; // Não continuar com a atualização da linha
                }
                
                // Se não saiu, atualizar linha normalmente
                // Define a cor do badge baseado no tipo
                let badgeClass = 'primary';
                if (data.tipo === 'Visitante') badgeClass = 'success';
                else if (data.tipo === 'Prestador') badgeClass = 'warning';
                
                // Formata a data/hora se disponível
                let dataFormatada = '-';
                if (data.hora_entrada) {
                    dataFormatada = new Date(data.hora_entrada.replace(' ', 'T')).toLocaleString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
                // Atualiza o conteúdo da linha existente
                linha.html(`
                    <td><strong>${data.nome}</strong></td>
                    <td><span class="badge badge-${badgeClass}">${data.tipo}</span></td>
                    <td>${data.cpf || '-'}</td>
                    <td>${data.empresa || '-'}</td>
                    <td>${data.setor || '-'}</td>
                    <td>${(data.tipo === 'Visitante' || data.tipo === 'Prestador') && data.funcionario_responsavel ? '<i class="fas fa-user-tie text-primary mr-1"></i>' + data.funcionario_responsavel : '-'}</td>
                    <td>${data.placa_veiculo || '-'}</td>
                    <td><i class="fas fa-clock text-muted"></i> ${dataFormatada}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-editar" 
                                data-id="${data.id}" 
                                data-tipo="${data.tipo}"
                                data-nome="${data.nome}"
                                data-cpf="${data.cpf || ''}"
                                data-empresa="${data.empresa || ''}"
                                data-setor="${data.setor || ''}"
                                data-funcionario_responsavel="${data.funcionario_responsavel || ''}"
                                data-placa_veiculo="${data.placa_veiculo || ''}"
                                title="Editar registro">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                `);
            }
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
                                <th>Responsável</th>
                                <th>Placa</th>
                                <th>Hora de Entrada</th>
                                <th width="100">Ações</th>
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
                    <td>${(data.tipo === 'Visitante' || data.tipo === 'Prestador') && data.funcionario_responsavel ? '<i class="fas fa-user-tie text-primary mr-1"></i>' + data.funcionario_responsavel : '-'}</td>
                    <td>${data.placa_veiculo || '-'}</td>
                    <td><i class="fas fa-clock text-muted"></i> ${dataFormatada}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-editar" 
                                data-id="${data.id}" 
                                data-tipo="${data.tipo}"
                                data-nome="${data.nome}"
                                data-cpf="${data.cpf || ''}"
                                data-empresa="${data.empresa || ''}"
                                data-setor="${data.setor || ''}"
                                data-funcionario_responsavel="${data.funcionario_responsavel || ''}"
                                data-placa_veiculo="${data.placa_veiculo || ''}"
                                title="Editar registro">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            // Adiciona a linha no topo da tabela
            $('.table tbody').prepend(novaLinha);
            
            // Atualiza o contador no badge "Pessoas Atualmente na Empresa"
            const badge = $('.card-title .badge');
            const contadorAtual = parseInt(badge.text()) || 0;
            badge.text(contadorAtual + 1);
            
            // Atualiza os contadores específicos do dashboard baseado no tipo
            if (data.tipo === 'Visitante') {
                // Primeiro card: Visitantes Hoje
                const visitantesCard = $('.small-box').eq(0).find('.inner h3');
                const visitantesAtual = parseInt(visitantesCard.text()) || 0;
                visitantesCard.text(visitantesAtual + 1);
            } else if (data.tipo === 'Profissional Renner') {
                // Segundo card: Funcionários Ativos
                const funcionariosCard = $('.small-box').eq(1).find('.inner h3');
                const funcionariosAtual = parseInt(funcionariosCard.text()) || 0;
                funcionariosCard.text(funcionariosAtual + 1);
            }
            // Nota: Prestadores de Serviço contam apenas nas "Entradas Hoje", não têm contador específico
            
            // Sempre atualiza o contador de "Entradas Hoje" (terceiro card)
            const entradasCard = $('.small-box').eq(2).find('.inner h3');
            const entradasAtual = parseInt(entradasCard.text()) || 0;
            entradasCard.text(entradasAtual + 1);
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
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        // Tratamento melhorado para erros de duplicidade
                        if (response.errors && Array.isArray(response.errors)) {
                            const mensagens = response.errors.join('<br>');
                            showToast(`⚠️ Problema de duplicidade:<br><small>${mensagens}</small>`, 'error');
                        } else {
                            showToast(response.message || 'Erro ao cadastrar visitante', 'error');
                        }
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
                        setTimeout(() => window.location.reload(), 500);
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
            const funcionario_responsavel = $('#prestador_funcionario_responsavel').val().trim();
            
            if (!nome) {
                showToast('Nome é obrigatório', 'error');
                return;
            }
            
            if (!funcionario_responsavel) {
                showToast('Funcionário Responsável é obrigatório', 'error');
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
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        // Tratamento melhorado para erros de duplicidade
                        if (response.errors && Array.isArray(response.errors)) {
                            const mensagens = response.errors.join('<br>');
                            showToast(`⚠️ Problema de duplicidade:<br><small>${mensagens}</small>`, 'error');
                        } else {
                            showToast(response.message || 'Erro ao cadastrar prestador', 'error');
                        }
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

        // Edição inline de registros
        $(document).on('click', '.btn-editar', function() {
            const btn = $(this);
            const id = btn.data('id');
            const tipo = btn.data('tipo');
            const nome = btn.data('nome');
            const cpf = btn.data('cpf');
            const empresa = btn.data('empresa');
            const setor = btn.data('setor');
            const funcionario = btn.data('funcionario');
            const placa_veiculo = btn.data('placa_veiculo');
            
            // Preencher campos básicos
            $('#edit_id').val(id);
            $('#edit_tipo_original').val(tipo);
            $('#edit_tipo_display').text(tipo);
            $('#edit_nome').val(nome);
            $('#edit_cpf').val(cpf);
            $('#edit_empresa').val(empresa);
            $('#edit_setor').val(setor);
            $('#edit_placa_veiculo').val(placa_veiculo);
            
            // Verificar se é APE e marcar checkbox imediatamente
            if (placa_veiculo === 'APE') {
                $('#edit_ape_checkbox').prop('checked', true);
                $('#edit_placa_veiculo').prop('readonly', true);
            } else {
                $('#edit_ape_checkbox').prop('checked', false);
                $('#edit_placa_veiculo').prop('readonly', false);
            }
            
            // Mostrar/ocultar campos específicos baseado no tipo
            $('#campo_funcionario_responsavel, #campo_hora_saida, #campo_observacao, #campos_profissional_renner').hide();
            
            // Mostrar campo de hora de saída para todos os tipos
            $('#campo_hora_saida').show();
            
            // Mostrar/ocultar campos específicos para Profissional Renner
            if (tipo === 'Profissional Renner') {
                $('#campo_empresa').hide();
                $('#campo_cpf').hide();
                // Manter placa visível para Profissionais Renner
                $('#campo_placa_veiculo').show();
                // Mostrar campos específicos M2: saida e retorno
                $('#campos_profissional_renner').show();
                // Alterar cor do header do modal para Profissional Renner
                $('.modal-header').removeClass('bg-info').addClass('bg-primary');
            } else {
                $('#campo_empresa').show();
                $('#campo_cpf').show();
                $('#campo_placa_veiculo').show();
                // Manter cor padrão do header para outros tipos
                $('.modal-header').removeClass('bg-primary').addClass('bg-info');
            }
            
            if (tipo === 'Visitante') {
                $('#campo_funcionario_responsavel').show();
                // Preencher campos específicos do visitante
                $('#edit_funcionario_responsavel').val(btn.data('funcionario_responsavel') || '');
                
                // Buscar dados completos do visitante para preencher hora de saída
                $.ajax({
                    url: `/visitantes?action=get_data&id=${id}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.hora_saida) {
                            // Formatar data para datetime-local sem conversão de timezone
                            const horaSaidaFormatada = response.data.hora_saida.replace(' ', 'T').slice(0, 16);
                            $('#edit_hora_saida').val(horaSaidaFormatada);
                        }
                    },
                    error: function() {
                        // Em caso de erro, deixa o campo vazio
                        $('#edit_hora_saida').val('');
                    }
                });
            } else if (tipo === 'Prestador') {
                $('#campo_funcionario_responsavel').show();
                $('#campo_observacao').show();
                // Preencher campos específicos do prestador
                $('#edit_funcionario_responsavel').val(btn.data('funcionario_responsavel') || '');
                $('#edit_observacao').val('');
                
                $.ajax({
                    url: `/prestadores-servico?action=get_data&id=${id}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.saida) {
                            // Formatar data para datetime-local sem conversão de timezone
                            const saidaFormatada = response.data.saida.replace(' ', 'T').slice(0, 16);
                            $('#edit_hora_saida').val(saidaFormatada);
                        }
                        if (response.data.observacao) {
                            $('#edit_observacao').val(response.data.observacao);
                        }
                    },
                    error: function() {
                        // Em caso de erro, deixa o campo vazio
                        $('#edit_hora_saida').val('');
                    }
                });
            } else if (tipo === 'Profissional Renner') {
                // Buscar dados específicos do profissional para preencher todos os campos M2
                $.ajax({
                    url: `/profissionais-renner?action=get_data&id=${id}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            // Preencher saída final (campo hora_saida)
                            if (response.data.saida_final) {
                                const saidaFinalFormatada = response.data.saida_final.replace(' ', 'T').slice(0, 16);
                                $('#edit_hora_saida').val(saidaFinalFormatada);
                            }
                            // Preencher saída intermediária (campo M2)
                            if (response.data.saida) {
                                const saidaFormatada = response.data.saida.replace(' ', 'T').slice(0, 16);
                                $('#edit_saida').val(saidaFormatada);
                            }
                            // Preencher retorno (campo M2)
                            if (response.data.retorno) {
                                const retornoFormatado = response.data.retorno.replace(' ', 'T').slice(0, 16);
                                $('#edit_retorno').val(retornoFormatado);
                            }
                        }
                    },
                    error: function() {
                        // Em caso de erro, deixa os campos vazios
                        $('#edit_hora_saida').val('');
                        $('#edit_saida').val('');
                        $('#edit_retorno').val('');
                    }
                });
            }
            
            // Abrir modal
            const modalElement = document.getElementById('modalEditar');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        });

        // Salvar edição (apenas editar dados, sem saída)
        $('#btnSalvarEdicao').on('click', function() {
            const nome = $('#edit_nome').val().trim();
            const tipoOriginal = $('#edit_tipo_original').val();
            
            if (!nome) {
                showToast('Nome é obrigatório', 'error');
                return;
            }
            
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
            
            // Coletar dados comuns do formulário
            const dadosComuns = {
                id: $('#edit_id').val(),
                nome: $('#edit_nome').val(),
                cpf: $('#edit_cpf').val(),
                setor: $('#edit_setor').val(),
                csrf_token: $('meta[name="csrf-token"]').attr('content') || '<?= CSRFProtection::generateToken() ?>'
            };
            
            // Adicionar empresa apenas se não for Profissional Renner
            if (tipoOriginal !== 'Profissional Renner') {
                dadosComuns.empresa = $('#edit_empresa').val();
            }
            
            // Placa de veículo é obrigatória para todos os tipos
            dadosComuns.placa_veiculo = $('#edit_placa_veiculo').val();
            
            let formData = new FormData();
            let endpoint = '';
            
            // Adicionar dados comuns (incluindo campos vazios)
            Object.keys(dadosComuns).forEach(key => {
                formData.append(key, dadosComuns[key] || '');
            });
            
            // Determinar endpoint e campos específicos baseado no tipo
            const horaSaida = $('#edit_hora_saida').val();
            
            switch (tipoOriginal) {
                case 'Visitante':
                    endpoint = '/visitantes?action=update_ajax';
                    formData.append('funcionario_responsavel', $('#edit_funcionario_responsavel').val());
                    if (horaSaida) {
                        formData.append('hora_saida', horaSaida);
                    }
                    break;
                case 'Profissional Renner':
                    endpoint = '/profissionais-renner?action=update_ajax';
                    if (horaSaida) {
                        formData.append('saida_final', horaSaida); // Para Profissional Renner, hora de saída = saída final
                    }
                    // Adicionar campos M2: saída intermediária e retorno
                    const saidaIntermediaria = $('#edit_saida').val();
                    const retorno = $('#edit_retorno').val();
                    if (saidaIntermediaria) {
                        formData.append('saida', saidaIntermediaria);
                    }
                    if (retorno) {
                        formData.append('retorno', retorno);
                    }
                    break;
                case 'Prestador':
                    endpoint = '/prestadores-servico?action=update_ajax';
                    formData.append('funcionario_responsavel', $('#edit_funcionario_responsavel').val());
                    formData.append('observacao', $('#edit_observacao').val());
                    if (horaSaida) {
                        formData.append('saida', horaSaida);
                    }
                    break;
                default:
                    showToast('Tipo de registro inválido', 'error');
                    $(this).prop('disabled', false).html('<i class="fas fa-save"></i> Salvar Alterações');
                    return;
            }
            
            $.ajax({
                url: endpoint,
                method: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,  // Não processar os dados automáticamente
                contentType: false,  // Deixar o browser definir o content-type
                success: function(response) {
                    if (response.success) {
                        showToast('Registro atualizado com sucesso!');
                        $('#modalEditar').modal('hide');
                        // Recarregar página para atualizar o dashboard
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        // Tratamento melhorado para erros de duplicidade
                        if (response.errors && Array.isArray(response.errors)) {
                            const mensagens = response.errors.join('<br>');
                            showToast(`⚠️ Problema de duplicidade:<br><small>${mensagens}</small>`, 'error');
                        } else {
                            showToast(response.message || 'Erro ao atualizar registro', 'error');
                        }
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
                    $('#btnSalvarEdicao').prop('disabled', false).html('<i class="fas fa-save"></i> Salvar Alterações');
                }
            });
        });



        // Limpar formulário de edição ao fechar modal
        $('#modalEditar').on('hidden.bs.modal', function() {
            $('#formEditar')[0].reset();
            $('#campo_funcionario_responsavel, #campo_hora_saida, #campo_observacao, #campos_profissional_renner').hide();
        });

        // ==================== MÁSCARAS DE ENTRADA - BOOTSTRAP 4.6.2 COMPATÍVEL ====================
        
        console.log('✅ Sistema de máscaras carregado (Bootstrap 4.6.2)');
        
        // Máscara para CPF - Formato: 000.000.000-00
        function aplicarMascaraCPF(selector) {
            $(selector).on('input paste keyup', function() {
                let cpf = this.value.replace(/\D/g, ''); // Remove não-dígitos
                cpf = cpf.substring(0, 11); // Limita a 11 dígitos
                
                // Aplica formatação progressiva
                cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
                cpf = cpf.replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                cpf = cpf.replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
                
                this.value = cpf;
            });
        }
        
        // Máscara para Placa - Formatos: ABC-1234 (antigo) e ABC1D23 (Mercosul)
        function aplicarMascaraPlaca(selector) {
            $(selector).on('input paste keyup', function() {
                let placa = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                placa = placa.substring(0, 7); // Máximo 7 caracteres
                
                // Detecta formato e aplica formatação
                if (placa.length >= 4) {
                    const letras = placa.substring(0, 3);
                    const resto = placa.substring(3);
                    
                    // Se primeiros 3 são letras
                    if (/^[A-Z]{3}$/.test(letras)) {
                        // Se resto são só números = formato antigo ABC-1234
                        if (/^[0-9]+$/.test(resto)) {
                            placa = letras + '-' + resto;
                        }
                        // Senão = formato Mercosul ABC1D23 (sem formatação extra)
                    }
                }
                
                this.value = placa;
            });
        }
        
        // Aplicar máscaras quando os modais abrem
        $('#modalVisitante').on('shown.bs.modal', function() {
            aplicarMascaraCPF('#visitante_cpf');
            aplicarMascaraPlaca('#visitante_placa_veiculo');
            console.log('✅ Máscaras aplicadas ao Modal Visitante');
            
            // Controle do checkbox "A pé" para visitante
            let previousValueVisitante = '';
            
            $('#visitante_ape_checkbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    previousValueVisitante = $('#visitante_placa_veiculo').val() !== 'APE' ? $('#visitante_placa_veiculo').val() : '';
                    $('#visitante_placa_veiculo').val('APE').prop('readonly', true).removeAttr('required');
                } else {
                    $('#visitante_placa_veiculo').val(previousValueVisitante).prop('readonly', false).attr('required', 'required');
                }
            });
            
            // Verificar estado inicial
            if ($('#visitante_placa_veiculo').val() === 'APE') {
                $('#visitante_ape_checkbox').prop('checked', true);
                $('#visitante_placa_veiculo').prop('readonly', true);
            }
        });
        
        $('#modalPrestador').on('shown.bs.modal', function() {
            aplicarMascaraCPF('#prestador_cpf');
            aplicarMascaraPlaca('#prestador_placa_veiculo');
            console.log('✅ Máscaras aplicadas ao Modal Prestador');
            
            // Controle do checkbox "A pé" para prestador
            let previousValuePrestador = '';
            
            $('#prestador_ape_checkbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    previousValuePrestador = $('#prestador_placa_veiculo').val() !== 'APE' ? $('#prestador_placa_veiculo').val() : '';
                    $('#prestador_placa_veiculo').val('APE').prop('readonly', true).removeAttr('required');
                } else {
                    $('#prestador_placa_veiculo').val(previousValuePrestador).prop('readonly', false).attr('required', 'required');
                }
            });
            
            // Verificar estado inicial
            if ($('#prestador_placa_veiculo').val() === 'APE') {
                $('#prestador_ape_checkbox').prop('checked', true);
                $('#prestador_placa_veiculo').prop('readonly', true);
            }
        });
        
        $('#modalProfissional').on('shown.bs.modal', function() {
            aplicarMascaraPlaca('#profissional_placa_veiculo');
            console.log('✅ Máscaras aplicadas ao Modal Profissional');
            
            // Garantir que o campo de placa esteja habilitado inicialmente
            $('#profissional_placa_veiculo').prop('readonly', false).prop('disabled', false);
            $('#profissional_ape_checkbox').prop('checked', false);
            
            // Autocomplete para o campo Nome
            if ($('#profissional_nome').autocomplete('instance')) {
                $('#profissional_nome').autocomplete('destroy');
            }
            
            $('#profissional_nome').autocomplete({
                source: function(request, response) {
                    console.log('🔍 Buscando profissionais para:', request.term);
                    $.ajax({
                        url: '/profissionais-renner?action=search',
                        dataType: 'json',
                        data: {
                            q: request.term
                        },
                        success: function(data) {
                            console.log('✅ Resposta recebida:', data);
                            if (data.success && data.data) {
                                const items = $.map(data.data, function(item) {
                                    return {
                                        label: item.nome + ' - ' + item.setor,
                                        value: item.nome,
                                        setor: item.setor,
                                        fre: item.fre
                                    };
                                });
                                console.log('📋 Items mapeados:', items);
                                response(items);
                            } else {
                                console.log('⚠️ Nenhum resultado encontrado');
                                response([]);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ Erro na requisição:', error, xhr.responseText);
                            response([]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    console.log('✨ Item selecionado:', ui.item);
                    $('#profissional_nome').val(ui.item.value);
                    $('#profissional_setor').val(ui.item.setor);
                    return false;
                }
            });
            
            console.log('✅ Autocomplete inicializado para Profissionais');
            
            // Controle do checkbox "A pé" para Profissional Renner
            let previousValueProfissional = '';
            
            $('#profissional_ape_checkbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    previousValueProfissional = $('#profissional_placa_veiculo').val() !== 'APE' ? $('#profissional_placa_veiculo').val() : '';
                    $('#profissional_placa_veiculo').val('APE').prop('readonly', true).removeAttr('required');
                } else {
                    $('#profissional_placa_veiculo').val(previousValueProfissional).prop('readonly', false).attr('required', 'required');
                }
            });
            
            // Verificar estado inicial
            if ($('#profissional_placa_veiculo').val() === 'APE') {
                $('#profissional_ape_checkbox').prop('checked', true);
                $('#profissional_placa_veiculo').prop('readonly', true);
            }
        });
        
        $('#modalEditar').on('shown.bs.modal', function() {
            aplicarMascaraCPF('#edit_cpf');
            aplicarMascaraPlaca('#edit_placa_veiculo');
            console.log('✅ Máscaras aplicadas ao Modal Editar');
            
            // Controle do checkbox "A pé" para modal de edição
            let previousValueEdit = '';
            
            $('#edit_ape_checkbox').off('change').on('change', function() {
                if ($(this).is(':checked')) {
                    previousValueEdit = $('#edit_placa_veiculo').val() !== 'APE' ? $('#edit_placa_veiculo').val() : '';
                    $('#edit_placa_veiculo').val('APE').prop('readonly', true).removeAttr('required');
                } else {
                    $('#edit_placa_veiculo').val(previousValueEdit).prop('readonly', false).attr('required', 'required');
                }
            });
            
            // Verificar estado inicial
            if ($('#edit_placa_veiculo').val() === 'APE') {
                $('#edit_ape_checkbox').prop('checked', true);
                $('#edit_placa_veiculo').prop('readonly', true);
            }
        });
        
        console.log('🎯 Sistema de máscaras inicializado com Bootstrap 4.6.2!');
        
    });
    </script>
    
    <?php
    // Incluir rodapé LGPD
    require_once __DIR__ . '/../components/lgpd-footer.php';
    
    // Banner de Cookies LGPD
    require_once BASE_PATH . '/src/services/CookieService.php';
    CookieService::includeBanner();
    ?>
</body>
</html>