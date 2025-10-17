/**
 * SNIPPET: Integração Dashboard com Pré-Cadastros (Autocomplete)
 * 
 * Adicionar ao arquivo: views/dashboard/index.php (ou JS separado)
 * 
 * Funcionalidade:
 * - Busca pré-cadastros ao digitar nome
 * - Verifica validade antes de preencher
 * - Oferece renovação se expirado
 * - Preenche formulário automaticamente
 * 
 * @version 2.0.0
 * @status DRAFT
 */

// ========================================
// AUTOCOMPLETE PARA VISITANTES
// ========================================

$('#busca_visitante_pre_cadastro').autocomplete({
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
    minLength: 2,
    select: function(event, ui) {
        const cadastro = ui.item.data;
        
        // Verificar validade
        if (cadastro.status_validade === 'expirado') {
            mostrarModalRenovacaoVisitante(cadastro);
            return false; // Não preencher ainda
        }
        
        if (cadastro.status_validade === 'expirando') {
            const confirma = confirm(
                `⚠️ ATENÇÃO: Este cadastro expira em ${cadastro.dias_restantes} dias.\n\n` +
                `Deseja continuar mesmo assim?`
            );
            if (!confirma) return false;
        }
        
        // Preencher formulário
        preencherFormularioVisitante(cadastro.id);
        
        return false;
    }
}).autocomplete("instance")._renderItem = function(ul, item) {
    // Customizar aparência do autocomplete
    const statusIcon = {
        'valido': '✅',
        'expirando': '⚠️',
        'expirado': '❌'
    }[item.data.status_validade] || '';
    
    return $("<li>")
        .append(`<div>${item.label} ${statusIcon}</div>`)
        .appendTo(ul);
};

// ========================================
// PREENCHER FORMULÁRIO VISITANTE
// ========================================

function preencherFormularioVisitante(cadastroId) {
    $.ajax({
        url: '/api/pre-cadastros/obter-dados',
        data: {
            id: cadastroId,
            tipo: 'visitante'
        },
        success: function(response) {
            if (response.success) {
                const dados = response.data;
                
                // Preencher campos
                $('#nome_visitante').val(dados.nome);
                $('#empresa_visitante').val(dados.empresa);
                $('#doc_type_visitante').val(dados.doc_type);
                $('#doc_number_visitante').val(dados.doc_number);
                $('#doc_country_visitante').val(dados.doc_country);
                $('#placa_visitante').val(dados.placa_veiculo);
                
                // Campo hidden para vincular ao cadastro
                $('#cadastro_id_visitante').val(dados.cadastro_id);
                
                // Atualizar visibilidade do campo país
                if (['CPF', 'RG', 'CNH'].includes(dados.doc_type)) {
                    $('#div_doc_country_visitante').hide();
                } else {
                    $('#div_doc_country_visitante').show();
                }
                
                // Desabilitar campos já preenchidos
                $('#nome_visitante, #doc_type_visitante, #doc_number_visitante').prop('readonly', true);
                
                // Focar no próximo campo (funcionário responsável)
                $('#funcionario_responsavel_visitante').focus();
                
                // Mostrar alerta de sucesso
                mostrarAlerta('success', '✅ Cadastro encontrado! Complete os dados restantes.');
            } else {
                mostrarAlerta('error', 'Erro ao carregar dados do cadastro');
            }
        },
        error: function() {
            mostrarAlerta('error', 'Erro ao buscar dados do pré-cadastro');
        }
    });
}

// ========================================
// MODAL DE RENOVAÇÃO
// ========================================

function mostrarModalRenovacaoVisitante(cadastro) {
    const diasExpirado = cadastro.dias_expirado;
    const novaValidade = new Date();
    novaValidade.setFullYear(novaValidade.getFullYear() + 1);
    
    const html = `
        <div class="modal fade" id="modal-renovar-visitante" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Cadastro Expirado
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            <strong>${cadastro.nome}</strong><br>
                            <small class="text-muted">${cadastro.doc_type} ${cadastro.doc_number}</small>
                        </p>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            Este cadastro expirou há <strong>${diasExpirado} dias</strong>.
                        </div>
                        <p>
                            Deseja renovar este cadastro por mais <strong>1 ano</strong> e 
                            prosseguir com o registro de entrada?
                        </p>
                        <p class="text-muted mb-0">
                            Nova validade: <strong>${novaValidade.toLocaleDateString('pt-BR')}</strong>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-warning" 
                                onclick="renovarEProsseguir(${cadastro.id}, 'visitante')">
                            <i class="fas fa-redo me-2"></i>
                            Renovar e Prosseguir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar modal ao body
    $('body').append(html);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modal-renovar-visitante'));
    modal.show();
    
    // Remover do DOM ao fechar
    $('#modal-renovar-visitante').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

// ========================================
// RENOVAR E PROSSEGUIR
// ========================================

function renovarEProsseguir(cadastroId, tipo) {
    $.ajax({
        url: '/api/pre-cadastros/renovar',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            id: cadastroId,
            tipo: tipo
        }),
        success: function(response) {
            if (response.success) {
                // Fechar modal
                bootstrap.Modal.getInstance(document.getElementById('modal-renovar-' + tipo)).hide();
                
                // Preencher formulário
                preencherFormularioVisitante(cadastroId);
                
                // Mostrar alerta
                mostrarAlerta('success', response.message);
            } else {
                mostrarAlerta('error', 'Erro ao renovar: ' + response.message);
            }
        },
        error: function() {
            mostrarAlerta('error', 'Erro ao renovar cadastro');
        }
    });
}

// ========================================
// HELPER: Mostrar Alerta
// ========================================

function mostrarAlerta(tipo, mensagem) {
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const icon = tipo === 'success' ? 'check-circle' : 'exclamation-circle';
    
    const html = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${icon} me-2"></i>
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertas-dashboard').html(html);
    
    // Auto-fechar após 5 segundos
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

// ========================================
// MESMA LÓGICA PARA PRESTADORES
// ========================================

// (Código idêntico, apenas trocar "visitante" por "prestador")
