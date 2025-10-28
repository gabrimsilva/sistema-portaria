<?php
require_once __DIR__ . '/../../src/services/NavigationService.php';
$isEdit = isset($ramal) && !empty($ramal);
$pageTitle = $isEdit ? 'Editar Ramal' : 'Novo Ramal';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    
    <?= NavigationService::renderSidebar() ?>
    
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>
                            <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?>"></i> 
                            <?= $pageTitle ?>
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item"><a href="/ramais">Ramais</a></li>
                            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Novo' ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle"></i> 
                                    Dados do Ramal
                                </h3>
                            </div>
                            
                            <form method="POST" action="/ramais/<?= $isEdit ? 'atualizar' : 'salvar' ?>">
                                <div class="card-body">
                                    
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <?= htmlspecialchars($error) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <input type="hidden" name="csrf_token" value="<?= CSRFProtection::generateToken() ?>">
                                    <?php if ($isEdit): ?>
                                        <input type="hidden" name="id" value="<?= $ramal['id'] ?>">
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label for="area">
                                            Área/Setor *
                                            <small class="text-muted">(ex: Marketing, TI, Comercial)</small>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="area" 
                                               name="area" 
                                               value="<?= htmlspecialchars($ramal['area'] ?? '') ?>"
                                               placeholder="Digite a área ou setor"
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="nome">
                                            Nome da Pessoa *
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="nome" 
                                               name="nome" 
                                               value="<?= htmlspecialchars($ramal['nome'] ?? '') ?>"
                                               placeholder="Digite o nome completo"
                                               required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ramal">
                                                    Número do Ramal/Telefone *
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="ramal" 
                                                       name="ramal" 
                                                       value="<?= htmlspecialchars($ramal['ramal'] ?? '') ?>"
                                                       placeholder="Ex: 3401 ou (41) 98765-4321"
                                                       required>
                                                <small class="form-text text-muted">
                                                    Ramal interno (4 dígitos) ou telefone completo
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tipo">
                                                    Tipo de Número *
                                                </label>
                                                <select class="form-control" id="tipo" name="tipo" required>
                                                    <option value="interno" <?= ($ramal['tipo'] ?? '') === 'interno' ? 'selected' : '' ?>>
                                                        Ramal Interno (3XXX)
                                                    </option>
                                                    <option value="externo" <?= ($ramal['tipo'] ?? '') === 'externo' ? 'selected' : '' ?>>
                                                        Telefone Externo
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="observacoes">
                                            Observações
                                            <small class="text-muted">(opcional)</small>
                                        </label>
                                        <textarea class="form-control" 
                                                  id="observacoes" 
                                                  name="observacoes" 
                                                  rows="3"
                                                  placeholder="Informações adicionais..."><?= htmlspecialchars($ramal['observacoes'] ?? '') ?></textarea>
                                    </div>
                                    
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                                    </button>
                                    <a href="/ramais" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> 
                                        Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Auto-detectar tipo baseado no número digitado
    $('#ramal').on('blur', function() {
        const ramal = $(this).val().replace(/\D/g, '');
        
        if (ramal.length === 4 && ramal.startsWith('3')) {
            $('#tipo').val('interno');
        } else if (ramal.length >= 10) {
            $('#tipo').val('externo');
        }
    });
});
</script>

</body>
</html>
