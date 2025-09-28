<?php
/**
 * 📄 FORMULÁRIO DE FUNCIONÁRIOS - REFATORADO COM LAYOUTSERVICE
 * 
 * Página refatorada para usar o sistema unificado de layout,
 * eliminando duplicação de código de navegação.
 */

// Configuração da página
$pageConfig = [
    'title' => 'Cadastro de Funcionário - Sistema de Controle de Acesso',
    'context' => 'employees',
    'pageTitle' => 'Cadastro de Funcionário',
    'breadcrumbs' => [
        ['name' => 'Início', 'url' => '/dashboard'],
        ['name' => 'Funcionários', 'url' => '/employees'],
        ['name' => 'Cadastro', 'active' => true]
    ],
    'activeMenu' => 'employees'
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];
?>

<!-- 📄 CONTEÚDO ESPECÍFICO DA PÁGINA -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= isset($employee) ? 'Editar' : 'Cadastrar' ?> Funcionário</h3>
            <div class="card-tools">
                <a href="/employees" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    Funcionário <?= isset($employee) ? 'atualizado' : 'cadastrado' ?> com sucesso!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    Erro ao <?= isset($employee) ? 'atualizar' : 'cadastrar' ?> funcionário.
                </div>
            <?php endif; ?>
            
            <form id="employeeForm" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?= htmlspecialchars($employee['nome'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" class="form-control cpf-mask" 
                                   value="<?= htmlspecialchars($employee['cpf'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cargo">Cargo *</label>
                            <input type="text" id="cargo" name="cargo" class="form-control" 
                                   value="<?= htmlspecialchars($employee['cargo'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_admissao">Data de Admissão *</label>
                            <input type="date" id="data_admissao" name="data_admissao" class="form-control" 
                                   value="<?= htmlspecialchars($employee['data_admissao'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" class="form-control phone-mask" 
                                   value="<?= htmlspecialchars($employee['telefone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($employee['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="foto">Foto do Funcionário</label>
                    <input type="file" id="foto" name="foto" class="form-control-file" accept="image/*">
                    <?php if (isset($employee['foto']) && $employee['foto']): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($employee['foto']) ?>" 
                                 alt="Foto atual" class="img-thumbnail" style="max-width: 150px;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="ativo" name="ativo" class="form-check-input" 
                               value="1" <?= (!isset($employee) || $employee['ativo']) ? 'checked' : '' ?>>
                        <label for="ativo" class="form-check-label">Funcionário Ativo</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= isset($employee) ? 'Atualizar' : 'Cadastrar' ?>
                    </button>
                    <a href="/employees" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Renderizar layout end
echo $layout['end'];
?>

<script>
// Máscaras para CPF e telefone
$(document).ready(function() {
    $('.cpf-mask').mask('000.000.000-00', {reverse: true});
    $('.phone-mask').mask('(00) 00000-0000');
    
    // Validação do formulário
    $('#employeeForm').on('submit', function(e) {
        var nome = $('#nome').val().trim();
        var cpf = $('#cpf').val().trim();
        var cargo = $('#cargo').val().trim();
        var dataAdmissao = $('#data_admissao').val();
        
        if (!nome || !cpf || !cargo || !dataAdmissao) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
            return false;
        }
    });
});
</script>