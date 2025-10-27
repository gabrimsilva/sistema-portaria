/**
 * 📸 Dashboard Photo Integration
 * 
 * Integra o componente PhotoCapture com o Dashboard
 * para captura e upload de fotos de Visitantes e Prestadores
 * 
 * @version 1.0.0
 */

$(document).ready(function() {
    let visitantePhotoCapture = null;
    let prestadorPhotoCapture = null;
    
    // Inicializar componentes de captura quando modais são abertos
    $('#modalVisitante').on('shown.bs.modal', function() {
        if (!visitantePhotoCapture) {
            try {
                visitantePhotoCapture = new PhotoCapture('visitante-photo-capture-container', {
                    width: 640,
                    height: 480,
                    onCapture: function(dataUrl) {
                        console.log('✅ Foto de visitante capturada');
                    },
                    onError: function(error) {
                        console.error('❌ Erro ao capturar foto:', error);
                    }
                });
                console.log('✅ PhotoCapture inicializado para Visitantes');
            } catch (error) {
                console.error('❌ Erro ao inicializar PhotoCapture:', error);
            }
        }
    });
    
    $('#modalPrestador').on('shown.bs.modal', function() {
        if (!prestadorPhotoCapture) {
            try {
                prestadorPhotoCapture = new PhotoCapture('prestador-photo-capture-container', {
                    width: 640,
                    height: 480,
                    onCapture: function(dataUrl) {
                        console.log('✅ Foto de prestador capturada');
                    },
                    onError: function(error) {
                        console.error('❌ Erro ao capturar foto:', error);
                    }
                });
                console.log('✅ PhotoCapture inicializado para Prestadores');
            } catch (error) {
                console.error('❌ Erro ao inicializar PhotoCapture:', error);
            }
        }
    });
    
    // Resetar componentes quando modais são fechados
    $('#modalVisitante').on('hidden.bs.modal', function() {
        if (visitantePhotoCapture) {
            visitantePhotoCapture.reset();
        }
    });
    
    $('#modalPrestador').on('hidden.bs.modal', function() {
        if (prestadorPhotoCapture) {
            prestadorPhotoCapture.reset();
        }
    });
    
    // Função para fazer upload da foto
    function uploadPhoto(cadastroId, photoDataUrl, tipo) {
        if (!photoDataUrl) {
            return Promise.resolve({ success: true, skipped: true });
        }
        
        return new Promise((resolve, reject) => {
            // Converter data URL para Blob
            fetch(photoDataUrl)
                .then(res => res.blob())
                .then(blob => {
                    const formData = new FormData();
                    formData.append('cadastro_id', cadastroId);
                    formData.append('photo', blob, 'photo.jpg');
                    formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));
                    
                    const endpoint = tipo === 'visitante' ? '/visitantes' : '/prestadores-servico';
                    
                    $.ajax({
                        url: `${endpoint}?action=upload_foto`,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                console.log('✅ Foto enviada com sucesso:', response.foto_url);
                                resolve(response);
                            } else {
                                console.error('❌ Erro ao enviar foto:', response.error);
                                reject(new Error(response.error || 'Erro ao enviar foto'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('❌ Erro na requisição de upload:', error);
                            reject(new Error('Erro ao conectar com o servidor'));
                        }
                    });
                })
                .catch(error => {
                    console.error('❌ Erro ao processar foto:', error);
                    reject(error);
                });
        });
    }
    
    // Modificar submit de visitante para incluir upload de foto
    const originalVisitanteSubmit = $('#btnSalvarVisitante').off('click');
    $('#btnSalvarVisitante').on('click', function(e) {
        e.preventDefault();
        
        const form = $('#formVisitante')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
        
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Primeiro salvar o cadastro/registro
        $.ajax({
            url: '/visitantes?action=save_ajax',
            method: 'POST',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                if (response.success) {
                    const cadastroId = response.cadastro_id;
                    const photo = visitantePhotoCapture ? visitantePhotoCapture.getCapturedImage() : null;
                    
                    // Depois fazer upload da foto (se houver)
                    uploadPhoto(cadastroId, photo, 'visitante')
                        .then(() => {
                            showToast('Visitante cadastrado com sucesso!', 'success');
                            $('#modalVisitante').modal('hide');
                            limparFormulario('formVisitante');
                            setTimeout(() => window.location.reload(), 500);
                        })
                        .catch(photoError => {
                            // Cadastro foi salvo mas foto falhou - avisar usuário
                            console.warn('⚠️ Foto não foi salva:', photoError);
                            showToast('Visitante cadastrado, mas a foto não foi salva', 'warning');
                            $('#modalVisitante').modal('hide');
                            limparFormulario('formVisitante');
                            setTimeout(() => window.location.reload(), 1000);
                        });
                } else {
                    console.log('❌ Erro:', response.message, response.errors);
                    if (Array.isArray(response.errors) && response.errors.length > 0) {
                        response.errors.forEach(err => showToast(err, 'error'));
                    } else {
                        showToast(response.message || 'Erro ao cadastrar visitante', 'error');
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Visitante');
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401) {
                    showToast('Sessão expirada. Faça login novamente.', 'error');
                    setTimeout(() => window.location.href = '/login', 2000);
                } else {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao conectar com o servidor';
                    showToast(errorMsg, 'error');
                }
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Visitante');
            }
        });
    });
    
    // Modificar submit de prestador para incluir upload de foto
    const originalPrestadorSubmit = $('#btnSalvarPrestador').off('click');
    $('#btnSalvarPrestador').on('click', function(e) {
        e.preventDefault();
        
        const form = $('#formPrestador')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
        
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Primeiro salvar o cadastro/registro
        $.ajax({
            url: '/prestadores-servico?action=save_ajax',
            method: 'POST',
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                if (response.success) {
                    const cadastroId = response.cadastro_id;
                    const photo = prestadorPhotoCapture ? prestadorPhotoCapture.getCapturedImage() : null;
                    
                    // Depois fazer upload da foto (se houver)
                    uploadPhoto(cadastroId, photo, 'prestador')
                        .then(() => {
                            showToast('Prestador cadastrado com sucesso!', 'success');
                            $('#modalPrestador').modal('hide');
                            limparFormulario('formPrestador');
                            setTimeout(() => window.location.reload(), 500);
                        })
                        .catch(photoError => {
                            // Cadastro foi salvo mas foto falhou - avisar usuário
                            console.warn('⚠️ Foto não foi salva:', photoError);
                            showToast('Prestador cadastrado, mas a foto não foi salva', 'warning');
                            $('#modalPrestador').modal('hide');
                            limparFormulario('formPrestador');
                            setTimeout(() => window.location.reload(), 1000);
                        });
                } else {
                    console.log('❌ Erro:', response.message, response.errors);
                    if (Array.isArray(response.errors) && response.errors.length > 0) {
                        response.errors.forEach(err => showToast(err, 'error'));
                    } else {
                        showToast(response.message || 'Erro ao cadastrar prestador', 'error');
                    }
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Prestador');
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401) {
                    showToast('Sessão expirada. Faça login novamente.', 'error');
                    setTimeout(() => window.location.href = '/login', 2000);
                } else {
                    const errorMsg = xhr.responseJSON?.message || 'Erro ao conectar com o servidor';
                    showToast(errorMsg, 'error');
                }
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Cadastrar Prestador');
            }
        });
    });
    
    console.log('✅ Dashboard Photo Integration inicializado');
});
