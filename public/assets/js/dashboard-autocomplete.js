/**
 * Dashboard Autocomplete
 * 
 * Integração com sistema de Pré-Cadastros v2.0.0
 * Adiciona autocomplete nos formulários de entrada para buscar
 * visitantes e prestadores pré-cadastrados
 */

(function() {
    'use strict';
    
    // ⚠️ VALIDAÇÃO DE CADASTROS EXPIRADOS
    let selectedCadastroStatus = null;
    let selectedCadastroId = null;
    let selectedCadastroTipo = null;
    
    /**
     * Inicializar autocomplete para Visitantes
     */
    function initVisitanteAutocomplete() {
        const $nomeInput = $('#visitante_nome');
        
        if ($nomeInput.length === 0) {
            return; // Campo não existe nesta página
        }
        
        $nomeInput.autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/api/pre-cadastros/buscar',
                    data: {
                        q: request.term,
                        tipo: 'visitante'
                    },
                    success: function(data) {
                        if (data.success) {
                            response(data.results);
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 3,
            delay: 300,
            select: function(event, ui) {
                const cadastro = ui.item.data;
                
                // ⚠️ ARMAZENAR STATUS PARA VALIDAÇÃO
                selectedCadastroStatus = cadastro.status_validade;
                selectedCadastroId = cadastro.id;
                selectedCadastroTipo = 'visitante';
                
                // Preencher campos automaticamente
                $('#visitante_nome').val(cadastro.nome);
                $('#visitante_empresa').val(cadastro.empresa || '');
                
                // Documento
                if (cadastro.doc_type && cadastro.doc_number) {
                    $('#visitante_doc_type').val(cadastro.doc_type).trigger('change');
                    $('#visitante_doc_number').val(cadastro.doc_number);
                }
                
                // País (se existir o campo)
                if ($('#visitante_doc_country').length && cadastro.doc_country) {
                    $('#visitante_doc_country').val(cadastro.doc_country);
                }
                
                // Placa do veículo
                if (cadastro.placa_veiculo) {
                    $('#visitante_placa_veiculo').val(cadastro.placa_veiculo);
                }
                
                // Mostrar alerta de sucesso
                showPreCadastroAlert(cadastro);
                
                return false; // Prevenir comportamento padrão
            },
            focus: function(event, ui) {
                $('#visitante_nome').val(ui.item.value);
                return false;
            }
        }).data("ui-autocomplete")._renderItem = function(ul, item) {
            const cadastro = item.data;
            const statusBadge = getStatusBadge(cadastro.status_validade);
            const docMasked = maskDocument(cadastro.doc_number);
            
            return $("<li>")
                .append(`
                    <div class="autocomplete-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${cadastro.nome}</strong>
                                ${statusBadge}
                                <br>
                                <small class="text-muted">
                                    ${cadastro.doc_type}: ${docMasked}
                                    ${cadastro.empresa ? ' | ' + cadastro.empresa : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `)
                .appendTo(ul);
        };
        
        // Adicionar ícone de busca
        $nomeInput.after('<small class="form-text text-muted">💡 Digite 3 letras para buscar pré-cadastros</small>');
    }
    
    /**
     * Inicializar autocomplete para Prestadores
     */
    function initPrestadorAutocomplete() {
        const $nomeInput = $('#prestador_nome');
        
        if ($nomeInput.length === 0) {
            return;
        }
        
        $nomeInput.autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/api/pre-cadastros/buscar',
                    data: {
                        q: request.term,
                        tipo: 'prestador'
                    },
                    success: function(data) {
                        if (data.success) {
                            response(data.results);
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 3,
            delay: 300,
            select: function(event, ui) {
                const cadastro = ui.item.data;
                
                // ⚠️ ARMAZENAR STATUS PARA VALIDAÇÃO
                selectedCadastroStatus = cadastro.status_validade;
                selectedCadastroId = cadastro.id;
                selectedCadastroTipo = 'prestador';
                
                // Preencher campos
                $('#prestador_nome').val(cadastro.nome);
                $('#prestador_empresa').val(cadastro.empresa || '');
                
                if (cadastro.doc_type && cadastro.doc_number) {
                    $('#prestador_doc_type').val(cadastro.doc_type).trigger('change');
                    $('#prestador_doc_number').val(cadastro.doc_number);
                }
                
                if ($('#prestador_doc_country').length && cadastro.doc_country) {
                    $('#prestador_doc_country').val(cadastro.doc_country);
                }
                
                if (cadastro.placa_veiculo) {
                    $('#prestador_placa_veiculo').val(cadastro.placa_veiculo);
                }
                
                showPreCadastroAlert(cadastro);
                
                return false;
            },
            focus: function(event, ui) {
                $('#prestador_nome').val(ui.item.value);
                return false;
            }
        }).data("ui-autocomplete")._renderItem = function(ul, item) {
            const cadastro = item.data;
            const statusBadge = getStatusBadge(cadastro.status_validade);
            const docMasked = maskDocument(cadastro.doc_number);
            
            return $("<li>")
                .append(`
                    <div class="autocomplete-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${cadastro.nome}</strong>
                                ${statusBadge}
                                <br>
                                <small class="text-muted">
                                    ${cadastro.doc_type}: ${docMasked}
                                    ${cadastro.empresa ? ' | ' + cadastro.empresa : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `)
                .appendTo(ul);
        };
        
        $nomeInput.after('<small class="form-text text-muted">💡 Digite 3 letras para buscar pré-cadastros</small>');
    }
    
    /**
     * Mostrar alerta informativo quando pré-cadastro for selecionado
     */
    function showPreCadastroAlert(cadastro) {
        const validadeFormatada = new Date(cadastro.valid_until).toLocaleDateString('pt-BR');
        
        // Remover alertas anteriores
        $('.pre-cadastro-alert').remove();
        
        let alertClass = 'alert-success';
        let icon = 'fa-check-circle';
        let message = `Pré-cadastro encontrado! Válido até ${validadeFormatada}`;
        
        if (cadastro.status_validade === 'expirando') {
            alertClass = 'alert-warning';
            icon = 'fa-exclamation-triangle';
            message = `Atenção: Pré-cadastro expira em ${cadastro.dias_restantes} dia(s) - ${validadeFormatada}`;
        } else if (cadastro.status_validade === 'expirado') {
            alertClass = 'alert-danger';
            icon = 'fa-times-circle';
            message = `Pré-cadastro expirado há ${cadastro.dias_expirado} dia(s)! Renove antes de prosseguir.`;
        }
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show pre-cadastro-alert mt-2" role="alert">
                <i class="fas ${icon} me-2"></i>
                <strong>Pré-Cadastro:</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        // Inserir após o campo de nome
        $('#visitante_nome, #prestador_nome').parent().append(alert);
    }
    
    /**
     * Obter badge de status
     */
    function getStatusBadge(status) {
        const badges = {
            'valido': '<span class="badge badge-success ms-2">✓ Válido</span>',
            'expirando': '<span class="badge badge-warning ms-2">⚠ Expirando</span>',
            'expirado': '<span class="badge badge-danger ms-2">✗ Expirado</span>'
        };
        
        return badges[status] || '';
    }
    
    /**
     * Máscara de documento (mostrar apenas últimos 4 dígitos)
     */
    function maskDocument(doc) {
        if (!doc) return '';
        
        if (doc.length <= 4) return doc;
        
        return '•••' + doc.slice(-4);
    }
    
    /**
     * Validar cadastro expirado antes do submit
     */
    function validateCadastroExpiration() {
        // Interceptar submit do formulário de visitante
        $('#btnSalvarVisitante').on('click', function(e) {
            if (selectedCadastroStatus === 'expirado') {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                showExpiredRenewalDialog(selectedCadastroId, 'visitante');
                return false;
            }
        });
        
        // Interceptar submit do formulário de prestador
        $('#btnSalvarPrestador').on('click', function(e) {
            if (selectedCadastroStatus === 'expirado') {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                showExpiredRenewalDialog(selectedCadastroId, 'prestador');
                return false;
            }
        });
    }
    
    /**
     * Mostrar diálogo para editar cadastro expirado
     */
    function showExpiredRenewalDialog(cadastroId, tipo) {
        const nomeLabel = tipo === 'visitante' ? 'Visitante' : 'Prestador';
        const nomeLabelPlural = tipo === 'visitante' ? 'Visitantes' : 'Prestadores';
        
        if (confirm(`⚠️ CADASTRO EXPIRADO!\n\nEste ${nomeLabel.toLowerCase()} possui um pré-cadastro expirado.\n\nPara registrar a entrada, você precisa renovar o cadastro primeiro.\n\n✅ Ir para Pré-Cadastro (editar/renovar)\n❌ Cancelar`)) {
            // Redirecionar para página de edição do pré-cadastro
            const editUrl = `/pre-cadastros/${tipo === 'visitante' ? 'visitantes' : 'prestadores'}?action=edit&id=${cadastroId}`;
            window.location.href = editUrl;
        } else {
            // Limpar seleção
            selectedCadastroStatus = null;
            selectedCadastroId = null;
            selectedCadastroTipo = null;
        }
    }
    
    /**
     * Resetar status quando limpar formulário
     */
    function resetCadastroStatus() {
        $('#visitante_nome, #prestador_nome').on('input', function() {
            // Se usuário digitou manualmente (não selecionou do autocomplete), resetar
            if ($(this).val().length < 3) {
                selectedCadastroStatus = null;
                selectedCadastroId = null;
                selectedCadastroTipo = null;
            }
        });
    }
    
    /**
     * Inicializar tudo quando DOM estiver pronto
     */
    $(document).ready(function() {
        initVisitanteAutocomplete();
        initPrestadorAutocomplete();
        validateCadastroExpiration();
        resetCadastroStatus();
        
        console.log('✅ Dashboard Autocomplete (Pré-Cadastros) inicializado');
    });
    
})();
