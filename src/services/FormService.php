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
        
        // Separar atributos especiais dos atributos HTML padrão
        $specialAttrs = ['id', 'name', 'label', 'help', 'type', 'class', 'value', 'required', 'placeholder', 'maxlength', 'style'];
        $htmlAttrs = array_diff_key($config, array_flip($specialAttrs));
        
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
        
        // Adicionar atributos arbitrários (data-*, aria-*, etc.)
        foreach ($htmlAttrs as $attr => $value) {
            if ($value !== null && $value !== '') {
                $html .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
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
        $checkboxId = $id . '_ape_checkbox';
        
        $html = '<div class="form-group">';
        $html .= '<label for="' . htmlspecialchars($id) . '">Placa de Veículo <span class="text-danger">*</span></label>';
        $html .= '<div class="input-group">';
        $html .= '<input type="text" class="form-control placa-input" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '"';
        $html .= ' value="' . htmlspecialchars($value) . '" placeholder="ABC-1234"';
        $html .= ' style="text-transform: uppercase;" required>';
        $html .= '<span class="input-group-text">';
        $html .= '<input type="checkbox" class="ape-checkbox" id="' . htmlspecialchars($checkboxId) . '" ' . ($value === 'APE' ? 'checked' : '') . '>';
        $html .= '<label for="' . htmlspecialchars($checkboxId) . '" class="ml-1 mb-0">A pé</label>';
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
     * @param array $config Configurações personalizadas (IDs, seletores)
     * @return string JavaScript para validações
     */
    public static function renderFormScripts($features = [], $config = [])
    {
        // Configurações padrão
        $defaults = [
            'cpf_selector' => '.cpf-input',
            'placa_selector' => '.placa-input',
            'placa_checkbox_selector' => '.ape-checkbox'
        ];
        $config = array_merge($defaults, $config);
        
        $html = '<script>';
        
        // Verificar se jQuery está disponível
        $html .= 'if (typeof $ === "undefined") { console.warn("FormService: jQuery não carregado. Algumas funcionalidades podem não funcionar."); }';
        
        $html .= 'document.addEventListener("DOMContentLoaded", function() {';
        
        // Script para checkbox "A pé" - usando class selectors para maior flexibilidade
        if (in_array('placa', $features)) {
            $html .= '
            document.querySelectorAll("' . $config['placa_checkbox_selector'] . '").forEach(function(checkbox) {
                const placaContainer = checkbox.closest(".form-group");
                const placaInput = placaContainer ? placaContainer.querySelector("' . $config['placa_selector'] . '") : null;
                
                if (!placaInput) return;
                
                let previousValue = "";
                
                checkbox.addEventListener("change", function() {
                    if (this.checked) {
                        previousValue = placaInput.value !== "APE" ? placaInput.value : "";
                        placaInput.value = "APE";
                        placaInput.readOnly = true;
                    } else {
                        placaInput.value = previousValue;
                        placaInput.readOnly = false;
                    }
                });
                
                // Verificar estado inicial
                if (placaInput.value === "APE") {
                    checkbox.checked = true;
                    placaInput.readOnly = true;
                }
            });';
        }
        
        $html .= '});';
        
        // Máscara CPF - usando class selector e verificação de existência
        if (in_array('cpf', $features)) {
            $html .= '
            document.querySelectorAll("' . $config['cpf_selector'] . '").forEach(function(cpfInput) {
                cpfInput.addEventListener("input", function(e) {
                    var value = e.target.value.replace(/\\D/g, "");
                    value = value.replace(/(\\d{3})(\\d)/, "$1.$2");
                    value = value.replace(/(\\d{3})(\\d)/, "$1.$2");
                    value = value.replace(/(\\d{3})(\\d{1,2})$/, "$1-$2");
                    e.target.value = value;
                });
            });';
        }
        
        $html .= '</script>';
        
        return $html;
    }
}