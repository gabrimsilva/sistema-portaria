// ================================================
// JAVASCRIPT: GESTÃO DE VALIDADE
// Versão: 2.1.0 (ETAPA 6 - Higiene UX)
// Objetivo: Gerenciar renovação, bloqueio e status de cadastros
// ================================================

const GestaoValidade = (function() {
    'use strict';

    // Registrar no CleanupManager
    const cleanup = window.CleanupManager ? CleanupManager.register('gestao-validade') : null;

    /**
     * Renovar cadastro (com fetch cleanup)
     */
    async function renovar(tipo, id, dias = null) {
        // Dias padrão por tipo
        if (!dias) {
            dias = tipo === 'visitante' ? 90 : 30;
        }

        try {
            const response = cleanup ?
                await cleanup.fetch('/api/cadastros/validade/renovar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        dias: dias
                    })
                }) :
                await fetch('/api/cadastros/validade/renovar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        dias: dias
                    })
                });

            if (!response) return false; // Abortado

            const data = await response.json();

            if (data.success) {
                showAlert(`Cadastro renovado por ${dias} dias!`, 'success');
                return true;
            } else {
                showAlert(data.message || 'Erro ao renovar cadastro', 'danger');
                return false;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro ao renovar:', error);
                showAlert('Erro ao renovar cadastro', 'danger');
            }
            return false;
        }
    }

    /**
     * Renovar com modal de confirmação
     */
    function renovarComConfirmacao(tipo, id, nome, diasAtual) {
        const diasPadrao = tipo === 'visitante' ? 90 : 30;
        
        const html = `
            <div class="modal fade" id="modalRenovacao" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Renovar Cadastro
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Nome:</strong> ${escapeHtml(nome)}</p>
                            <p><strong>Tipo:</strong> ${tipo === 'visitante' ? 'Visitante' : 'Prestador de Serviço'}</p>
                            
                            <div class="mb-3">
                                <label class="form-label">Renovar por quantos dias?</label>
                                <select class="form-select" id="selectDiasRenovacao">
                                    <option value="7">7 dias</option>
                                    <option value="15">15 dias</option>
                                    <option value="30" ${diasPadrao === 30 ? 'selected' : ''}>30 dias (1 mês)</option>
                                    <option value="60">60 dias (2 meses)</option>
                                    <option value="90" ${diasPadrao === 90 ? 'selected' : ''}>90 dias (3 meses)</option>
                                    <option value="180">180 dias (6 meses)</option>
                                    <option value="365">365 dias (1 ano)</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Nova data de validade: <strong id="novaDataValidade">-</strong>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="btnConfirmarRenovacao">
                                <i class="bi bi-check-circle me-1"></i>Confirmar Renovação
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior se existir
        const modalAnterior = document.getElementById('modalRenovacao');
        if (modalAnterior) modalAnterior.remove();

        // Adicionar novo modal
        document.body.insertAdjacentHTML('beforeend', html);

        const modal = new bootstrap.Modal(document.getElementById('modalRenovacao'));
        const selectDias = document.getElementById('selectDiasRenovacao');
        const novaDataElement = document.getElementById('novaDataValidade');
        const btnConfirmar = document.getElementById('btnConfirmarRenovacao');

        // Atualizar data ao mudar dias (com cleanup)
        const changeHandler = function() {
            const dias = parseInt(this.value);
            const novaData = new Date();
            novaData.setDate(novaData.getDate() + dias);
            novaDataElement.textContent = novaData.toLocaleDateString('pt-BR');
        };
        
        if (cleanup) {
            cleanup.addEventListener(selectDias, 'change', changeHandler);
        } else {
            selectDias.addEventListener('change', changeHandler);
        }

        // Trigger inicial
        selectDias.dispatchEvent(new Event('change'));

        // Confirmar renovação (com cleanup)
        const clickHandler = async function() {
            const dias = parseInt(selectDias.value);
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Renovando...';

            const sucesso = await renovar(tipo, id, dias);
            
            if (sucesso) {
                modal.hide();
                // Recarregar dados se houver callback
                if (window.recarregarDados) {
                    window.recarregarDados();
                }
            }

            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirmar Renovação';
        };
        
        if (cleanup) {
            cleanup.addEventListener(btnConfirmar, 'click', clickHandler);
        } else {
            btnConfirmar.addEventListener('click', clickHandler);
        }

        modal.show();
    }

    /**
     * Bloquear cadastro (com fetch cleanup)
     */
    async function bloquear(tipo, id, motivo) {
        if (!motivo || motivo.trim().length < 10) {
            showAlert('Motivo do bloqueio deve ter no mínimo 10 caracteres', 'warning');
            return false;
        }

        try {
            const response = cleanup ?
                await cleanup.fetch('/api/cadastros/validade/bloquear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        motivo: motivo
                    })
                }) :
                await fetch('/api/cadastros/validade/bloquear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        motivo: motivo
                    })
                });

            if (!response) return false; // Abortado

            const data = await response.json();

            if (data.success) {
                showAlert('Cadastro bloqueado com sucesso', 'success');
                return true;
            } else {
                showAlert(data.message || 'Erro ao bloquear cadastro', 'danger');
                return false;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro ao bloquear:', error);
                showAlert('Erro ao bloquear cadastro', 'danger');
            }
            return false;
        }
    }

    /**
     * Bloquear com modal de confirmação
     */
    function bloquearComConfirmacao(tipo, id, nome) {
        const html = `
            <div class="modal fade" id="modalBloqueio" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-slash-circle me-2"></i>
                                Bloquear Cadastro
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Atenção!</strong> Esta ação impedirá novos acessos até que o cadastro seja desbloqueado.
                            </div>

                            <p><strong>Nome:</strong> ${escapeHtml(nome)}</p>
                            <p><strong>Tipo:</strong> ${tipo === 'visitante' ? 'Visitante' : 'Prestador de Serviço'}</p>
                            
                            <div class="mb-3">
                                <label class="form-label">Motivo do Bloqueio *</label>
                                <textarea class="form-control" id="textareaMotivoBloquear" rows="3"
                                          placeholder="Descreva o motivo do bloqueio..."
                                          minlength="10" maxlength="200"></textarea>
                                <div class="form-text"><span id="contadorBloqueio">0</span>/200 caracteres</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="btnConfirmarBloqueio">
                                <i class="bi bi-slash-circle me-1"></i>Confirmar Bloqueio
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remover modal anterior
        const modalAnterior = document.getElementById('modalBloqueio');
        if (modalAnterior) modalAnterior.remove();

        document.body.insertAdjacentHTML('beforeend', html);

        const modal = new bootstrap.Modal(document.getElementById('modalBloqueio'));
        const textarea = document.getElementById('textareaMotivoBloquear');
        const contador = document.getElementById('contadorBloqueio');
        const btnConfirmar = document.getElementById('btnConfirmarBloqueio');

        // Contador de caracteres (com cleanup)
        const inputHandler = function() {
            contador.textContent = this.value.length;
            contador.style.color = this.value.length < 10 ? 'red' : 'inherit';
        };
        
        if (cleanup) {
            cleanup.addEventListener(textarea, 'input', inputHandler);
        } else {
            textarea.addEventListener('input', inputHandler);
        }

        // Confirmar bloqueio (com cleanup)
        const clickHandler = async function() {
            const motivo = textarea.value.trim();
            
            if (motivo.length < 10) {
                showAlert('Motivo deve ter no mínimo 10 caracteres', 'warning');
                textarea.focus();
                return;
            }

            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Bloqueando...';

            const sucesso = await bloquear(tipo, id, motivo);
            
            if (sucesso) {
                modal.hide();
                if (window.recarregarDados) {
                    window.recarregarDados();
                }
            }

            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="bi bi-slash-circle me-1"></i>Confirmar Bloqueio';
        };
        
        if (cleanup) {
            cleanup.addEventListener(btnConfirmar, 'click', clickHandler);
        } else {
            btnConfirmar.addEventListener('click', clickHandler);
        }

        modal.show();
    }

    /**
     * Desbloquear cadastro (com fetch cleanup)
     */
    async function desbloquear(tipo, id) {
        if (!confirm('Deseja realmente desbloquear este cadastro?')) {
            return false;
        }

        try {
            const response = cleanup ?
                await cleanup.fetch('/api/cadastros/validade/desbloquear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id
                    })
                }) :
                await fetch('/api/cadastros/validade/desbloquear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id
                    })
                });

            if (!response) return false; // Abortado

            const data = await response.json();

            if (data.success) {
                showAlert('Cadastro desbloqueado com sucesso', 'success');
                return true;
            } else {
                showAlert(data.message || 'Erro ao desbloquear cadastro', 'danger');
                return false;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro ao desbloquear:', error);
                showAlert('Erro ao desbloquear cadastro', 'danger');
            }
            return false;
        }
    }

    /**
     * Renderizar badge de status
     */
    function renderBadgeStatus(status) {
        const badges = {
            'ativo': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Ativo</span>',
            'expirando': '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Expirando</span>',
            'expirado': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Expirado</span>',
            'bloqueado': '<span class="badge bg-dark"><i class="bi bi-slash-circle me-1"></i>Bloqueado</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">-</span>';
    }

    /**
     * Calcular dias restantes
     */
    function calcularDiasRestantes(dataVencimento) {
        if (!dataVencimento) return null;
        
        const hoje = new Date();
        const vencimento = new Date(dataVencimento);
        const diffTime = vencimento - hoje;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays;
    }

    /**
     * Obter classe de cor por dias restantes
     */
    function getCorDiasRestantes(dias) {
        if (dias <= 0) return 'text-danger';
        if (dias <= 7) return 'text-warning';
        return 'text-success';
    }

    // Utilitários
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function showAlert(mensagem, tipo = 'info') {
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

    // Cleanup ao sair
    if (cleanup) {
        console.log('[GestaoValidade] CleanupManager integrado - fetch e listeners gerenciados');
    } else {
        console.warn('[GestaoValidade] CleanupManager não disponível');
    }

    // API pública
    return {
        renovar,
        renovarComConfirmacao,
        bloquear,
        bloquearComConfirmacao,
        desbloquear,
        renderBadgeStatus,
        calcularDiasRestantes,
        getCorDiasRestantes
    };

})();

// Expor globalmente
window.GestaoValidade = GestaoValidade;
