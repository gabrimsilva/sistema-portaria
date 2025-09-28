<?php

/**
 * 🎨 ComponentService - Sistema de Componentes UI Reutilizáveis
 * 
 * Fornece componentes padronizados para interface consistente:
 * - Cards responsivos 
 * - Filtros inteligentes
 * - Tabelas com funcionalidades
 * - Botões e alertas
 * - Formulários estruturados
 */
class ComponentService
{
    /**
     * Renderiza card padrão do sistema
     * 
     * @param array $config Configuração do card
     * @return string HTML do card
     */
    public static function renderCard($config = [])
    {
        $defaults = [
            'title' => '',
            'icon' => 'fas fa-info-circle',
            'headerClass' => '',
            'bodyClass' => '',
            'footerClass' => '',
            'actions' => [],
            'collapsible' => false,
            'removable' => false,
            'maximizable' => false,
            'loading' => false,
            'content' => '',
            'footer' => '',
            'safeContent' => false, // Se true, não escapa HTML do content
            'safeFooter' => false   // Se true, não escapa HTML do footer
        ];
        
        $c = array_merge($defaults, $config);
        $cardId = 'card_' . uniqid();
        
        $html = '<div class="card' . ($c['loading'] ? ' card-loading' : '') . '" id="' . $cardId . '">';
        
        // Header
        if ($c['title'] || $c['actions'] || $c['collapsible'] || $c['removable'] || $c['maximizable']) {
            $html .= '<div class="card-header ' . htmlspecialchars($c['headerClass']) . '">';
            
            if ($c['title']) {
                $html .= '<h3 class="card-title">';
                if ($c['icon']) {
                    $html .= '<i class="' . htmlspecialchars($c['icon']) . '"></i> ';
                }
                $html .= htmlspecialchars($c['title']);
                $html .= '</h3>';
            }
            
            // Card tools
            if ($c['actions'] || $c['collapsible'] || $c['removable'] || $c['maximizable']) {
                $html .= '<div class="card-tools">';
                
                // Custom actions
                foreach ($c['actions'] as $action) {
                    $html .= self::renderButton($action);
                }
                
                // Standard tools
                if ($c['collapsible']) {
                    $html .= '<button type="button" class="btn btn-tool" data-card-widget="collapse">';
                    $html .= '<i class="fas fa-minus"></i></button>';
                }
                
                if ($c['maximizable']) {
                    $html .= '<button type="button" class="btn btn-tool" data-card-widget="maximize">';
                    $html .= '<i class="fas fa-expand"></i></button>';
                }
                
                if ($c['removable']) {
                    $html .= '<button type="button" class="btn btn-tool" data-card-widget="remove">';
                    $html .= '<i class="fas fa-times"></i></button>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        // Body
        $html .= '<div class="card-body ' . htmlspecialchars($c['bodyClass']) . '">';
        
        if ($c['loading']) {
            $html .= '<div class="text-center">';
            $html .= '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i>';
            $html .= '<p class="mt-2">Carregando...</p>';
            $html .= '</div>';
        } else {
            // Escapar conteúdo por segurança, exceto se marcado como safe
            if ($c['safeContent']) {
                $html .= $c['content']; // HTML raw - USE COM CUIDADO
            } else {
                $html .= htmlspecialchars($c['content']);
            }
        }
        
        $html .= '</div>';
        
        // Footer
        if ($c['footer']) {
            $html .= '<div class="card-footer ' . htmlspecialchars($c['footerClass']) . '">';
            // Escapar footer por segurança, exceto se marcado como safe
            if ($c['safeFooter']) {
                $html .= $c['footer']; // HTML raw - USE COM CUIDADO
            } else {
                $html .= htmlspecialchars($c['footer']);
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza tabela com funcionalidades avançadas
     * 
     * @param array $config Configuração da tabela
     * @return string HTML da tabela
     */
    public static function renderTable($config = [])
    {
        $defaults = [
            'id' => 'table_' . uniqid(),
            'class' => 'table table-bordered table-hover',
            'responsive' => true,
            'striped' => true,
            'condensed' => false,
            'sortable' => true,
            'searchable' => true,
            'pagination' => true,
            'exportable' => false,
            'selectable' => false,
            'headers' => [],
            'data' => [],
            'actions' => [],
            'emptyMessage' => 'Nenhum registro encontrado',
            'pageSize' => 10
        ];
        
        $c = array_merge($defaults, $config);
        
        $html = '';
        
        // Wrapper responsivo
        if ($c['responsive']) {
            $html .= '<div class="table-responsive">';
        }
        
        $tableClass = $c['class'];
        if ($c['striped']) $tableClass .= ' table-striped';
        if ($c['condensed']) $tableClass .= ' table-sm';
        
        $html .= '<table id="' . htmlspecialchars($c['id']) . '" class="' . htmlspecialchars($tableClass) . '"';
        
        // Data attributes para funcionalidades
        if ($c['sortable']) $html .= ' data-sortable="true"';
        if ($c['searchable']) $html .= ' data-searchable="true"';
        if ($c['pagination']) $html .= ' data-pagination="true" data-page-size="' . $c['pageSize'] . '"';
        
        $html .= '>';
        
        // Headers
        if (!empty($c['headers'])) {
            $html .= '<thead class="table-dark">';
            $html .= '<tr>';
            
            if ($c['selectable']) {
                $html .= '<th width="30">';
                $html .= '<input type="checkbox" id="select-all-' . $c['id'] . '" class="select-all">';
                $html .= '</th>';
            }
            
            foreach ($c['headers'] as $header) {
                $html .= '<th';
                if (isset($header['width'])) {
                    $html .= ' width="' . htmlspecialchars($header['width']) . '"';
                }
                if (isset($header['class'])) {
                    $html .= ' class="' . htmlspecialchars($header['class']) . '"';
                }
                if (isset($header['sortable']) && !$header['sortable']) {
                    $html .= ' data-sortable="false"';
                }
                $html .= '>';
                $html .= htmlspecialchars($header['text'] ?? $header);
                $html .= '</th>';
            }
            
            if (!empty($c['actions'])) {
                $html .= '<th width="120" data-sortable="false">Ações</th>';
            }
            
            $html .= '</tr>';
            $html .= '</thead>';
        }
        
        // Body
        $html .= '<tbody>';
        
        if (empty($c['data'])) {
            $colspan = count($c['headers']);
            if ($c['selectable']) $colspan++;
            if (!empty($c['actions'])) $colspan++;
            
            $html .= '<tr>';
            $html .= '<td colspan="' . $colspan . '" class="text-center text-muted py-4">';
            $html .= htmlspecialchars($c['emptyMessage']);
            $html .= '</td>';
            $html .= '</tr>';
        } else {
            foreach ($c['data'] as $rowIndex => $row) {
                $html .= '<tr>';
                
                if ($c['selectable']) {
                    $html .= '<td>';
                    $html .= '<input type="checkbox" name="selected[]" value="' . htmlspecialchars($row['id'] ?? $rowIndex) . '" class="row-select">';
                    $html .= '</td>';
                }
                
                foreach ($c['headers'] as $headerIndex => $header) {
                    $field = $header['field'] ?? $headerIndex;
                    $value = $row[$field] ?? '';
                    
                    $html .= '<td';
                    if (isset($header['class'])) {
                        $html .= ' class="' . htmlspecialchars($header['class']) . '"';
                    }
                    $html .= '>';
                    
                    // Formatar valor se necessário
                    if (isset($header['formatter'])) {
                        $value = call_user_func($header['formatter'], $value, $row);
                    } else {
                        $value = htmlspecialchars($value);
                    }
                    
                    $html .= $value;
                    $html .= '</td>';
                }
                
                // Actions
                if (!empty($c['actions'])) {
                    $html .= '<td>';
                    $html .= '<div class="btn-group btn-group-sm">';
                    
                    foreach ($c['actions'] as $action) {
                        $actionConfig = $action;
                        if (isset($action['url'])) {
                            $actionConfig['url'] = str_replace('{id}', $row['id'] ?? $rowIndex, $action['url']);
                        }
                        $html .= self::renderButton($actionConfig);
                    }
                    
                    $html .= '</div>';
                    $html .= '</td>';
                }
                
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        if ($c['responsive']) {
            $html .= '</div>';
        }
        
        // JavaScript para funcionalidades REAIS usando DataTables
        if ($c['sortable'] || $c['searchable'] || $c['pagination']) {
            $html .= self::generateDataTableScript($c);
        } elseif ($c['selectable']) {
            $html .= self::generateTableScript($c);
        }
        
        return $html;
    }
    
    /**
     * Renderiza filtros inteligentes
     * 
     * @param array $config Configuração dos filtros
     * @return string HTML dos filtros
     */
    public static function renderFilters($config = [])
    {
        $defaults = [
            'title' => 'Filtros',
            'icon' => 'fas fa-filter',
            'collapsible' => true,
            'method' => 'GET',
            'action' => '',
            'filters' => [],
            'resetButton' => true,
            'submitButton' => true,
            'cols' => 'auto' // auto, 2, 3, 4, 6
        ];
        
        $c = array_merge($defaults, $config);
        
        return self::renderCard([
            'title' => $c['title'],
            'icon' => $c['icon'],
            'collapsible' => $c['collapsible'],
            'content' => self::generateFiltersContent($c)
        ]);
    }
    
    /**
     * Gera conteúdo interno dos filtros com CSRF protection
     */
    private static function generateFiltersContent($config)
    {
        $html = '<form method="' . htmlspecialchars($config['method']) . '"';
        if ($config['action']) {
            $html .= ' action="' . htmlspecialchars($config['action']) . '"';
        }
        $html .= '>';
        
        // CSRF token para POST requests
        if (strtoupper($config['method']) === 'POST') {
            $html .= '<input type="hidden" name="csrf_token" value="' . 
                     htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
        }
        
        // Determinar colunas
        $colClass = 'col-md-4';
        if ($config['cols'] === '2') $colClass = 'col-md-6';
        elseif ($config['cols'] === '3') $colClass = 'col-md-4';
        elseif ($config['cols'] === '4') $colClass = 'col-md-3';
        elseif ($config['cols'] === '6') $colClass = 'col-md-2';
        elseif ($config['cols'] === 'auto') {
            $filterCount = count($config['filters']);
            if ($filterCount <= 2) $colClass = 'col-md-6';
            elseif ($filterCount <= 3) $colClass = 'col-md-4';
            elseif ($filterCount <= 4) $colClass = 'col-md-3';
            else $colClass = 'col-md-2';
        }
        
        $html .= '<div class="row">';
        
        foreach ($config['filters'] as $filter) {
            $html .= '<div class="' . $colClass . '">';
            $html .= self::renderFilterField($filter);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Botões
        if ($config['submitButton'] || $config['resetButton']) {
            $html .= '<div class="row mt-3">';
            $html .= '<div class="col-12">';
            
            if ($config['submitButton']) {
                $html .= '<button type="submit" class="btn btn-primary">';
                $html .= '<i class="fas fa-search"></i> Filtrar';
                $html .= '</button>';
            }
            
            if ($config['resetButton']) {
                $html .= ' <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="btn btn-secondary">';
                $html .= '<i class="fas fa-undo"></i> Limpar';
                $html .= '</a>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</form>';
        
        return $html;
    }
    
    /**
     * Renderiza campo de filtro individual
     */
    private static function renderFilterField($filter)
    {
        $type = $filter['type'] ?? 'text';
        $name = $filter['name'] ?? '';
        $label = $filter['label'] ?? ucfirst($name);
        
        // Usar REQUEST para suportar GET e POST
        $value = $filter['value'] ?? ($_REQUEST[$name] ?? '');
        $placeholder = $filter['placeholder'] ?? '';
        $options = $filter['options'] ?? [];
        $class = $filter['class'] ?? 'form-control';
        
        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
        
        switch ($type) {
            case 'select':
                $html .= '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '" class="' . htmlspecialchars($class) . '">';
                $html .= '<option value="">' . ($placeholder ?: 'Todos') . '</option>';
                foreach ($options as $optValue => $optLabel) {
                    $selected = ($value == $optValue) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars($optValue) . '"' . $selected . '>';
                    $html .= htmlspecialchars($optLabel);
                    $html .= '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'date':
                $html .= '<input type="date" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '" value="' . htmlspecialchars($value) . '">';
                break;
                
            case 'number':
                $html .= '<input type="number" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '" value="' . htmlspecialchars($value) . '"';
                if ($placeholder) $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
                $html .= '>';
                break;
                
            default: // text
                $html .= '<input type="text" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '" ';
                $html .= 'class="' . htmlspecialchars($class) . '" value="' . htmlspecialchars($value) . '"';
                if ($placeholder) $html .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
                $html .= '>';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza botão padronizado
     * 
     * @param array $config Configuração do botão
     * @return string HTML do botão
     */
    public static function renderButton($config = [])
    {
        $defaults = [
            'text' => 'Botão',
            'icon' => '',
            'url' => '#',
            'class' => 'btn btn-primary',
            'size' => '', // sm, lg
            'type' => 'link', // link, button, submit
            'target' => '',
            'onclick' => '',
            'disabled' => false,
            'loading' => false,
            'tooltip' => ''
        ];
        
        $c = array_merge($defaults, $config);
        
        $btnClass = $c['class'];
        if ($c['size']) $btnClass .= ' btn-' . $c['size'];
        if ($c['disabled']) $btnClass .= ' disabled';
        
        if ($c['type'] === 'button' || $c['type'] === 'submit') {
            $html = '<button type="' . $c['type'] . '" class="' . htmlspecialchars($btnClass) . '"';
            if ($c['disabled']) $html .= ' disabled';
            if ($c['onclick']) $html .= ' onclick="' . htmlspecialchars($c['onclick']) . '"';
            if ($c['tooltip']) $html .= ' title="' . htmlspecialchars($c['tooltip']) . '" data-toggle="tooltip"';
            $html .= '>';
        } else {
            $html = '<a href="' . htmlspecialchars($c['url']) . '" class="' . htmlspecialchars($btnClass) . '"';
            if ($c['target']) $html .= ' target="' . htmlspecialchars($c['target']) . '"';
            if ($c['onclick']) $html .= ' onclick="' . htmlspecialchars($c['onclick']) . '"';
            if ($c['tooltip']) $html .= ' title="' . htmlspecialchars($c['tooltip']) . '" data-toggle="tooltip"';
            $html .= '>';
        }
        
        if ($c['loading']) {
            $html .= '<i class="fas fa-spinner fa-spin"></i> ';
        } elseif ($c['icon']) {
            $html .= '<i class="' . htmlspecialchars($c['icon']) . '"></i> ';
        }
        
        $html .= htmlspecialchars($c['text']);
        
        if ($c['type'] === 'button' || $c['type'] === 'submit') {
            $html .= '</button>';
        } else {
            $html .= '</a>';
        }
        
        return $html;
    }
    
    /**
     * Gera JavaScript para funcionalidades REAIS da tabela com DataTables
     */
    private static function generateDataTableScript($config)
    {
        $script = '<script>';
        $script .= '$(document).ready(function() {';
        
        // Configuração DataTables
        $script .= '$("#' . $config['id'] . '").DataTable({';
        $script .= '"responsive": true,';
        $script .= '"autoWidth": false,';
        
        if ($config['searchable']) {
            $script .= '"searching": true,';
        } else {
            $script .= '"searching": false,';
        }
        
        if ($config['pagination']) {
            $script .= '"paging": true,';
            $script .= '"pageLength": ' . $config['pageSize'] . ',';
        } else {
            $script .= '"paging": false,';
        }
        
        if ($config['sortable']) {
            $script .= '"ordering": true,';
        } else {
            $script .= '"ordering": false,';
        }
        
        // Tradução para português
        $script .= '"language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "' . htmlspecialchars($config['emptyMessage']) . '",
            "info": "Mostrando página _PAGE_ de _PAGES_",
            "infoEmpty": "Nenhum registro disponível",
            "infoFiltered": "(filtrado de _MAX_ registros totais)",
            "search": "Pesquisar:",
            "paginate": {
                "first": "Primeira",
                "last": "Última", 
                "next": "Próxima",
                "previous": "Anterior"
            }
        }';
        
        $script .= '});';
        
        // Seleção múltipla se necessário
        if ($config['selectable']) {
            $script .= '
                $("#select-all-' . $config['id'] . '").change(function() {
                    $("#' . $config['id'] . ' .row-select").prop("checked", this.checked);
                });
                
                $("#' . $config['id'] . ' .row-select").change(function() {
                    var total = $("#' . $config['id'] . ' .row-select").length;
                    var checked = $("#' . $config['id'] . ' .row-select:checked").length;
                    $("#select-all-' . $config['id'] . '").prop("checked", total === checked);
                });
            ';
        }
        
        $script .= '});';
        $script .= '</script>';
        
        return $script;
    }
    
    /**
     * Gera JavaScript simples para seleção sem DataTables
     */
    private static function generateTableScript($config)
    {
        $script = '<script>';
        $script .= '$(document).ready(function() {';
        
        if ($config['selectable']) {
            $script .= '
                $("#select-all-' . $config['id'] . '").change(function() {
                    $("#' . $config['id'] . ' .row-select").prop("checked", this.checked);
                });
                
                $("#' . $config['id'] . ' .row-select").change(function() {
                    var total = $("#' . $config['id'] . ' .row-select").length;
                    var checked = $("#' . $config['id'] . ' .row-select:checked").length;
                    $("#select-all-' . $config['id'] . '").prop("checked", total === checked);
                });
            ';
        }
        
        $script .= '});';
        $script .= '</script>';
        
        return $script;
    }
    
    /**
     * Renderiza alerta padronizado
     * 
     * @param string $message Mensagem
     * @param string $type Tipo (success, info, warning, danger)
     * @param array $options Opções adicionais
     * @return string HTML do alerta
     */
    public static function renderAlert($message, $type = 'info', $options = [])
    {
        $defaults = [
            'dismissible' => true,
            'icon' => true,
            'class' => ''
        ];
        
        $opts = array_merge($defaults, $options);
        
        $icons = [
            'success' => 'fas fa-check-circle',
            'info' => 'fas fa-info-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'danger' => 'fas fa-times-circle'
        ];
        
        $alertClass = 'alert alert-' . $type;
        if ($opts['dismissible']) $alertClass .= ' alert-dismissible';
        if ($opts['class']) $alertClass .= ' ' . $opts['class'];
        
        $html = '<div class="' . $alertClass . '">';
        
        if ($opts['dismissible']) {
            $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            $html .= '<span aria-hidden="true">&times;</span>';
            $html .= '</button>';
        }
        
        if ($opts['icon'] && isset($icons[$type])) {
            $html .= '<i class="' . $icons[$type] . '"></i> ';
        }
        
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
}