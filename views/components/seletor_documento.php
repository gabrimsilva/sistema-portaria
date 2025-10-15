<?php
// ================================================
// COMPONENT: SELETOR DE TIPO DE DOCUMENTO
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para views/ sem aprovação!
// Este componente substitui o campo de CPF nos formulários

/**
 * Parâmetros:
 * $fieldName: Nome base dos campos (ex: 'visitante', 'prestador')
 * $required: Se campos são obrigatórios (default: true)
 * $defaultDocType: Tipo de documento padrão (default: 'CPF')
 * $defaultDocNumber: Número do documento padrão (default: '')
 * $defaultDocCountry: País do documento padrão (default: 'BR')
 */

$fieldName = $fieldName ?? 'documento';
$required = $required ?? true;
$defaultDocType = $defaultDocType ?? 'CPF';
$defaultDocNumber = $defaultDocNumber ?? '';
$defaultDocCountry = $defaultDocCountry ?? 'BR';
$requiredAttr = $required ? 'required' : '';
?>

<!-- Seletor de Documento Internacional -->
<div class="row" id="seletor-documento-<?= $fieldName ?>">
    <div class="col-md-4">
        <label class="form-label">
            Tipo de Documento <?= $required ? '*' : '' ?>
        </label>
        <select class="form-select doc-type-select" 
                name="<?= $fieldName ?>_doc_type" 
                id="<?= $fieldName ?>_doc_type"
                <?= $requiredAttr ?>>
            <option value="">Selecione...</option>
            <optgroup label="🇧🇷 Documentos Brasileiros">
                <option value="CPF" <?= $defaultDocType === 'CPF' ? 'selected' : '' ?>>CPF</option>
                <option value="RG" <?= $defaultDocType === 'RG' ? 'selected' : '' ?>>RG</option>
                <option value="CNH" <?= $defaultDocType === 'CNH' ? 'selected' : '' ?>>CNH</option>
            </optgroup>
            <optgroup label="🌎 Documentos Internacionais">
                <option value="PASSAPORTE" <?= $defaultDocType === 'PASSAPORTE' ? 'selected' : '' ?>>Passaporte</option>
                <option value="RNE" <?= $defaultDocType === 'RNE' ? 'selected' : '' ?>>RNE (Registro Nacional de Estrangeiro)</option>
                <option value="DNI" <?= $defaultDocType === 'DNI' ? 'selected' : '' ?>>DNI (Argentina/Espanha)</option>
                <option value="CI" <?= $defaultDocType === 'CI' ? 'selected' : '' ?>>CI (Cédula de Identidad)</option>
                <option value="OUTRO" <?= $defaultDocType === 'OUTRO' ? 'selected' : '' ?>>Outro</option>
            </optgroup>
        </select>
        <div class="invalid-feedback">Selecione o tipo de documento</div>
    </div>

    <div class="col-md-5">
        <label class="form-label">
            Número do Documento <?= $required ? '*' : '' ?>
            <i class="bi bi-info-circle text-muted" 
               data-bs-toggle="tooltip" 
               title="O número será validado conforme o tipo selecionado"></i>
        </label>
        <input type="text" 
               class="form-control doc-number-input" 
               name="<?= $fieldName ?>_doc_number" 
               id="<?= $fieldName ?>_doc_number"
               placeholder="Digite o número"
               value="<?= htmlspecialchars($defaultDocNumber) ?>"
               <?= $requiredAttr ?>>
        <div class="invalid-feedback">Número de documento inválido</div>
        <div class="valid-feedback">
            <i class="bi bi-check-circle-fill"></i> Documento válido
        </div>
    </div>

    <div class="col-md-3" id="<?= $fieldName ?>_country_container" style="display: none;">
        <label class="form-label">
            País <?= $required ? '*' : '' ?>
        </label>
        <select class="form-select doc-country-select" 
                name="<?= $fieldName ?>_doc_country" 
                id="<?= $fieldName ?>_doc_country">
            <option value="BR" <?= $defaultDocCountry === 'BR' ? 'selected' : '' ?>>Brasil</option>
            <option value="AR" <?= $defaultDocCountry === 'AR' ? 'selected' : '' ?>>Argentina</option>
            <option value="BO" <?= $defaultDocCountry === 'BO' ? 'selected' : '' ?>>Bolívia</option>
            <option value="CL" <?= $defaultDocCountry === 'CL' ? 'selected' : '' ?>>Chile</option>
            <option value="CO" <?= $defaultDocCountry === 'CO' ? 'selected' : '' ?>>Colômbia</option>
            <option value="PY" <?= $defaultDocCountry === 'PY' ? 'selected' : '' ?>>Paraguai</option>
            <option value="PE" <?= $defaultDocCountry === 'PE' ? 'selected' : '' ?>>Peru</option>
            <option value="UY" <?= $defaultDocCountry === 'UY' ? 'selected' : '' ?>>Uruguai</option>
            <option value="VE" <?= $defaultDocCountry === 'VE' ? 'selected' : '' ?>>Venezuela</option>
            <option value="US" <?= $defaultDocCountry === 'US' ? 'selected' : '' ?>>Estados Unidos</option>
            <option value="ES" <?= $defaultDocCountry === 'ES' ? 'selected' : '' ?>>Espanha</option>
            <option value="PT" <?= $defaultDocCountry === 'PT' ? 'selected' : '' ?>>Portugal</option>
            <option value="OTHER" <?= $defaultDocCountry === 'OTHER' ? 'selected' : '' ?>>Outro</option>
        </select>
    </div>
</div>

<script>
// Inicializar validação de documentos para este seletor
(function() {
    const fieldName = '<?= $fieldName ?>';
    const docTypeSelect = document.getElementById(fieldName + '_doc_type');
    const docNumberInput = document.getElementById(fieldName + '_doc_number');
    const countryContainer = document.getElementById(fieldName + '_country_container');
    const countrySelect = document.getElementById(fieldName + '_doc_country');

    // Mostrar/ocultar campo de país
    if (docTypeSelect) {
        docTypeSelect.addEventListener('change', function() {
            const needsCountry = ['PASSAPORTE', 'DNI', 'CI', 'OUTRO'].includes(this.value);
            if (countryContainer) {
                countryContainer.style.display = needsCountry ? 'block' : 'none';
                if (countrySelect) {
                    countrySelect.required = needsCountry;
                }
            }
            
            // Atualizar placeholder
            updatePlaceholder(this.value);
            
            // Limpar validação anterior
            if (docNumberInput) {
                docNumberInput.classList.remove('is-valid', 'is-invalid');
            }
        });

        // Trigger inicial
        docTypeSelect.dispatchEvent(new Event('change'));
    }

    // Atualizar placeholder conforme tipo
    function updatePlaceholder(docType) {
        if (!docNumberInput) return;
        
        const placeholders = {
            'CPF': 'Ex: 000.000.000-00',
            'RG': 'Ex: 00.000.000-0',
            'CNH': 'Ex: 00000000000',
            'PASSAPORTE': 'Ex: AB123456',
            'RNE': 'Ex: W1234567',
            'DNI': 'Ex: 12345678',
            'CI': 'Ex: 1234567',
            'OUTRO': 'Digite o número'
        };
        
        docNumberInput.placeholder = placeholders[docType] || 'Digite o número';
    }

    // Validação em tempo real (debounce)
    if (docNumberInput && docTypeSelect) {
        let timeout;
        docNumberInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                validateDocument(docTypeSelect.value, this.value, countrySelect?.value || 'BR');
            }, 500);
        });
    }

    // Função de validação (usa DocumentValidator via AJAX)
    async function validateDocument(docType, docNumber, country) {
        if (!docType || !docNumber || docNumber.length < 3) {
            if (docNumberInput) {
                docNumberInput.classList.remove('is-valid', 'is-invalid');
            }
            return;
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
                    doc_country: country
                })
            });

            const data = await response.json();

            if (docNumberInput) {
                if (data.isValid) {
                    docNumberInput.classList.remove('is-invalid');
                    docNumberInput.classList.add('is-valid');
                    // Atualizar com número normalizado
                    if (data.normalized && data.normalized !== docNumber) {
                        docNumberInput.value = data.normalized;
                    }
                } else {
                    docNumberInput.classList.remove('is-valid');
                    docNumberInput.classList.add('is-invalid');
                    const feedback = docNumberInput.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = data.message || 'Documento inválido';
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao validar documento:', error);
        }
    }
})();
</script>
