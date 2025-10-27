/**
 * Dashboard - Autocomplete de Pré-Cadastros v2.0.0
 * 
 * Integra os modais do Dashboard com o sistema de Pré-Cadastros,
 * permitindo busca rápida e preenchimento automático de formulários.
 * 
 * Funcionalidades:
 * - Autocomplete para Prestadores e Visitantes
 * - Preenchimento automático de todos os campos
 * - Vinculação ao cadastro_id para contagem de entradas
 * 
 * @version 1.0.0
 */

const DashboardPreCadastrosAutocomplete = {
    
    /**
     * Inicializar autocomplete nos modais
     */
    init: function() {
        this.setupPrestadorAutocomplete();
        this.setupVisitanteAutocomplete();
        
        console.log('✅ Dashboard Pré-Cadastros Autocomplete inicializado');
    },
    
    /**
     * Autocomplete para Prestadores
     */
    setupPrestadorAutocomplete: function() {
        const self = this;
        
        $('#prestador_nome').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/api/pre-cadastros/prestadores/search',
                    data: { q: request.term },
                    success: function(data) {
                        if (data.success && data.data) {
                            response(data.data.map(item => ({
                                label: `${item.nome} - ${item.empresa || 'Sem empresa'} (${item.doc_type}: ${self.maskDoc(item.doc_number)})`,
                                value: item.nome,
                                data: item
                            })));
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                const cadastro = ui.item.data;
                self.fillPrestadorForm(cadastro);
                return false;
            }
        });
    },
    
    /**
     * Autocomplete para Visitantes
     */
    setupVisitanteAutocomplete: function() {
        const self = this;
        
        $('#visitante_nome').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/api/pre-cadastros/visitantes/search',
                    data: { q: request.term },
                    success: function(data) {
                        if (data.success && data.data) {
                            response(data.data.map(item => ({
                                label: `${item.nome} - ${item.empresa || 'Sem empresa'} (${item.doc_type}: ${self.maskDoc(item.doc_number)})`,
                                value: item.nome,
                                data: item
                            })));
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                const cadastro = ui.item.data;
                self.fillVisitanteForm(cadastro);
                return false;
            }
        });
    },
    
    /**
     * Preencher formulário de Prestador
     */
    fillPrestadorForm: function(cadastro) {
        $('#prestador_cadastro_id').val(cadastro.id);
        $('#prestador_nome').val(cadastro.nome);
        $('#prestador_empresa').val(cadastro.empresa || '');
        $('#prestador_doc_type').val(cadastro.doc_type || '');
        $('#prestador_doc_number').val(cadastro.doc_number || '');
        $('#prestador_doc_country').val(cadastro.doc_country || 'Brasil');
        $('#prestador_placa_veiculo').val(cadastro.placa_veiculo || '');
        
        // Exibir foto se disponível
        if (cadastro.foto_url) {
            $('#prestador-foto-preview').attr('src', cadastro.foto_url);
            $('#prestador-photo-display').show();
        } else {
            $('#prestador-photo-display').hide();
        }
        
        $('#formPrestador').prepend(`
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> 
                <strong>Pré-Cadastro encontrado!</strong> 
                Dados preenchidos automaticamente. Válido até: ${this.formatDate(cadastro.valid_until)}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        console.log('✅ Formulário Prestador preenchido com cadastro ID:', cadastro.id);
    },
    
    /**
     * Preencher formulário de Visitante
     */
    fillVisitanteForm: function(cadastro) {
        $('#visitante_cadastro_id').val(cadastro.id);
        $('#visitante_nome').val(cadastro.nome);
        $('#visitante_empresa').val(cadastro.empresa || '');
        $('#visitante_doc_type').val(cadastro.doc_type || '');
        $('#visitante_doc_number').val(cadastro.doc_number || '');
        $('#visitante_doc_country').val(cadastro.doc_country || 'Brasil');
        $('#visitante_placa_veiculo').val(cadastro.placa_veiculo || '');
        
        // Exibir foto se disponível
        if (cadastro.foto_url) {
            $('#visitante-foto-preview').attr('src', cadastro.foto_url);
            $('#visitante-photo-display').show();
        } else {
            $('#visitante-photo-display').hide();
        }
        
        $('#formVisitante').prepend(`
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> 
                <strong>Pré-Cadastro encontrado!</strong> 
                Dados preenchidos automaticamente. Válido até: ${this.formatDate(cadastro.valid_until)}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        console.log('✅ Formulário Visitante preenchido com cadastro ID:', cadastro.id);
    },
    
    /**
     * Mascarar documento (LGPD)
     */
    maskDoc: function(doc) {
        if (!doc) return '';
        const len = doc.length;
        if (len <= 4) return doc;
        return '*'.repeat(Math.max(0, len - 4)) + doc.slice(-4);
    },
    
    /**
     * Formatar data
     */
    formatDate: function(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateStr;
    },
    
    /**
     * Limpar formulário ao fechar modal
     */
    clearForms: function() {
        $('#prestador_cadastro_id').val('');
        $('#visitante_cadastro_id').val('');
        $('.alert-info').remove();
        
        // Esconder fotos
        $('#prestador-photo-display').hide();
        $('#visitante-photo-display').hide();
    }
};

$(document).ready(function() {
    DashboardPreCadastrosAutocomplete.init();
    
    $('#modalPrestador, #modalVisitante').on('hidden.bs.modal', function() {
        DashboardPreCadastrosAutocomplete.clearForms();
    });
});
