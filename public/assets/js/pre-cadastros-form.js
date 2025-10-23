/**
 * JavaScript: Pré-Cadastros (Formulário)
 * 
 * Funcionalidades:
 * - Máscara de documentos
 * - Validação condicional
 * - Mostrar/ocultar campo de país
 * - Cálculo automático de validade
 * 
 * @version 2.0.0
 * @status DRAFT
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // MÁSCARAS E VALIDAÇÕES
    // ========================================
    
    const docTypeSelect = document.getElementById('doc_type');
    let docNumberInput = document.getElementById('doc_number');
    const docCountryDiv = document.getElementById('div-doc-country');
    const docHint = document.getElementById('doc-hint');
    
    let currentInputListener = null; // Armazenar listener atual
    
    // Atualizar máscara ao mudar tipo de documento
    docTypeSelect.addEventListener('change', function() {
        updateDocumentMask(this.value, false); // Limpa ao trocar manualmente
        updateCountryVisibility(this.value, false); // Não é modo edição quando troca manualmente
    });
    
    // Inicializar com CPF (padrão) - mas só se não tiver valor pré-carregado (modo edição)
    const initialDocType = docTypeSelect.value || 'CPF';
    const hasPreloadedValue = docNumberInput.value.trim().length > 0;
    const isEditMode = hasPreloadedValue; // Se tem valor pré-carregado = modo edição
    
    updateDocumentMask(initialDocType, hasPreloadedValue);
    updateCountryVisibility(initialDocType, isEditMode);
    
    /**
     * Atualizar máscara conforme tipo de documento
     * @param {string} docType - Tipo do documento
     * @param {boolean} preserveValue - Se true, mantém valor existente (modo edição)
     */
    function updateDocumentMask(docType, preserveValue = false) {
        // Salvar valor atual
        const currentValue = preserveValue ? docNumberInput.value : '';
        
        // Remover listener anterior se existir
        if (currentInputListener) {
            docNumberInput.removeEventListener('input', currentInputListener);
            currentInputListener = null;
        }
        
        // Resetar campo
        docNumberInput.value = '';
        docNumberInput.placeholder = '';
        
        switch (docType) {
            case 'CPF':
                docNumberInput.placeholder = '000.000.000-00';
                docHint.textContent = 'Ex: 123.456.789-00';
                currentInputListener = createMaskListener('000.000.000-00');
                break;
                
            case 'RG':
                docNumberInput.placeholder = '00.000.000-0';
                docHint.textContent = 'Ex: 12.345.678-9';
                currentInputListener = createMaskListener('00.000.000-0');
                break;
                
            case 'CNH':
                docNumberInput.placeholder = '00000000000';
                docHint.textContent = 'Ex: 12345678900 (11 dígitos)';
                currentInputListener = createNumericListener();
                break;
                
            case 'Passaporte':
                docNumberInput.placeholder = 'AB123456';
                docHint.textContent = 'Ex: AB123456 (alfanumérico)';
                currentInputListener = createAlphanumericListener(false);
                break;
                
            case 'RNE':
                docNumberInput.placeholder = 'V123456-7';
                docHint.textContent = 'Ex: V123456-7';
                currentInputListener = createAlphanumericListener(true); // Permite hífen
                break;
                
            case 'DNI':
            case 'CI':
                docNumberInput.placeholder = '12345678';
                docHint.textContent = 'Documento de identificação estrangeiro';
                currentInputListener = createAlphanumericListener(false);
                break;
                
            case 'Outros':
                docNumberInput.placeholder = 'Número do documento';
                docHint.textContent = 'Informe o número conforme documento';
                currentInputListener = createAlphanumericListener(true); // Permite caracteres especiais
                break;
        }
        
        // Aplicar listener
        if (currentInputListener) {
            docNumberInput.addEventListener('input', currentInputListener);
        }
        
        // Restaurar valor se necessário
        if (currentValue) {
            docNumberInput.value = currentValue;
        }
    }
    
    /**
     * Mostrar/ocultar campo de país
     * @param {string} docType - Tipo do documento
     * @param {boolean} isEditMode - Se true, está em modo edição (não esconde campos)
     */
    function updateCountryVisibility(docType, isEditMode = false) {
        const isBrazilian = ['CPF', 'RG', 'CNH'].includes(docType);
        const docCountryInput = document.getElementById('doc_country');
        
        if (isBrazilian) {
            // 🔧 MODO EDIÇÃO: Sempre mantém visível
            if (isEditMode) {
                docCountryDiv.style.display = 'block';
                return;
            }
            
            // MODO NOVO CADASTRO: Esconde e define Brasil
            docCountryDiv.style.display = 'none';
            docCountryInput.value = 'Brasil';
        } else {
            // Documentos estrangeiros: sempre mostra
            docCountryDiv.style.display = 'block';
        }
    }
    
    /**
     * Criar listener de máscara
     */
    function createMaskListener(mask) {
        return function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let result = '';
            let maskIndex = 0;
            
            for (let i = 0; i < value.length && maskIndex < mask.length; i++) {
                while (mask[maskIndex] && mask[maskIndex] !== '0') {
                    result += mask[maskIndex++];
                }
                if (maskIndex < mask.length) {
                    result += value[i];
                    maskIndex++;
                }
            }
            
            e.target.value = result;
        };
    }
    
    /**
     * Criar listener para apenas números
     */
    function createNumericListener() {
        return function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        };
    }
    
    /**
     * Criar listener alfanumérico
     */
    function createAlphanumericListener(allowSpecialChars = false) {
        return function(e) {
            if (allowSpecialChars) {
                // Permite alfanuméricos + hífen
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
            } else {
                // Apenas alfanuméricos
                e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            }
        };
    }
    
    // ========================================
    // VALIDADE AUTOMÁTICA
    // ========================================
    
    const validFromInput = document.getElementById('valid_from');
    const validUntilInput = document.getElementById('valid_until');
    const btnDefaultValidity = document.getElementById('btn-default-validity');
    
    // Botão "Padrão (+1 ano)" - Atualiza AMBOS os campos para HOJE + 1 ANO
    btnDefaultValidity.addEventListener('click', function() {
        const today = new Date();
        const oneYearFromToday = new Date(today);
        oneYearFromToday.setFullYear(oneYearFromToday.getFullYear() + 1);
        
        // Atualizar AMBOS os campos
        validFromInput.value = today.toISOString().split('T')[0];
        validUntilInput.value = oneYearFromToday.toISOString().split('T')[0];
    });
    
    // Atualizar "valid_until" automaticamente ao mudar "valid_from"
    validFromInput.addEventListener('change', function() {
        const fromDate = new Date(this.value);
        const untilDate = new Date(fromDate);
        untilDate.setFullYear(untilDate.getFullYear() + 1);
        
        validUntilInput.value = untilDate.toISOString().split('T')[0];
    });
    
    // ========================================
    // PLACA DE VEÍCULO
    // ========================================
    
    const placaInput = document.getElementById('placa_veiculo');
    
    placaInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
    });
    
    // ========================================
    // VALIDAÇÃO DO FORMULÁRIO
    // ========================================
    
    const form = document.getElementById('form-pre-cadastro');
    
    form.addEventListener('submit', function(e) {
        const validFrom = new Date(validFromInput.value);
        const validUntil = new Date(validUntilInput.value);
        
        // Validar validade
        if (validUntil <= validFrom) {
            e.preventDefault();
            alert('A data de fim deve ser posterior à data de início!');
            validUntilInput.focus();
            return false;
        }
        
        // Validar documento
        const docNumber = docNumberInput.value.trim();
        if (!docNumber) {
            e.preventDefault();
            alert('Número do documento é obrigatório!');
            docNumberInput.focus();
            return false;
        }
        
        return true;
    });
});
