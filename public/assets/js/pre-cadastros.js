/**
 * JavaScript: Pré-Cadastros (Lista)
 * 
 * Funcionalidades:
 * - Listar cadastros com filtros
 * - Renovar cadastros expirados
 * - Excluir cadastros
 * 
 * @version 2.0.0
 * @status DRAFT
 */

const PreCadastros = {
    tipo: 'visitante', // 'visitante' ou 'prestador'
    cadastros: [],
    filtros: {
        status: 'all',
        busca: ''
    },
    
    /**
     * Obter endpoint correto (pluralização correta)
     */
    getEndpointSegment: function() {
        const map = {
            'visitante': 'visitantes',
            'prestador': 'prestadores'
        };
        return map[this.tipo] || this.tipo + 's';
    },
    
    /**
     * Inicializar módulo
     */
    init: function(tipo) {
        this.tipo = tipo;
        this.bindEvents();
        this.loadCadastros();
    },
    
    /**
     * Vincular eventos
     */
    bindEvents: function() {
        const self = this;
        
        // Filtro de status
        $('#filtro-status').on('change', function() {
            self.filtros.status = $(this).val();
            self.loadCadastros();
        });
        
        // Busca
        let searchTimeout;
        $('#filtro-busca').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                self.filtros.busca = $('#filtro-busca').val();
                self.loadCadastros();
            }, 500);
        });
        
        // Limpar filtros
        $('#btn-limpar-filtros').on('click', function() {
            $('#filtro-status').val('all');
            $('#filtro-busca').val('');
            self.filtros = { status: 'all', busca: '' };
            self.loadCadastros();
        });
        
        // Confirmar renovação
        $('#btn-confirmar-renovacao').on('click', function() {
            const id = $(this).data('id');
            self.renovarCadastro(id);
        });
    },
    
    /**
     * Carregar cadastros
     */
    loadCadastros: function() {
        const self = this;
        const endpoint = `/api/pre-cadastros/${this.getEndpointSegment()}/list`;
        
        $.ajax({
            url: endpoint,
            data: {
                status: this.filtros.status,
                search: this.filtros.busca
            },
            success: function(response) {
                if (response.success) {
                    self.cadastros = response.data;
                    self.renderTable();
                    self.loadStats(); // Atualizar estatísticas também
                } else {
                    console.error('Erro ao carregar:', response.message);
                }
            },
            error: function(xhr) {
                console.error('Erro AJAX:', xhr.responseText);
            }
        });
    },
    
    /**
     * Carregar estatísticas (cards do topo)
     */
    loadStats: function() {
        const endpoint = `/api/pre-cadastros/${this.getEndpointSegment()}/stats`;
        
        $.ajax({
            url: endpoint,
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    
                    // Atualizar valores nos cards
                    $('.card.bg-primary h2').text(stats.total || 0);
                    $('.card.bg-success h2').text(stats.validos || 0);
                    $('.card.bg-warning h2').text(stats.expirando || 0);
                    $('.card.bg-danger h2').text(stats.expirados || 0);
                }
            },
            error: function(xhr) {
                console.error('Erro ao carregar estatísticas:', xhr);
            }
        });
    },
    
    /**
     * Renderizar tabela
     */
    renderTable: function() {
        const tbody = $('#tbody-cadastros');
        tbody.empty();
        
        if (this.cadastros.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i><br>
                        Nenhum cadastro encontrado
                    </td>
                </tr>
            `);
            return;
        }
        
        this.cadastros.forEach(cadastro => {
            tbody.append(this.renderRow(cadastro));
        });
    },
    
    /**
     * Renderizar linha da tabela
     */
    renderRow: function(c) {
        const docMasked = this.maskDocument(c.doc_number);
        const statusBadge = this.getStatusBadge(c.status_validade, c.dias_restantes, c.dias_expirado);
        const validadeFormatada = this.formatDate(c.valid_until);
        
        return `
            <tr>
                <td><strong>${c.nome}</strong></td>
                <td>${c.empresa || '<span class="text-muted">-</span>'}</td>
                <td>
                    <span class="badge bg-secondary">${c.doc_type}</span>
                    ${docMasked}
                </td>
                <td>${c.placa_veiculo || '<span class="text-muted">-</span>'}</td>
                <td>${validadeFormatada}</td>
                <td>${statusBadge}</td>
                <td>
                    <span class="badge bg-primary">${c.total_entradas || 0}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        ${this.renderActions(c)}
                    </div>
                </td>
            </tr>
        `;
    },
    
    /**
     * Renderizar ações
     */
    renderActions: function(c) {
        let actions = '';
        
        // Editar
        actions += `
            <a href="/pre-cadastros/${this.getEndpointSegment()}?action=edit&id=${c.id}" 
               class="btn btn-outline-primary" title="Editar">
                <i class="fas fa-edit"></i>
            </a>
        `;
        
        // Renovar (se expirado)
        if (c.status_validade === 'expirado') {
            actions += `
                <button type="button" class="btn btn-outline-warning" 
                        onclick="PreCadastros.showRenovarModal(${c.id}, '${c.nome}')"
                        title="Renovar">
                    <i class="fas fa-redo"></i>
                </button>
            `;
        }
        
        // Excluir
        actions += `
            <button type="button" class="btn btn-outline-danger" 
                    onclick="PreCadastros.confirmarExclusao(${c.id}, '${c.nome}')"
                    title="Excluir">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        return actions;
    },
    
    /**
     * Badge de status
     */
    getStatusBadge: function(status, diasRestantes, diasExpirado) {
        switch (status) {
            case 'valido':
                return `<span class="badge bg-success">✅ Válido</span>`;
            case 'expirando':
                return `<span class="badge bg-warning">⚠️ Expira em ${diasRestantes}d</span>`;
            case 'expirado':
                return `<span class="badge bg-danger">❌ Expirado há ${diasExpirado}d</span>`;
            default:
                return `<span class="badge bg-secondary">${status}</span>`;
        }
    },
    
    /**
     * Mascarar documento (LGPD)
     */
    maskDocument: function(doc) {
        if (!doc) return '';
        const len = doc.length;
        if (len <= 4) return doc;
        return '*'.repeat(len - 4) + doc.slice(-4);
    },
    
    /**
     * Formatar data
     */
    formatDate: function(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    },
    
    /**
     * Modal de renovação
     */
    showRenovarModal: function(id, nome) {
        const novaValidade = new Date();
        novaValidade.setFullYear(novaValidade.getFullYear() + 1);
        const formatted = novaValidade.toLocaleDateString('pt-BR');
        
        $('#nova-validade').text(formatted);
        $('#btn-confirmar-renovacao').data('id', id);
        
        // Usar jQuery/Bootstrap 4
        $('#modal-renovar').modal('show');
    },
    
    /**
     * Renovar cadastro
     */
    renovarCadastro: function(id) {
        const self = this;
        
        console.log('🔄 Renovando cadastro ID:', id);
        
        $.ajax({
            url: '/api/pre-cadastros/renovar',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id: id,
                tipo: this.tipo
            }),
            success: function(response) {
                console.log('✅ Resposta do servidor:', response);
                
                // Fechar modal usando jQuery/Bootstrap 4
                $('#modal-renovar').modal('hide');
                
                // Mostrar mensagem
                if (response.success) {
                    alert(response.message);
                    self.loadCadastros(); // Recarregar lista
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('❌ Erro AJAX:', xhr);
                
                // Fechar modal mesmo em caso de erro
                $('#modal-renovar').modal('hide');
                
                alert('Erro ao renovar cadastro');
                console.error(xhr.responseText);
            }
        });
    },
    
    /**
     * Confirmar exclusão
     */
    confirmarExclusao: function(id, nome) {
        console.log('🗑️ Tentando excluir:', id, nome);
        console.log('🔍 Endpoint segment:', this.getEndpointSegment());
        
        const confirmacao = confirm(`Deseja realmente excluir o cadastro de ${nome}?\n\nEsta ação não pode ser desfeita.`);
        console.log('✅ Usuário confirmou?', confirmacao);
        
        if (confirmacao) {
            const url = `/pre-cadastros/${this.getEndpointSegment()}?action=delete&id=${id}`;
            console.log('🚀 Redirecionando para:', url);
            window.location.href = url;
        }
    }
};
