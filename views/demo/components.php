<?php
/**
 * 🎨 DEMONSTRAÇÃO COMPONENTSERVICE - UI REUTILIZÁVEL
 * 
 * Página para demonstrar todos os componentes padronizados:
 * - Cards responsivos
 * - Tabelas avançadas
 * - Filtros inteligentes
 * - Botões e alertas
 */

// Configuração da página
$pageConfig = [
    'title' => 'ComponentService - Demonstração de Componentes UI',
    'context' => 'default',
    'pageTitle' => 'Sistema de Componentes UI Reutilizáveis',
    'breadcrumbs' => [
        ['name' => 'Início', 'url' => '/dashboard'],
        ['name' => 'Demonstrações', 'url' => '#'],
        ['name' => 'Componentes UI', 'active' => true]
    ],
    'additionalCSS' => ['https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css'],
    'additionalJS' => ['https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', 'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js']
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];

// Sample data for demonstrations
$sampleEmployees = [
    ['id' => 1, 'nome' => 'João Silva', 'cargo' => 'Desenvolvedor', 'status' => 'Ativo', 'salario' => 5000],
    ['id' => 2, 'nome' => 'Maria Santos', 'cargo' => 'Designer', 'status' => 'Ativo', 'salario' => 4500],
    ['id' => 3, 'nome' => 'Pedro Costa', 'cargo' => 'Gerente', 'status' => 'Inativo', 'salario' => 7000],
    ['id' => 4, 'nome' => 'Ana Oliveira', 'cargo' => 'Analista', 'status' => 'Ativo', 'salario' => 4000]
];
?>

<!-- 🎨 DEMONSTRAÇÃO DE COMPONENTES -->
<div class="row">
    <div class="col-md-12">
        <?= ComponentService::renderAlert(
            'Esta página demonstra todos os componentes UI padronizados do ComponentService. Use estes componentes para manter consistência visual em todo o sistema.',
            'info'
        ) ?>
    </div>
</div>

<!-- DEMONSTRAÇÃO DE CARDS -->
<div class="row">
    <div class="col-md-6">
        <?= ComponentService::renderCard([
            'title' => 'Card Simples',
            'icon' => 'fas fa-info-circle',
            'safeContent' => true, // Permite HTML seguro
            'content' => '
                <p>Este é um card básico com título e ícone.</p>
                <p>Pode conter qualquer conteúdo HTML e é totalmente responsivo.</p>
                <ul>
                    <li>Suporte a ícones</li>
                    <li>Classes customizáveis</li>
                    <li>Responsivo por padrão</li>
                </ul>
            '
        ]) ?>
    </div>
    
    <div class="col-md-6">
        <?= ComponentService::renderCard([
            'title' => 'Card com Funcionalidades',
            'icon' => 'fas fa-cogs',
            'collapsible' => true,
            'maximizable' => true,
            'actions' => [
                [
                    'text' => 'Exportar',
                    'icon' => 'fas fa-download',
                    'class' => 'btn btn-success btn-sm',
                    'url' => '#'
                ]
            ],
            'safeContent' => true, // HTML seguro permitido
            'content' => '
                <div class="alert alert-success">
                    <strong>Card Avançado!</strong> Este card possui:
                </div>
                <ul>
                    <li><strong>Collapse:</strong> Clique no botão "-" no header</li>
                    <li><strong>Maximize:</strong> Clique no botão "expand"</li>
                    <li><strong>Ações customizadas:</strong> Botão "Exportar"</li>
                </ul>
            ',
            'footer' => 'Footer do card com informações adicionais'
        ]) ?>
    </div>
</div>

<!-- DEMONSTRAÇÃO DE BOTÕES -->
<div class="row">
    <div class="col-md-12">
        <?= ComponentService::renderCard([
            'title' => 'Botões Padronizados',
            'icon' => 'fas fa-mouse-pointer',
            'safeContent' => true,
            'content' => '
                <h5>Botões por Tipo:</h5>
                <div class="mb-3">
                    ' . ComponentService::renderButton([
                        'text' => 'Primário',
                        'icon' => 'fas fa-star',
                        'class' => 'btn btn-primary'
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Sucesso',
                        'icon' => 'fas fa-check',
                        'class' => 'btn btn-success'
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Aviso',
                        'icon' => 'fas fa-exclamation',
                        'class' => 'btn btn-warning'
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Perigo',
                        'icon' => 'fas fa-times',
                        'class' => 'btn btn-danger'
                    ]) . '
                </div>
                
                <h5>Tamanhos:</h5>
                <div class="mb-3">
                    ' . ComponentService::renderButton([
                        'text' => 'Pequeno',
                        'icon' => 'fas fa-cog',
                        'class' => 'btn btn-info',
                        'size' => 'sm'
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Normal',
                        'icon' => 'fas fa-cog',
                        'class' => 'btn btn-info'
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Grande',
                        'icon' => 'fas fa-cog',
                        'class' => 'btn btn-info',
                        'size' => 'lg'
                    ]) . '
                </div>
                
                <h5>Estados:</h5>
                <div class="mb-3">
                    ' . ComponentService::renderButton([
                        'text' => 'Carregando',
                        'class' => 'btn btn-primary',
                        'loading' => true
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Desabilitado',
                        'class' => 'btn btn-secondary',
                        'disabled' => true
                    ]) . ' 
                    ' . ComponentService::renderButton([
                        'text' => 'Com Tooltip',
                        'class' => 'btn btn-dark',
                        'tooltip' => 'Esta é uma dica para o usuário'
                    ]) . '
                </div>
            '
        ]) ?>
    </div>
</div>

<!-- DEMONSTRAÇÃO DE FILTROS -->
<div class="row">
    <div class="col-md-12">
        <?= ComponentService::renderFilters([
            'title' => 'Filtros Inteligentes',
            'icon' => 'fas fa-filter',
            'method' => 'GET',
            'action' => '',
            'cols' => 'auto',
            'filters' => [
                [
                    'name' => 'nome',
                    'label' => 'Nome',
                    'type' => 'text',
                    'placeholder' => 'Digite o nome...'
                ],
                [
                    'name' => 'cargo',
                    'label' => 'Cargo',
                    'type' => 'select',
                    'options' => [
                        'desenvolvedor' => 'Desenvolvedor',
                        'designer' => 'Designer',
                        'gerente' => 'Gerente',
                        'analista' => 'Analista'
                    ]
                ],
                [
                    'name' => 'status',
                    'label' => 'Status',
                    'type' => 'select',
                    'options' => [
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo'
                    ]
                ],
                [
                    'name' => 'data_inicio',
                    'label' => 'Data Início',
                    'type' => 'date'
                ],
                [
                    'name' => 'salario_min',
                    'label' => 'Salário Mínimo',
                    'type' => 'number',
                    'placeholder' => '0'
                ]
            ]
        ]) ?>
    </div>
</div>

<!-- DEMONSTRAÇÃO DE TABELA -->
<div class="row">
    <div class="col-md-12">
        <?= ComponentService::renderCard([
            'title' => 'Tabela Avançada',
            'icon' => 'fas fa-table',
            'content' => ComponentService::renderTable([
                'id' => 'demo-table',
                'headers' => [
                    ['text' => 'ID', 'field' => 'id', 'width' => '60'],
                    ['text' => 'Nome', 'field' => 'nome'],
                    ['text' => 'Cargo', 'field' => 'cargo'],
                    [
                        'text' => 'Status', 
                        'field' => 'status',
                        'formatter' => function($value, $row) {
                            $class = $value === 'Ativo' ? 'badge-success' : 'badge-danger';
                            return '<span class="badge ' . $class . '">' . htmlspecialchars($value) . '</span>';
                        }
                    ],
                    [
                        'text' => 'Salário',
                        'field' => 'salario',
                        'formatter' => function($value, $row) {
                            return 'R$ ' . number_format($value, 2, ',', '.');
                        }
                    ]
                ],
                'data' => $sampleEmployees,
                'selectable' => true,
                'sortable' => true,
                'searchable' => true,
                'pagination' => true,
                'pageSize' => 3,
                'actions' => [
                    [
                        'text' => 'Editar',
                        'icon' => 'fas fa-edit',
                        'class' => 'btn btn-primary btn-sm',
                        'url' => '/employees/edit/{id}'
                    ],
                    [
                        'text' => 'Excluir',
                        'icon' => 'fas fa-trash',
                        'class' => 'btn btn-danger btn-sm',
                        'url' => '/employees/delete/{id}',
                        'onclick' => 'return confirm("Tem certeza?")'
                    ]
                ]
            ])
        ]) ?>
    </div>
</div>

<!-- DEMONSTRAÇÃO DE ALERTAS -->
<div class="row">
    <div class="col-md-6">
        <?= ComponentService::renderCard([
            'title' => 'Alertas Padronizados',
            'icon' => 'fas fa-bell',
            'safeContent' => true,
            'content' => '
                <h6>Tipos de Alerta:</h6>
                ' . ComponentService::renderAlert('Operação realizada com sucesso!', 'success') . '
                ' . ComponentService::renderAlert('Informação importante para o usuário.', 'info') . '
                ' . ComponentService::renderAlert('Atenção: verifique os dados inseridos.', 'warning') . '
                ' . ComponentService::renderAlert('Erro: não foi possível completar a operação.', 'danger') . '
            '
        ]) ?>
    </div>
    
    <div class="col-md-6">
        <?= ComponentService::renderCard([
            'title' => 'Card com Estado de Loading',
            'icon' => 'fas fa-spinner',
            'loading' => true
        ]) ?>
    </div>
</div>

<!-- CÓDIGO DE EXEMPLO -->
<div class="row">
    <div class="col-md-12">
        <?= ComponentService::renderCard([
            'title' => 'Código de Exemplo',
            'icon' => 'fas fa-code',
            'collapsible' => true,
            'safeContent' => true,
            'content' => '
                <h6>Como usar os componentes:</h6>
                <pre><code>&lt;?php
// Card simples
echo ComponentService::renderCard([
    \'title\' => \'Meu Card\',
    \'icon\' => \'fas fa-star\',
    \'content\' => \'&lt;p&gt;Conteúdo do card&lt;/p&gt;\'
]);

// Botão
echo ComponentService::renderButton([
    \'text\' => \'Salvar\',
    \'icon\' => \'fas fa-save\',
    \'class\' => \'btn btn-success\',
    \'url\' => \'/save\'
]);

// Tabela
echo ComponentService::renderTable([
    \'headers\' => [
        [\'text\' => \'Nome\', \'field\' => \'nome\'],
        [\'text\' => \'Email\', \'field\' => \'email\']
    ],
    \'data\' => $dados,
    \'actions\' => [
        [\'text\' => \'Editar\', \'url\' => \'/edit/{id}\']
    ]
]);

// Filtros
echo ComponentService::renderFilters([
    \'filters\' => [
        [\'name\' => \'nome\', \'type\' => \'text\'],
        [\'name\' => \'status\', \'type\' => \'select\', \'options\' => $options]
    ]
]);

// Alerta
echo ComponentService::renderAlert(\'Sucesso!\', \'success\');
?&gt;</code></pre>
            '
        ]) ?>
    </div>
</div>

<?php
// Renderizar layout end
echo $layout['end'];
?>

<style>
/* CSS específico para demonstração */
.demo-spacing {
    margin-bottom: 20px;
}

pre {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    font-size: 14px;
}

code {
    color: #e83e8c;
}

.badge {
    font-size: 0.875em;
}
</style>

<script>
$(document).ready(function() {
    // Ativar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Demonstrar funcionalidade da tabela
    console.log('ComponentService Demo carregado com sucesso!');
});
</script>