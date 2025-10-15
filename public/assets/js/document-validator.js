// ================================================
// JAVASCRIPT: VALIDAÇÃO DE DOCUMENTOS
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para assets/js/ sem aprovação!

const DocumentValidator = (function() {
    'use strict';

    // Máscaras por tipo de documento
    const masks = {
        'CPF': '000.000.000-00',
        'RG': '00.000.000-0',
        'CNH': '00000000000',
        'PASSAPORTE': null, // Alfanumérico sem máscara
        'RNE': null,
        'DNI': null,
        'CI': null,
        'OUTRO': null
    };

    /**
     * Validar documento via API
     */
    async function validate(docType, docNumber, docCountry = 'BR') {
        if (!docType || !docNumber) {
            return {
                isValid: false,
                message: 'Tipo e número de documento são obrigatórios',
                normalized: ''
            };
        }

        try {
            const response = await fetch('/api/documentos/validar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    doc_type: docType,
                    doc_number: docNumber,
                    doc_country: docCountry
                })
            });

            return await response.json();
        } catch (error) {
            console.error('Erro na validação:', error);
            return {
                isValid: false,
                message: 'Erro ao validar documento',
                normalized: ''
            };
        }
    }

    /**
     * Validar CPF localmente (não requer API)
     */
    function validateCPFLocal(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1+$/.test(cpf)) {
            return false;
        }

        // Validar dígitos verificadores
        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    }

    /**
     * Aplicar máscara ao documento
     */
    function applyMask(docType, value) {
        const mask = masks[docType];
        
        if (!mask) {
            // Sem máscara, apenas limpar caracteres especiais
            return value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
        }

        if (docType === 'CPF') {
            return applyCPFMask(value);
        }

        if (docType === 'RG') {
            return applyRGMask(value);
        }

        if (docType === 'CNH') {
            return value.replace(/\D/g, '').substring(0, 11);
        }

        return value;
    }

    /**
     * Aplicar máscara de CPF
     */
    function applyCPFMask(value) {
        value = value.replace(/\D/g, '');
        value = value.substring(0, 11);
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return value;
    }

    /**
     * Aplicar máscara de RG
     */
    function applyRGMask(value) {
        value = value.replace(/[^0-9X]/gi, '').toUpperCase();
        value = value.substring(0, 9);
        value = value.replace(/(\d{2})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1})$/, '$1-$2');
        return value;
    }

    /**
     * Remover máscara/formatação
     */
    function removeMask(docType, value) {
        if (docType === 'CPF' || docType === 'RG' || docType === 'CNH') {
            return value.replace(/\D/g, '');
        }
        return value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
    }

    /**
     * Formatar documento para exibição
     */
    function formatForDisplay(docType, docNumber) {
        return applyMask(docType, docNumber);
    }

    /**
     * Obter ícone do tipo de documento
     */
    function getIcon(docType) {
        const icons = {
            'CPF': 'bi-credit-card',
            'RG': 'bi-person-badge',
            'CNH': 'bi-card-heading',
            'PASSAPORTE': 'bi-airplane',
            'RNE': 'bi-globe',
            'DNI': 'bi-credit-card-2-front',
            'CI': 'bi-person-vcard',
            'OUTRO': 'bi-file-text'
        };
        return icons[docType] || 'bi-file-text';
    }

    /**
     * Obter badge de país
     */
    function getCountryFlag(countryCode) {
        const flags = {
            'BR': '🇧🇷',
            'AR': '🇦🇷',
            'BO': '🇧🇴',
            'CL': '🇨🇱',
            'CO': '🇨🇴',
            'PY': '🇵🇾',
            'PE': '🇵🇪',
            'UY': '🇺🇾',
            'VE': '🇻🇪',
            'US': '🇺🇸',
            'ES': '🇪🇸',
            'PT': '🇵🇹'
        };
        return flags[countryCode] || '🌍';
    }

    /**
     * Inicializar validação automática em formulários
     */
    function initAutoValidation() {
        document.querySelectorAll('.doc-type-select').forEach(select => {
            const container = select.closest('[id^="seletor-documento"]');
            if (!container) return;

            const numberInput = container.querySelector('.doc-number-input');
            const countrySelect = container.querySelector('.doc-country-select');

            if (!numberInput) return;

            // Validação em tempo real
            let timeout;
            numberInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(async () => {
                    const docType = select.value;
                    const docNumber = this.value;
                    const country = countrySelect?.value || 'BR';

                    if (docType && docNumber && docNumber.length >= 3) {
                        const result = await validate(docType, docNumber, country);
                        
                        if (result.isValid) {
                            numberInput.classList.remove('is-invalid');
                            numberInput.classList.add('is-valid');
                            
                            // Aplicar valor normalizado
                            if (result.normalized && result.normalized !== docNumber) {
                                numberInput.value = result.normalized;
                            }
                        } else {
                            numberInput.classList.remove('is-valid');
                            numberInput.classList.add('is-invalid');
                        }
                    }
                }, 500);
            });

            // Aplicar máscara ao digitar
            numberInput.addEventListener('input', function() {
                const docType = select.value;
                if (docType) {
                    const cursorPos = this.selectionStart;
                    const oldValue = this.value;
                    this.value = applyMask(docType, this.value);
                    
                    // Manter posição do cursor
                    if (this.value.length >= cursorPos) {
                        this.setSelectionRange(cursorPos, cursorPos);
                    }
                }
            });
        });
    }

    // Auto-inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoValidation);
    } else {
        initAutoValidation();
    }

    // API pública
    return {
        validate,
        validateCPFLocal,
        applyMask,
        removeMask,
        formatForDisplay,
        getIcon,
        getCountryFlag,
        initAutoValidation
    };

})();
