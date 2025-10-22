<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= CSRFProtection::generateToken() ?>">
    <title>Visitantes - Sistema de Controle de Acesso</title>
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
                            <h1 class="m-0">Visitantes</h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Visitante cadastrado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            Visitante atualizado com sucesso!
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="card-title">Lista de Visitantes</h3>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="/visitantes?action=new" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Novo Visitante
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF ou empresa" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="setor" class="form-control">
                                            <option value="">Todos os setores</option>
                                            <?php foreach ($setores as $s): ?>
                                                <option value="<?= htmlspecialchars($s['setor']) ?>" <?= ($_GET['setor'] ?? '') === $s['setor'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['setor']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="status" class="form-control">
                                            <option value="">Todos os status</option>
                                            <option value="ativo" <?= ($_GET['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo (na empresa)</option>
                                            <option value="saiu" <?= ($_GET['status'] ?? '') === 'saiu' ? 'selected' : '' ?>>Saiu</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-secondary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nome</th>
                                            <th>CPF</th>
                                            <th>País</th>
                                            <th>Empresa</th>
                                            <th>Funcionário Responsável</th>
                                            <th>Setor</th>
                                            <th>Hora Entrada</th>
                                            <th>Hora Saída</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($visitantes)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center">Nenhum visitante encontrado</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($visitantes as $visitante): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($visitante['nome']) ?></td>
                                                <td><?= htmlspecialchars($visitante['cpf']) ?></td>
                                                <td><?= htmlspecialchars($visitante['doc_country'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($visitante['empresa']) ?></td>
                                                <td><?= htmlspecialchars($visitante['funcionario_responsavel']) ?></td>
                                                <td><?= htmlspecialchars($visitante['setor']) ?></td>
                                                <td><?= $visitante['hora_entrada'] ? date('d/m/Y H:i', strtotime($visitante['hora_entrada'])) : '-' ?></td>
                                                <td><?= $visitante['hora_saida'] ? date('d/m/Y H:i', strtotime($visitante['hora_saida'])) : '-' ?></td>
                                                <td>
                                                    <?php 
                                                    $status = '';
                                                    $badgeClass = '';
                                                    if ($visitante['hora_entrada'] && !$visitante['hora_saida']) {
                                                        $status = 'Na empresa';
                                                        $badgeClass = 'success';
                                                    } elseif ($visitante['hora_saida']) {
                                                        $status = 'Saiu';
                                                        $badgeClass = 'secondary';
                                                    } else {
                                                        $status = 'Aguardando';
                                                        $badgeClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?= $badgeClass ?>"><?= $status ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" 
                                                            class="btn btn-sm btn-primary btn-editar" 
                                                            data-id="<?= $visitante['id'] ?>"
                                                            data-tipo="Visitante"
                                                            data-nome="<?= htmlspecialchars($visitante['nome']) ?>"
                                                            data-cpf="<?= htmlspecialchars($visitante['cpf']) ?>"
                                                            data-empresa="<?= htmlspecialchars($visitante['empresa']) ?>"
                                                            data-setor="<?= htmlspecialchars($visitante['setor']) ?>"
                                                            data-funcionario="<?= htmlspecialchars($visitante['funcionario_responsavel']) ?>"
                                                            data-placa_veiculo="<?= htmlspecialchars($visitante['placa_veiculo'] ?? '') ?>"
                                                            data-toggle="modal" 
                                                            data-target="#modalEditar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if (!$visitante['hora_entrada']): ?>
                                                            <form method="POST" action="/visitantes?action=entrada" class="d-inline">
                                                                <?= CSRFProtection::getHiddenInput() ?>
                                                                <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" title="Registrar Entrada">
                                                                    <i class="fas fa-sign-in-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php elseif (!$visitante['hora_saida']): ?>
                                                            <form method="POST" action="/visitantes?action=saida" class="d-inline">
                                                                <?= CSRFProtection::getHiddenInput() ?>
                                                                <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning" title="Registrar Saída">
                                                                    <i class="fas fa-sign-out-alt"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="POST" action="/visitantes?action=delete" class="d-inline">
                                                            <?= CSRFProtection::getHiddenInput() ?>
                                                            <input type="hidden" name="id" value="<?= $visitante['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                                    <input type="text" class="form-control" id="edit_cpf" name="cpf" placeholder="000.000.000-00">
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
                        
                        <!-- Campo Placa de Veículo - visível apenas para Visitantes e Prestadores -->
                        <div id="campo_placa_veiculo" class="form-group" style="display: none;">
                            <label for="edit_placa_veiculo">Placa de Veículo</label>
                            <input type="text" class="form-control" id="edit_placa_veiculo" name="placa_veiculo" placeholder="ABC-1234">
                        </div>
                        
                        <!-- Campos específicos para visitantes -->
                        <div id="campo_funcionario_responsavel" class="form-group" style="display: none;">
                            <label for="edit_funcionario_responsavel">Funcionário Responsável</label>
                            <input type="text" class="form-control" id="edit_funcionario_responsavel" name="funcionario_responsavel">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Manipular clique do botão editar
        $(document).on('click', '.btn-editar', function(e) {
            e.preventDefault();
            const btn = $(this);
            
            // Evitar múltiplos cliques
            if (btn.hasClass('loading')) {
                return false;
            }
            btn.addClass('loading');
            
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
            
            // Mostrar/ocultar campos específicos baseado no tipo
            $('#campo_funcionario_responsavel, #campo_hora_saida, #campo_observacao').hide();
            
            // Mostrar campo de hora de saída para todos os tipos
            $('#campo_hora_saida').show();
            
            // Mostrar/ocultar campos específicos para Profissional Renner
            if (tipo === 'Profissional Renner') {
                $('#campo_empresa').hide();
                $('#campo_cpf').hide();
                $('#campo_placa_veiculo').hide();
                $('.modal-header').removeClass('bg-info').addClass('bg-primary');
            } else {
                $('#campo_empresa').show();
                $('#campo_cpf').show();
                $('#campo_placa_veiculo').show();
                $('.modal-header').removeClass('bg-primary').addClass('bg-info');
            }
            
            if (tipo === 'Visitante') {
                $('#campo_funcionario_responsavel').show();
                $('#edit_funcionario_responsavel').val(funcionario || '');
                
                // Buscar dados específicos do visitante para preencher hora de saída
                $.ajax({
                    url: `/visitantes?action=get_data&id=${id}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.hora_saida) {
                            const horaSaidaFormatada = response.data.hora_saida.replace(' ', 'T').slice(0, 16);
                            $('#edit_hora_saida').val(horaSaidaFormatada);
                        }
                    },
                    error: function() {
                        $('#edit_hora_saida').val('');
                    },
                    complete: function() {
                        btn.removeClass('loading');
                        // Abrir modal após carregar dados
                        const modalElement = document.getElementById('modalEditar');
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                });
            } else if (tipo === 'Prestador') {
                $('#campo_observacao').show();
                $('#edit_observacao').val('');
                
                $.ajax({
                    url: `/prestadores-servico?action=get_data&id=${id}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.saida) {
                            const saidaFormatada = response.data.saida.replace(' ', 'T').slice(0, 16);
                            $('#edit_hora_saida').val(saidaFormatada);
                        }
                        if (response.data.observacao) {
                            $('#edit_observacao').val(response.data.observacao);
                        }
                    },
                    error: function() {
                        $('#edit_hora_saida').val('');
                    },
                    complete: function() {
                        btn.removeClass('loading');
                        // Abrir modal após carregar dados
                        const modalElement = document.getElementById('modalEditar');
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                });
            } else {
                btn.removeClass('loading');
                // Abrir modal diretamente para outros tipos
                const modalElement = document.getElementById('modalEditar');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        });

        // Salvar edições
        $('#btnSalvarEdicao').click(function() {
            const formData = new FormData($('#formEditar')[0]);
            const tipo = $('#edit_tipo_original').val();
            
            let url = '';
            if (tipo === 'Visitante') {
                url = '/visitantes?action=update_ajax';
            } else if (tipo === 'Prestador') {
                url = '/prestadores-servico?action=update_ajax';
            } else if (tipo === 'Profissional Renner') {
                url = '/profissionais-renner?action=update_ajax';
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro ao salvar alterações');
                }
            });
        });
    });
    </script>
</body>
</html>