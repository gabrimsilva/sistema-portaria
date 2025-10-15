// ================================================
// JAVASCRIPT: ENTRADA RETROATIVA
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para assets/js/ sem aprovação!

(function() {
    'use strict';

    let profissionalSelecionado = null;

    const elements = {
        modal: document.getElementById('modalEntradaRetroativa'),
        form: document.getElementById('formEntradaRetroativa'),
        profissionalId: document.getElementById('retroProfissionalId'),
        nomeProfissional: document.getElementById('retroNomeProfissional'),
        setorProfissional: document.getElementById('retroSetorProfissional'),
        docProfissional: document.getElementById('retroDocProfissional'),
        dataEntrada: document.getElementById('retroDataEntrada'),
        horaEntrada: document.getElementById('retroHoraEntrada'),
        motivo: document.getElementById('retroMotivo'),
        confirmacao: document.getElementById('retroConfirmacao'),
        btnRegistrar: document.getElementById('btnRegistrarRetroativa'),
        alertaConflito: document.getElementById('alertaConflito'),
        mensagemConflito: document.getElementById('mensagemConflito'),
        preview: document.getElementById('previewRetroativa'),
        contadorCaracteres: document.getElementById('contadorCaracteres')
    };

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        configurarEventos();
        configurarValidacoes();
    });

    // Configurar eventos
    function configurarEventos() {
        // Contador de caracteres
        if (elements.motivo) {
            elements.motivo.addEventListener('input', function() {
                const length = this.value.length;
                if (elements.contadorCaracteres) {
                    elements.contadorCaracteres.textContent = length;
                    elements.contadorCaracteres.style.color = length < 10 ? 'red' : 'inherit';
                }
                atualizarPreview();
            });
        }

        // Validação de data
        if (elements.dataEntrada) {
            elements.dataEntrada.addEventListener('change', function() {
                validarDataPassado();
                verificarConflitos();
                atualizarPreview();
            });
        }

        // Validação de hora
        if (elements.horaEntrada) {
            elements.horaEntrada.addEventListener('change', function() {
                verificarConflitos();
                atualizarPreview();
            });
        }

        // Botão registrar
        if (elements.btnRegistrar) {
            elements.btnRegistrar.addEventListener('click', registrarEntradaRetroativa);
        }

        // Limpar form ao fechar modal
        if (elements.modal) {
            elements.modal.addEventListener('hidden.bs.modal', limparFormulario);
        }
    }

    // Configurar validações
    function configurarValidacoes() {
        if (elements.dataEntrada) {
            // Definir max como hoje
            const hoje = new Date().toISOString().split('T')[0];
            elements.dataEntrada.max = hoje;
        }
    }

    // Abrir modal (chamado externamente)
    window.abrirModalEntradaRetroativa = function(profissional) {
        profissionalSelecionado = profissional;
        
        if (elements.profissionalId) elements.profissionalId.value = profissional.id;
        if (elements.nomeProfissional) elements.nomeProfissional.innerHTML = `<strong>${escapeHtml(profissional.nome)}</strong>`;
        if (elements.setorProfissional) elements.setorProfissional.textContent = profissional.setor || '-';
        if (elements.docProfissional) elements.docProfissional.textContent = profissional.cpf || profissional.doc_number || '-';

        // Sugerir data de ontem
        const ontem = new Date();
        ontem.setDate(ontem.getDate() - 1);
        if (elements.dataEntrada) {
            elements.dataEntrada.value = ontem.toISOString().split('T')[0];
        }

        // Sugerir hora atual
        if (elements.horaEntrada) {
            const agora = new Date();
            elements.horaEntrada.value = agora.toTimeString().substring(0, 5);
        }

        const modal = new bootstrap.Modal(elements.modal);
        modal.show();
    };

    // Validar se data é no passado
    function validarDataPassado() {
        if (!elements.dataEntrada) return true;

        const dataSelecionada = new Date(elements.dataEntrada.value);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        if (dataSelecionada >= hoje) {
            elements.dataEntrada.setCustomValidity('Data deve ser no passado');
            elements.dataEntrada.classList.add('is-invalid');
            return false;
        } else {
            elements.dataEntrada.setCustomValidity('');
            elements.dataEntrada.classList.remove('is-invalid');
            return true;
        }
    }

    // Verificar conflitos de horário
    async function verificarConflitos() {
        if (!elements.dataEntrada.value || !elements.horaEntrada.value || !profissionalSelecionado) {
            return;
        }

        const dataHora = `${elements.dataEntrada.value} ${elements.horaEntrada.value}:00`;

        try {
            // Aqui você pode fazer uma chamada API para verificar conflitos
            // Por enquanto, apenas validação local
            
            // Verificar se é muito antigo (mais de 30 dias)
            const dataEntrada = new Date(elements.dataEntrada.value);
            const hoje = new Date();
            const diffDias = Math.floor((hoje - dataEntrada) / (1000 * 60 * 60 * 24));

            if (diffDias > 30) {
                mostrarConflito(`A entrada retroativa é de ${diffDias} dias atrás. Certifique-se de que está correto.`);
            } else {
                ocultarConflito();
            }
        } catch (error) {
            console.error('Erro ao verificar conflitos:', error);
        }
    }

    // Mostrar alerta de conflito
    function mostrarConflito(mensagem) {
        if (elements.alertaConflito && elements.mensagemConflito) {
            elements.mensagemConflito.textContent = mensagem;
            elements.alertaConflito.classList.remove('d-none');
        }
    }

    // Ocultar alerta de conflito
    function ocultarConflito() {
        if (elements.alertaConflito) {
            elements.alertaConflito.classList.add('d-none');
        }
    }

    // Atualizar preview
    function atualizarPreview() {
        if (!elements.preview) return;

        if (elements.dataEntrada.value && elements.horaEntrada.value && elements.motivo.value.length >= 10) {
            const dataHora = new Date(`${elements.dataEntrada.value}T${elements.horaEntrada.value}`);
            const dataHoraFormatada = dataHora.toLocaleString('pt-BR');

            document.getElementById('previewNome').textContent = profissionalSelecionado?.nome || '-';
            document.getElementById('previewDataHora').textContent = dataHoraFormatada;
            document.getElementById('previewMotivo').textContent = elements.motivo.value.substring(0, 100) + '...';

            elements.preview.classList.remove('d-none');
        } else {
            elements.preview.classList.add('d-none');
        }
    }

    // Registrar entrada retroativa
    async function registrarEntradaRetroativa() {
        if (!validarFormulario()) {
            return;
        }

        if (!elements.confirmacao.checked) {
            mostrarAlerta('Por favor, confirme as informações antes de continuar', 'warning');
            return;
        }

        elements.btnRegistrar.disabled = true;
        elements.btnRegistrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';

        try {
            const dataHora = `${elements.dataEntrada.value} ${elements.horaEntrada.value}:00`;

            const response = await fetch('/api/profissionais/entrada-retroativa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    profissional_id: elements.profissionalId.value,
                    data_entrada: dataHora,
                    motivo: elements.motivo.value
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('Entrada retroativa registrada com sucesso!', 'success');
                bootstrap.Modal.getInstance(elements.modal).hide();
                
                // Recarregar dados se houver callback
                if (window.recarregarRegistrosAcesso) {
                    window.recarregarRegistrosAcesso();
                }
            } else {
                mostrarAlerta(data.message || 'Erro ao registrar entrada retroativa', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao registrar entrada retroativa', 'danger');
        } finally {
            elements.btnRegistrar.disabled = false;
            elements.btnRegistrar.innerHTML = '<i class="bi bi-clock-history me-1"></i>Registrar Entrada Retroativa';
        }
    }

    // Validar formulário
    function validarFormulario() {
        if (!elements.form.checkValidity()) {
            elements.form.classList.add('was-validated');
            return false;
        }

        if (!validarDataPassado()) {
            return false;
        }

        if (elements.motivo.value.length < 10) {
            mostrarAlerta('O motivo deve ter no mínimo 10 caracteres', 'warning');
            elements.motivo.focus();
            return false;
        }

        return true;
    }

    // Limpar formulário
    function limparFormulario() {
        if (elements.form) {
            elements.form.reset();
            elements.form.classList.remove('was-validated');
        }
        if (elements.preview) {
            elements.preview.classList.add('d-none');
        }
        ocultarConflito();
        profissionalSelecionado = null;
    }

    // Utilitários
    function mostrarAlerta(mensagem, tipo = 'info') {
        // Usar sistema de alertas existente
        if (window.showAlert) {
            window.showAlert(mensagem, tipo);
        } else {
            alert(mensagem);
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
    }

})();
