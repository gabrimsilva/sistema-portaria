/**
 * Dashboard - Autocomplete de Placa de Veículo
 * Sistema de busca rápida por placa para Visitantes, Profissionais Renner e Prestadores
 */

(function() {
    'use strict';
    
    // Função para configurar autocomplete de placa
    function setupPlacaAutocomplete(selector, tipo) {
        const $campo = $(selector);
        
        if (!$campo.length) return;
        
        // Determinar endpoint correto
        let endpoint = '';
        switch (tipo) {
            case 'visitante':
                endpoint = '/api/pre-cadastros/visitantes/search-placa';
                break;
            case 'prestador':
                endpoint = '/api/pre-cadastros/prestadores/search-placa';
                break;
            case 'profissional':
                endpoint = '/api/profissionais-renner/search-placa';
                break;
            default:
                console.error('Tipo inválido:', tipo);
                return;
        }
        
        $campo.autocomplete({
            minLength: 2,
            delay: 300,
            source: function(request, response) {
                const placa = request.term.toUpperCase().replace(/[^A-Z0-9]/g, '');
                
                $.ajax({
                    url: endpoint,
                    method: 'GET',
                    data: { placa: placa },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success && result.data) {
                            const items = result.data.map(function(item) {
                                let label = item.placa_veiculo + ' - ' + item.nome;
                                if (item.empresa) {
                                    label += ' (' + item.empresa + ')';
                                }
                                
                                return {
                                    label: label,
                                    value: item.placa_veiculo,
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
                
                // Preencher placa
                $campo.val(data.placa_veiculo);
                
                // Preencher outros campos conforme tipo
                if (tipo === 'visitante' || tipo === 'prestador') {
                    // Preencher campos do pré-cadastro
                    $form.find('input[name="nome"]').val(data.nome || '');
                    $form.find('input[name="empresa"]').val(data.empresa || '');
                    
                    // Documento
                    if (data.doc_type) {
                        $form.find('select[name="doc_type"]').val(data.doc_type).trigger('change');
                    }
                    if (data.doc_number) {
                        $form.find('input[name="doc_number"]').val(data.doc_number);
                    }
                    if (data.doc_country) {
                        $form.find('input[name="doc_country"]').val(data.doc_country);
                    }
                    
                    // Cadastro ID (se disponível)
                    if (data.cadastro_id) {
                        $form.find('input[name="cadastro_id"]').val(data.cadastro_id);
                    }
                    
                    console.log(`✅ Dados preenchidos automaticamente via placa (${tipo}):`, data);
                    
                } else if (tipo === 'profissional') {
                    // Preencher campos do profissional
                    $form.find('input[name="nome"]').val(data.nome || '');
                    $form.find('input[name="setor"]').val(data.setor || '');
                    $form.find('input[name="fre"]').val(data.fre || '');
                    
                    // Armazenar ID do profissional
                    if (data.id) {
                        $form.find('input[name="profissional_id"]').val(data.id);
                    }
                    
                    console.log(`✅ Dados preenchidos automaticamente via placa (profissional):`, data);
                }
                
                // Exibir mensagem de sucesso
                const msg = `<i class="fas fa-check-circle text-success"></i> Dados carregados da placa <strong>${data.placa_veiculo}</strong>`;
                $campo.after(`<small class="form-text text-success placa-success-msg">${msg}</small>`);
                setTimeout(function() {
                    $('.placa-success-msg').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
                
                return false;
            },
            open: function() {
                $(this).autocomplete('widget').css('z-index', 9999);
            }
        }).autocomplete('instance')._renderItem = function(ul, item) {
            return $('<li>')
                .append('<div class="autocomplete-item">' +
                    '<i class="fas fa-car text-primary mr-2"></i>' +
                    '<strong>' + item.data.placa_veiculo + '</strong> - ' +
                    item.data.nome +
                    (item.data.empresa ? ' <small class="text-muted">(' + item.data.empresa + ')</small>' : '') +
                    '</div>')
                .appendTo(ul);
        };
    }
    
    // Inicializar quando o documento estiver pronto
    $(document).ready(function() {
        // Autocomplete de placa para Visitante (Dashboard)
        setupPlacaAutocomplete('#visitante_placa_veiculo', 'visitante');
        
        // Autocomplete de placa para Prestador (Dashboard)
        setupPlacaAutocomplete('#prestador_placa_veiculo', 'prestador');
        
        // Autocomplete de placa para Profissional Renner (Dashboard)
        setupPlacaAutocomplete('#profissional_placa_veiculo', 'profissional');
        
        // Autocomplete de placa para formulários de pré-cadastro
        // (usando ID genérico #placa_veiculo presente nos forms)
        if ($('#placa_veiculo').length) {
            // Detectar tipo pelo URL
            const path = window.location.pathname;
            let tipo = null;
            
            if (path.includes('/pre-cadastros/visitantes')) {
                tipo = 'visitante';
            } else if (path.includes('/pre-cadastros/prestadores')) {
                tipo = 'prestador';
            }
            
            if (tipo) {
                setupPlacaAutocomplete('#placa_veiculo', tipo);
                console.log(`✅ Placa Autocomplete inicializado para pré-cadastro (${tipo})`);
            }
        }
        
        console.log('✅ Dashboard Placa Autocomplete inicializado');
    });
    
})();
