<?php

/**
 * Serviço centralizado para componentes de formulários
 * 
 * Padroniza inputs, validações, botões e outros elementos
 * de interface de usuário em todo o sistema.
 */
class FormService
{
    /**
     * Renderiza um input text padrão
     * 
     * @param array $config Configuração do campo
     * @return string HTML do input
     */
    public static function renderTextInput($config)
    {
        $defaults = [
            'type' => 'text',
            'class' => 'form-control',
            'required' => false,
            'placeholder' => '',
            'value' => '',
            'maxlength' => null,
            'style' => null
        ];
        
        $config = array_merge($defaults, $config);
        
        $html = '<div class="form-group">';
        
        // Label
        $html .= '<label for="' . htmlspecialchars($config['id']) . '">';
        $html .= htmlspecialchars($config['label']);
        if ($config['required']) {
            $html .= ' <span class="text-danger">*</span>';
        }
        $html .= '</label>';
        
        // Input
        $html .= '<input type="' . htmlspecialchars($config['type']) . '"';
        $html .= ' class="' . htmlspecialchars($config['class']) . '"';
        $html .= ' id="' . htmlspecialchars($config['id']) . '"';
        $html .= ' name="' . htmlspecialchars($config['name']) . '"';
        $html .= ' value="' . htmlspecialchars($config['value']) . '"';
        
        if (!empty($config['placeholder'])) {
            $html .= ' placeholder="' . htmlspecialchars($config['placeholder']) . '"';
        }
        
        if ($config['maxlength']) {
            $html .= ' maxlength="' . (int)$config['maxlength'] . '"';
        }
        
        if ($config['style']) {
            $html .= ' style="' . htmlspecialchars($config['style']) . '"';
        }
        
        if ($config['required']) {
            $html .= ' required';
        }
        
        $html .= '>';
        
        // Help text
        if (!empty($config['help'])) {
            $html .= '<small class="form-text text-muted">' . htmlspecialchars($config['help']) . '</small>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza campo CPF com máscara automática
     * 
     * @param string $id ID do campo
     * @param string $name Nome do campo  
     * @param string $value Valor atual
     * @param bool $required Se é obrigatório
     * @return string HTML do campo CPF
     */
    public static function renderCpfInput($id, $name, $value = '', $required = false)
    {
        return self::renderTextInput([
            'id' => $id,
            'name' => $name,
            'label' => 'CPF',
            'value' => $value,
            'placeholder' => '000.000.000-00',
            'maxlength' => 14,
            'required' => $required,
            'data-mask' => 'cpf',
            'class' => 'form-control cpf-input'
        ]);
    }
    
    /**
     * Renderiza campo de placa com checkbox "A pé"
     * 
     * @param string $id ID do campo
     * @param string $name Nome do campo
     * @param string $value Valor atual
     * @return string HTML do campo placa
     */
    public static function renderPlacaInput($id, $name, $value = '')
    {
        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($id) . '">Placa de Veículo <span class="text-danger">*</span></label>';
        $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '"';
        $html .= ' value="' . htmlspecialchars($value) . '" placeholder="ABC-1234"';
        $html .= ' style="text-transform: uppercase;" required>';
        $html .= '<span class="input-group-text">';
        $html .= '<input type="checkbox" id="ape_checkbox" ' . ($value === 'APE' ? 'checked' : '') . '>';
        $html .= '<label for="ape_checkbox" class="ml-1 mb-0">A pé</label>';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<small class="form-text text-muted">Marque "A pé" se não houver veículo</small>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza campo datetime-local padronizado
     * 
     * @param array $config Configuração do campo
     * @return string HTML do campo datetime
     */
    public static function renderDateTimeInput($config)
    {
        $config['type'] = 'datetime-local';
        $config['class'] = 'form-control';
        
        // Formatar valor se for timestamp
        if (!empty($config['value']) && !str_contains($config['value'], 'T')) {
            $config['value'] = date('Y-m-d\TH:i', strtotime($config['value']));
        }
        
        return self::renderTextInput($config);
    }
    
    /**
     * Renderiza textarea padronizada
     * 
     * @param array $config Configuração do campo
     * @return string HTML da textarea
     */
    public static function renderTextarea($config)
    {
        $defaults = [
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => '',
            'value' => ''
        ];
        
        $config = array_merge($defaults, $config);
        
        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($config['id']) . '">' . htmlspecialchars($config['label']) . '</label>';
        $html .= '<textarea class="' . htmlspecialchars($config['class']) . '"';
        $html .= ' id="' . htmlspecialchars($config['id']) . '"';
        $html .= ' name="' . htmlspecialchars($config['name']) . '"';
        $html .= ' rows="' . (int)$config['rows'] . '"';
        
        if (!empty($config['placeholder'])) {
            $html .= ' placeholder="' . htmlspecialchars($config['placeholder']) . '"';
        }
        
        $html .= '>' . htmlspecialchars($config['value']) . '</textarea>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza botões de ação padrão do formulário
     * 
     * @param string $backUrl URL de retorno
     * @param string $saveText Texto do botão salvar
     * @return string HTML dos botões
     */
    public static function renderFormButtons($backUrl, $saveText = 'Salvar')
    {
        $html = '<div class="card-footer">';
        $html .= '<button type="submit" class="btn btn-primary">';
        $html .= '<i class="fas fa-save"></i> ' . htmlspecialchars($saveText);
        $html .= '</button>';
        $html .= ' ';
        $html .= '<a href="' . htmlspecialchars($backUrl) . '" class="btn btn-secondary">';
        $html .= '<i class="fas fa-arrow-left"></i> Voltar';
        $html .= '</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza alerta de erro padronizado
     * 
     * @param string $message Mensagem de erro
     * @param string $type Tipo do alerta (danger, warning, info, success)
     * @return string HTML do alerta
     */
    public static function renderAlert($message, $type = 'danger')
    {
        if (empty($message)) {
            return '';
        }
        
        $html = '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible">';
        $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= htmlspecialchars($message);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Renderiza JavaScript para máscaras e validações
     * 
     * @param array $features Recursos a incluir ('cpf', 'placa', 'phone', etc.)
     * @return string JavaScript para validações
     */
    public static function renderFormScripts($features = [])
    {
        $html = '<script>';
        $html .= '$(document).ready(function() {';
        
        // Script para checkbox "A pé"
        if (in_array('placa', $features)) {
            $html .= 'let previousPlacaValue = "";';
            $html .= '$("#ape_checkbox").change(function() {';
            $html .= 'if ($(this).is(":checked")) {';
            $html .= 'previousPlacaValue = $("#placa_veiculo").val() !== "APE" ? $("#placa_veiculo").val() : "";';
            $html .= '$("#placa_veiculo").val("APE").prop("readonly", true);';
            $html .= '} else {';
            $html .= '$("#placa_veiculo").val(previousPlacaValue).prop("readonly", false);';
            $html .= '}';
            $html .= '});';
            
            // Verificar estado inicial
            $html .= 'if ($("#placa_veiculo").val() === "APE") {';
            $html .= '$("#ape_checkbox").prop("checked", true);';
            $html .= '$("#placa_veiculo").prop("readonly", true);';
            $html .= '}';
        }
        
        $html .= '});';
        
        // Máscara CPF
        if (in_array('cpf', $features)) {
            $html .= 'document.getElementById("cpf").addEventListener("input", function (e) {';
            $html .= 'var value = e.target.value.replace(/\\D/g, "");';
            $html .= 'value = value.replace(/(\\d{3})(\\d)/, "$1.$2");';
            $html .= 'value = value.replace(/(\\d{3})(\\d)/, "$1.$2");';
            $html .= 'value = value.replace(/(\\d{3})(\\d{1,2})$/, "$1-$2");';
            $html .= 'e.target.value = value;';
            $html .= '});';
        }
        
        $html .= '</script>';
        
        return $html;
    }
}