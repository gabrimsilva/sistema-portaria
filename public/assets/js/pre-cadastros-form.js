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
    const docNumberInput = document.getElementById('doc_number');
    const docCountryDiv = document.getElementById('div-doc-country');
    const docHint = document.getElementById('doc-hint');
    
    // Atualizar máscara ao mudar tipo de documento
    docTypeSelect.addEventListener('change', function() {
        updateDocumentMask(this.value, false); // Limpa ao trocar manualmente
        updateCountryVisibility(this.value);
    });
    
    // Inicializar com CPF (padrão) - mas só se não tiver valor pré-carregado (modo edição)
    const initialDocType = docTypeSelect.value || 'CPF';
    const hasPreloadedValue = docNumberInput.value.trim().length > 0;
    
    updateDocumentMask(initialDocType, hasPreloadedValue);
    updateCountryVisibility(initialDocType);
    
    /**
     * Atualizar máscara conforme tipo de documento
     * @param {string} docType - Tipo do documento
     * @param {boolean} preserveValue - Se true, mantém valor existente (modo edição)
     */
    function updateDocumentMask(docType, preserveValue = false) {
        // Remover máscaras anteriores
        if (docNumberInput._mask) {
            docNumberInput._mask.destroy();
        }
        
        switch (docType) {
            case 'CPF':
                docNumberInput.placeholder = '000.000.000-00';
                docHint.textContent = 'Ex: 123.456.789-00';
                applyMask(docNumberInput, '000.000.000-00');
                break;
                
            case 'RG':
                docNumberInput.placeholder = '00.000.000-0';
                docHint.textContent = 'Ex: 12.345.678-9';
                applyMask(docNumberInput, '00.000.000-0');
                break;
                
            case 'CNH':
                docNumberInput.placeholder = '00000000000';
                docHint.textContent = 'Ex: 12345678900 (11 dígitos)';
                docNumberInput._mask = null; // Sem máscara
                break;
                
            case 'Passaporte':
                docNumberInput.placeholder = 'AB123456';
                docHint.textContent = 'Ex: AB123456 (alfanumérico)';
                docNumberInput._mask = null;
                break;
                
            case 'RNE':
                docNumberInput.placeholder = 'V123456-7';
                docHint.textContent = 'Ex: V123456-7';
                docNumberInput._mask = null;
                break;
                
            case 'DNI':
            case 'CI':
                docNumberInput.placeholder = '12345678';
                docHint.textContent = 'Documento de identificação estrangeiro';
                docNumberInput._mask = null;
                break;
                
            case 'Outros':
                docNumberInput.placeholder = 'Número do documento';
                docHint.textContent = 'Informe o número conforme documento';
                docNumberInput._mask = null;
                break;
        }
        
        // 🔧 CORREÇÃO: Só limpa se não for modo edição
        if (!preserveValue) {
            docNumberInput.value = '';
        }
    }
    
    /**
     * Mostrar/ocultar campo de país
     */
    function updateCountryVisibility(docType) {
        const isBrazilian = ['CPF', 'RG', 'CNH'].includes(docType);
        
        if (isBrazilian) {
            docCountryDiv.style.display = 'none';
            document.getElementById('doc_country').value = 'Brasil';
        } else {
            docCountryDiv.style.display = 'block';
        }
    }
    
    /**
     * Aplicar máscara (simples)
     */
    function applyMask(input, mask) {
        input.addEventListener('input', function(e) {
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
        });
        
        input._mask = { destroy: () => {} };
    }
    
    // ========================================
    // VALIDADE AUTOMÁTICA
    // ========================================
    
    const validFromInput = document.getElementById('valid_from');
    const validUntilInput = document.getElementById('valid_until');
    const btnDefaultValidity = document.getElementById('btn-default-validity');
    
    // Botão "Padrão (+1 ano)"
    btnDefaultValidity.addEventListener('click', function() {
        const fromDate = new Date(validFromInput.value || new Date());
        const untilDate = new Date(fromDate);
        untilDate.setFullYear(untilDate.getFullYear() + 1);
        
        validUntilInput.value = untilDate.toISOString().split('T')[0];
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
