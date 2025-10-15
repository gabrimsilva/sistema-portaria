// ================================================
// JAVASCRIPT: WIDGET CADASTROS EXPIRANDO
// Versão: 2.1.0 (ETAPA 6 - Higiene UX)
// ================================================

(function() {
    'use strict';

    // Registrar no CleanupManager
    const cleanup = window.CleanupManager ? CleanupManager.register('widget-expirando') : null;

    const elements = {
        widget: document.getElementById('widgetCadastrosExpirando'),
        loading: document.getElementById('loadingExpirando'),
        tabs: document.getElementById('tabsExpirando'),
        conteudo: document.getElementById('conteudoExpirando'),
        semCadastros: document.getElementById('semCadastrosExpirando'),
        footer: document.getElementById('footerExpirando'),
        listaVisitantes: document.getElementById('listaVisitantesExp'),
        listaPrestadores: document.getElementById('listaPrestadoresExp'),
        badgeVisitantes: document.getElementById('badgeVisitantesExp'),
        badgePrestadores: document.getElementById('badgePrestadoresExp'),
        btnRecarregar: document.getElementById('btnRecarregarExpirando')
    };

    let autoRefreshInterval = null;

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        carregarCadastrosExpirando();
        configurarEventos();
        
        // Auto-refresh a cada 5 minutos (com cleanup automático)
        autoRefreshInterval = cleanup ?
            cleanup.setInterval(carregarCadastrosExpirando, 5 * 60 * 1000) :
            setInterval(carregarCadastrosExpirando, 5 * 60 * 1000);
    });

    // Configurar eventos
    function configurarEventos() {
        if (elements.btnRecarregar) {
            const handler = function() {
                this.classList.add('rotate');
                carregarCadastrosExpirando();
                
                const rotateTimeout = cleanup ?
                    cleanup.setTimeout(() => this.classList.remove('rotate'), 1000) :
                    setTimeout(() => this.classList.remove('rotate'), 1000);
            };
            
            if (cleanup) {
                cleanup.addEventListener(elements.btnRecarregar, 'click', handler);
            } else {
                elements.btnRecarregar.addEventListener('click', handler);
            }
        }
    }

    // Carregar cadastros expirando (com fetch cleanup)
    async function carregarCadastrosExpirando() {
        mostrarLoading(true);

        try {
            const response = cleanup ?
                await cleanup.fetch('/api/cadastros/validade/expirando') :
                await fetch('/api/cadastros/validade/expirando');

            if (!response) return; // Abortado

            const data = await response.json();

            if (data.success) {
                const visitantes = data.expirando.visitantes || [];
                const prestadores = data.expirando.prestadores || [];
                
                renderizarCadastros(visitantes, prestadores);
            } else {
                console.error('Erro ao carregar cadastros expirando:', data.message);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro:', error);
            }
        } finally {
            mostrarLoading(false);
        }
    }

    // Renderizar cadastros
    function renderizarCadastros(visitantes, prestadores) {
        const totalExpirando = visitantes.length + prestadores.length;

        if (totalExpirando === 0) {
            mostrarSemCadastros(true);
            return;
        }

        mostrarSemCadastros(false);

        // Atualizar badges
        if (elements.badgeVisitantes) {
            elements.badgeVisitantes.textContent = visitantes.length;
        }
        if (elements.badgePrestadores) {
            elements.badgePrestadores.textContent = prestadores.length;
        }

        // Renderizar listas
        renderizarLista(visitantes, elements.listaVisitantes, 'visitante');
        renderizarLista(prestadores, elements.listaPrestadores, 'prestador');

        // Mostrar tabs e conteúdo
        if (elements.tabs) elements.tabs.classList.remove('d-none');
        if (elements.conteudo) elements.conteudo.classList.remove('d-none');
        if (elements.footer) elements.footer.classList.remove('d-none');
    }

    // Renderizar lista individual
    function renderizarLista(cadastros, container, tipo) {
        if (!container) return;

        container.innerHTML = '';

        if (cadastros.length === 0) {
            container.innerHTML = `
                <div class="list-group-item text-center text-muted py-3">
                    <i class="bi bi-check-circle me-2"></i>
                    Nenhum ${tipo} expirando
                </div>
            `;
            return;
        }

        cadastros.forEach(cadastro => {
            const item = criarItemCadastro(cadastro, tipo);
            container.appendChild(item);
        });
    }

    // Criar item de cadastro
    function criarItemCadastro(cadastro, tipo) {
        const div = document.createElement('div');
        div.className = 'list-group-item';

        const diasRestantes = calcularDiasRestantes(cadastro.valid_until || cadastro.data_vencimento);
        const classeStatus = diasRestantes <= 3 ? 'dias-critico' : 'dias-alerta';
        
        const icone = tipo === 'visitante' ? 'person' : 'briefcase';
        const doc = cadastro.doc_number || cadastro.cpf || '-';
        
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-${icone} text-muted me-2"></i>
                        <strong>${escapeHtml(cadastro.nome)}</strong>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-building me-1"></i>${escapeHtml(cadastro.empresa)}
                        <span class="mx-2">•</span>
                        <i class="bi bi-credit-card me-1"></i>${escapeHtml(doc)}
                    </div>
                    <div class="small mt-1">
                        <i class="bi bi-calendar-x text-warning me-1"></i>
                        Expira em: <strong>${formatarData(cadastro.valid_until || cadastro.data_vencimento)}</strong>
                    </div>
                </div>
                <div class="text-end ms-3">
                    <div class="dias-restantes ${classeStatus}">
                        ${diasRestantes}
                    </div>
                    <small class="text-muted">${diasRestantes === 1 ? 'dia' : 'dias'}</small>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-success btn-renovar" 
                                data-tipo="${tipo}" 
                                data-id="${cadastro.id}"
                                data-nome="${escapeHtml(cadastro.nome)}"
                                title="Renovar cadastro">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Evento de renovação (com cleanup automático)
        const btnRenovar = div.querySelector('.btn-renovar');
        if (btnRenovar) {
            const handler = function() {
                const dadosRenovacao = {
                    tipo: this.dataset.tipo,
                    id: this.dataset.id,
                    nome: this.dataset.nome
                };
                abrirModalRenovacao(dadosRenovacao);
            };
            
            if (cleanup) {
                cleanup.addEventListener(btnRenovar, 'click', handler);
            } else {
                btnRenovar.addEventListener('click', handler);
            }
        }

        return div;
    }

    // Calcular dias restantes
    function calcularDiasRestantes(dataVencimento) {
        if (!dataVencimento) return 0;
        
        const hoje = new Date();
        const vencimento = new Date(dataVencimento);
        const diffTime = vencimento - hoje;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return Math.max(0, diffDays);
    }

    // Formatar data
    function formatarData(data) {
        if (!data) return '-';
        
        const d = new Date(data);
        return d.toLocaleDateString('pt-BR');
    }

    // Abrir modal de renovação
    function abrirModalRenovacao(dados) {
        if (confirm(`Deseja renovar o cadastro de ${dados.nome}?`)) {
            renovarCadastro(dados.tipo, dados.id);
        }
    }

    // Renovar cadastro (com fetch cleanup)
    async function renovarCadastro(tipo, id) {
        try {
            const response = cleanup ?
                await cleanup.fetch('/api/cadastros/validade/renovar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        dias: tipo === 'visitante' ? 90 : 30
                    })
                }) :
                await fetch('/api/cadastros/validade/renovar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        id: id,
                        dias: tipo === 'visitante' ? 90 : 30
                    })
                });

            if (!response) return; // Abortado

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('Cadastro renovado com sucesso!', 'success');
                carregarCadastrosExpirando(); // Recarregar lista
            } else {
                mostrarAlerta(data.message || 'Erro ao renovar cadastro', 'danger');
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao renovar cadastro', 'danger');
            }
        }
    }

    // Utilitários
    function mostrarLoading(show) {
        if (elements.loading) elements.loading.classList.toggle('d-none', !show);
    }

    function mostrarSemCadastros(show) {
        if (elements.semCadastros) elements.semCadastros.classList.toggle('d-none', !show);
        if (elements.tabs) elements.tabs.classList.toggle('d-none', show);
        if (elements.conteudo) elements.conteudo.classList.toggle('d-none', show);
        if (elements.footer) elements.footer.classList.toggle('d-none', show);
    }

    function mostrarAlerta(mensagem, tipo = 'info') {
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

    // CSS para rotação do botão
    const style = document.createElement('style');
    style.textContent = `
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .rotate {
            animation: rotate 1s linear;
        }
    `;
    document.head.appendChild(style);

    // Cleanup ao sair da página/trocar de aba
    if (cleanup) {
        console.log('[Widget Expirando] CleanupManager integrado - auto-refresh gerenciado');
    } else {
        console.warn('[Widget Expirando] CleanupManager não disponível');
    }

})();
