/**
 * Dashboard - Autocomplete de Documento
 * Sistema de busca rápida por CPF, RG, Passaporte, etc para Visitantes, Profissionais Renner e Prestadores
 */

(function() {
    'use strict';
    
    // Função para configurar autocomplete de documento
    function setupDocumentoAutocomplete(selector, tipo) {
        const $campo = $(selector);
        
        if (!$campo.length) return;
        
        // Determinar endpoint correto
        let endpoint = '';
        switch (tipo) {
            case 'visitante':
                endpoint = '/api/pre-cadastros/visitantes/search-documento';
                break;
            case 'prestador':
                endpoint = '/api/pre-cadastros/prestadores/search-documento';
                break;
            case 'profissional':
                endpoint = '/api/profissionais-renner/search-documento';
                break;
            default:
                console.error('Tipo inválido:', tipo);
                return;
        }
        
        $campo.autocomplete({
            minLength: 3,
            delay: 300,
            source: function(request, response) {
                // Normalizar documento (remover pontuação)
                const documento = request.term.replace(/[^A-Z0-9]/gi, '');
                
                $.ajax({
                    url: endpoint,
                    method: 'GET',
                    data: { documento: documento },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success && result.data) {
                            const items = result.data.map(function(item) {
                                // Formatar label conforme tipo
                                let docFormatado = item.doc_number || item.cpf || '';
                                let docTipo = item.doc_type || 'CPF';
                                
                                let label = docFormatado + ' - ' + item.nome;
                                if (item.empresa) {
                                    label += ' (' + item.empresa + ')';
                                }
                                
                                return {
                                    label: label,
                                    value: docFormatado,
                                    data: item
                                };
                            });
                            response(items);
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            select: function(event, ui) {
                event.preventDefault();
                
                const data = ui.item.data;
                const $form = $campo.closest('form, .card, .modal-body');
                
                // Preencher documento
                $campo.val(data.doc_number || data.cpf || '');
                
                // Preencher outros campos conforme tipo
                if (tipo === 'visitante' || tipo === 'prestador') {
                    // Preencher campos do pré-cadastro
                    $form.find('input[name="nome"]').val(data.nome || '');
                    $form.find('input[name="empresa"]').val(data.empresa || '');
                    
                    // Tipo de documento
                    if (data.doc_type) {
                        $form.find('select[name="doc_type"]').val(data.doc_type).trigger('change');
                    }
                    
                    // País
                    if (data.doc_country) {
                        $form.find('input[name="doc_country"]').val(data.doc_country);
                    }
                    
                    // Placa
                    if (data.placa_veiculo) {
                        $form.find('input[name="placa_veiculo"]').val(data.placa_veiculo);
                    }
                    
                    // Cadastro ID (se disponível)
                    if (data.cadastro_id) {
                        $form.find('input[name="cadastro_id"]').val(data.cadastro_id);
                    }
                    
                    console.log(`✅ Dados preenchidos automaticamente via documento (${tipo}):`, data);
                    
                } else if (tipo === 'profissional') {
                    // Preencher campos do profissional
                    $form.find('input[name="nome"]').val(data.nome || '');
                    $form.find('input[name="setor"]').val(data.setor || '');
                    $form.find('input[name="fre"]').val(data.fre || '');
                    
                    // Placa
                    if (data.placa_veiculo) {
                        $form.find('input[name="placa_veiculo"]').val(data.placa_veiculo);
                    }
                    
                    // Armazenar ID do profissional
                    if (data.id) {
                        $form.find('input[name="profissional_id"]').val(data.id);
                    }
                    
                    console.log(`✅ Dados preenchidos automaticamente via CPF (profissional):`, data);
                }
                
                // Exibir mensagem de sucesso
                const docDisplay = data.doc_number || data.cpf || '';
                const msg = `<i class="fas fa-check-circle text-success"></i> Dados carregados do documento <strong>${docDisplay}</strong>`;
                $campo.after(`<small class="form-text text-success documento-success-msg">${msg}</small>`);
                setTimeout(function() {
                    $('.documento-success-msg').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
                
                return false;
            },
            open: function() {
                $(this).autocomplete('widget').css('z-index', 9999);
            }
        }).autocomplete('instance')._renderItem = function(ul, item) {
            const docTipo = item.data.doc_type || 'CPF';
            const docBadge = `<span class="badge badge-info">${docTipo}</span>`;
            
            return $('<li>')
                .append('<div class="autocomplete-item">' +
                    '<i class="fas fa-id-card text-primary mr-2"></i>' +
                    docBadge + ' ' +
                    '<strong>' + (item.data.doc_number || item.data.cpf) + '</strong> - ' +
                    item.data.nome +
                    (item.data.empresa ? ' <small class="text-muted">(' + item.data.empresa + ')</small>' : '') +
                    '</div>')
                .appendTo(ul);
        };
    }
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        // Autocomplete de documento para Visitante (Dashboard)
        setupDocumentoAutocomplete('#visitante_doc_number', 'visitante');
        
        // Autocomplete de documento para Prestador (Dashboard)
        setupDocumentoAutocomplete('#prestador_doc_number', 'prestador');
        
        // Autocomplete de CPF para Profissional Renner (Dashboard)
        setupDocumentoAutocomplete('#profissional_cpf', 'profissional');
        
        console.log('✅ Dashboard Documento Autocomplete inicializado');
    });
    
})();
