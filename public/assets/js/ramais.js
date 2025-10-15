// ================================================
// JAVASCRIPT: CONSULTA DE RAMAIS
// Versão: 2.0.0
// ================================================

// IMPORTANTE: Este é um DRAFT - NÃO copiar para assets/js/ sem aprovação!

(function() {
    'use strict';

    let ramaisCache = [];
    let setoresCache = [];

    // Elementos DOM
    const elements = {
        inputBusca: document.getElementById('inputBuscaRamal'),
        selectSetor: document.getElementById('selectSetorFiltro'),
        selectTipo: document.getElementById('selectTipoFiltro'),
        loading: document.getElementById('loadingRamais'),
        lista: document.getElementById('listaRamais'),
        tbody: document.getElementById('tbodyRamais'),
        nenhumRamal: document.getElementById('nenhumRamalEncontrado'),
        totalRamais: document.getElementById('totalRamais'),
        btnExportar: document.getElementById('btnExportarRamais'),
        btnSalvar: document.getElementById('btnSalvarRamal'),
        btnAtualizar: document.getElementById('btnAtualizarRamal')
    };

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        carregarSetores();
        carregarRamais();
        configurarEventos();
    });

    // Configurar eventos
    function configurarEventos() {
        // Busca em tempo real (debounce)
        let timeoutBusca;
        if (elements.inputBusca) {
            elements.inputBusca.addEventListener('input', function() {
                clearTimeout(timeoutBusca);
                timeoutBusca = setTimeout(() => {
                    filtrarRamais();
                }, 300);
            });
        }

        // Filtros
        if (elements.selectSetor) {
            elements.selectSetor.addEventListener('change', filtrarRamais);
        }
        if (elements.selectTipo) {
            elements.selectTipo.addEventListener('change', filtrarRamais);
        }

        // Exportar
        if (elements.btnExportar) {
            elements.btnExportar.addEventListener('click', exportarRamais);
        }

        // Salvar ramal
        if (elements.btnSalvar) {
            elements.btnSalvar.addEventListener('click', salvarRamal);
        }

        // Atualizar ramal
        if (elements.btnAtualizar) {
            elements.btnAtualizar.addEventListener('click', atualizarRamal);
        }
    }

    // Carregar setores
    async function carregarSetores() {
        try {
            const response = await fetch('/api/ramais/setores');
            const data = await response.json();
            
            if (data.success && data.setores) {
                setoresCache = data.setores;
                renderizarSetores();
            }
        } catch (error) {
            console.error('Erro ao carregar setores:', error);
        }
    }

    // Renderizar setores no filtro
    function renderizarSetores() {
        if (!elements.selectSetor) return;
        
        elements.selectSetor.innerHTML = '<option value="">Todos os setores</option>';
        
        setoresCache.forEach(setor => {
            const option = document.createElement('option');
            option.value = setor;
            option.textContent = setor;
            elements.selectSetor.appendChild(option);
        });
    }

    // Carregar ramais
    async function carregarRamais(termo = '') {
        mostrarLoading(true);
        
        try {
            const params = new URLSearchParams();
            if (termo) params.append('q', termo);
            
            const response = await fetch(`/api/ramais/buscar?${params}`);
            const data = await response.json();
            
            if (data.success) {
                ramaisCache = data.ramais || [];
                renderizarRamais(ramaisCache);
            }
        } catch (error) {
            console.error('Erro ao carregar ramais:', error);
            mostrarErro('Erro ao carregar ramais');
        } finally {
            mostrarLoading(false);
        }
    }

    // Filtrar ramais
    function filtrarRamais() {
        const termo = elements.inputBusca ? elements.inputBusca.value.toLowerCase() : '';
        const setorFiltro = elements.selectSetor ? elements.selectSetor.value : '';
        const tipoFiltro = elements.selectTipo ? elements.selectTipo.value : '';

        let ramaisFiltrados = ramaisCache;

        // Filtro de texto
        if (termo) {
            ramaisFiltrados = ramaisFiltrados.filter(r => 
                r.nome.toLowerCase().includes(termo) ||
                (r.setor && r.setor.toLowerCase().includes(termo)) ||
                (r.ramal && r.ramal.includes(termo))
            );
        }

        // Filtro de setor
        if (setorFiltro) {
            ramaisFiltrados = ramaisFiltrados.filter(r => r.setor === setorFiltro);
        }

        // Filtro de tipo
        if (tipoFiltro === 'brigadista') {
            ramaisFiltrados = ramaisFiltrados.filter(r => r.is_brigadista);
        } else if (tipoFiltro === 'outros') {
            ramaisFiltrados = ramaisFiltrados.filter(r => !r.is_brigadista);
        }

        renderizarRamais(ramaisFiltrados);
    }

    // Renderizar lista de ramais
    function renderizarRamais(ramais) {
        if (!elements.tbody) return;

        elements.tbody.innerHTML = '';

        if (ramais.length === 0) {
            mostrarNenhumRamal(true);
            return;
        }

        mostrarNenhumRamal(false);

        ramais.forEach(ramal => {
            const tr = document.createElement('tr');
            
            const badgeBrigadista = ramal.is_brigadista ? 
                '<span class="badge bg-danger ms-2"><i class="bi bi-fire me-1"></i>Brigadista</span>' : '';
            
            tr.innerHTML = `
                <td>
                    <strong>${escapeHtml(ramal.nome)}</strong>
                    ${badgeBrigadista}
                </td>
                <td>${escapeHtml(ramal.setor || '-')}</td>
                <td class="text-muted">${escapeHtml(ramal.empresa || '-')}</td>
                <td>
                    <span class="badge bg-primary">
                        <i class="bi bi-telephone me-1"></i>${escapeHtml(ramal.ramal)}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-editar" data-id="${ramal.id}" 
                                data-nome="${escapeHtml(ramal.nome)}" data-ramal="${escapeHtml(ramal.ramal)}"
                                title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-remover" data-id="${ramal.id}"
                                data-nome="${escapeHtml(ramal.nome)}" title="Remover">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            // Eventos de ação
            const btnEditar = tr.querySelector('.btn-editar');
            const btnRemover = tr.querySelector('.btn-remover');

            if (btnEditar) {
                btnEditar.addEventListener('click', () => abrirModalEditar(ramal));
            }

            if (btnRemover) {
                btnRemover.addEventListener('click', () => confirmarRemocao(ramal));
            }

            elements.tbody.appendChild(tr);
        });

        if (elements.totalRamais) {
            elements.totalRamais.textContent = ramais.length;
        }
    }

    // Abrir modal de edição
    function abrirModalEditar(ramal) {
        document.getElementById('editProfissionalId').value = ramal.id;
        document.getElementById('editNomeProfissional').value = ramal.nome;
        document.getElementById('editRamal').value = ramal.ramal;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarRamal'));
        modal.show();
    }

    // Salvar ramal
    async function salvarRamal() {
        const profissionalId = document.getElementById('selectProfissional').value;
        const ramal = document.getElementById('inputRamal').value;

        if (!profissionalId || !ramal) {
            mostrarAlerta('Por favor, preencha todos os campos', 'warning');
            return;
        }

        elements.btnSalvar.disabled = true;

        try {
            const response = await fetch('/api/ramais/adicionar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    profissional_id: profissionalId,
                    ramal: ramal
                })
            });

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('Ramal adicionado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalAdicionarRamal')).hide();
                document.getElementById('formAdicionarRamal').reset();
                carregarRamais();
            } else {
                mostrarAlerta(data.message || 'Erro ao adicionar ramal', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao salvar ramal', 'danger');
        } finally {
            elements.btnSalvar.disabled = false;
        }
    }

    // Atualizar ramal
    async function atualizarRamal() {
        const profissionalId = document.getElementById('editProfissionalId').value;
        const ramal = document.getElementById('editRamal').value;

        if (!ramal) {
            mostrarAlerta('Ramal é obrigatório', 'warning');
            return;
        }

        elements.btnAtualizar.disabled = true;

        try {
            const response = await fetch(`/api/ramais/${profissionalId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ ramal: ramal })
            });

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('Ramal atualizado com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarRamal')).hide();
                carregarRamais();
            } else {
                mostrarAlerta(data.message || 'Erro ao atualizar ramal', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao atualizar ramal', 'danger');
        } finally {
            elements.btnAtualizar.disabled = false;
        }
    }

    // Confirmar remoção
    function confirmarRemocao(ramal) {
        if (confirm(`Deseja realmente remover o ramal de ${ramal.nome}?`)) {
            removerRamal(ramal.id);
        }
    }

    // Remover ramal
    async function removerRamal(profissionalId) {
        try {
            const response = await fetch(`/api/ramais/${profissionalId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            const data = await response.json();

            if (data.success) {
                mostrarAlerta('Ramal removido com sucesso!', 'success');
                carregarRamais();
            } else {
                mostrarAlerta(data.message || 'Erro ao remover ramal', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao remover ramal', 'danger');
        }
    }

    // Exportar ramais
    function exportarRamais() {
        window.location.href = '/api/ramais/export';
    }

    // Utilitários
    function mostrarLoading(show) {
        if (elements.loading) elements.loading.classList.toggle('d-none', !show);
        if (elements.lista) elements.lista.classList.toggle('d-none', show);
    }

    function mostrarNenhumRamal(show) {
        if (elements.nenhumRamal) elements.nenhumRamal.classList.toggle('d-none', !show);
        if (elements.lista) elements.lista.classList.toggle('d-none', show);
    }

    function mostrarAlerta(mensagem, tipo = 'info') {
        // Usar sistema de alertas existente ou criar toast
        alert(mensagem);
    }

    function mostrarErro(mensagem) {
        console.error(mensagem);
        mostrarAlerta(mensagem, 'danger');
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
