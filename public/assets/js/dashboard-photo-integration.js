/**
 * 📸 Dashboard Photo Integration
 * 
 * Integra o componente PhotoCapture com o Dashboard
 * para captura e upload de fotos de Visitantes e Prestadores
 * 
 * @version 1.0.0
 */

// Variáveis globais para os componentes de captura
window.visitantePhotoCapture = null;
window.prestadorPhotoCapture = null;

$(document).ready(function() {
    // Inicializar componentes de captura quando modais são abertos
    $('#modalVisitante').on('shown.bs.modal', function() {
        if (!window.visitantePhotoCapture) {
            try {
                window.visitantePhotoCapture = new PhotoCapture('visitante-photo-capture-container', {
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
        if (!window.prestadorPhotoCapture) {
            try {
                window.prestadorPhotoCapture = new PhotoCapture('prestador-photo-capture-container', {
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
        if (window.visitantePhotoCapture) {
            window.visitantePhotoCapture.reset();
        }
    });
    
    $('#modalPrestador').on('hidden.bs.modal', function() {
        if (window.prestadorPhotoCapture) {
            window.prestadorPhotoCapture.reset();
        }
    });
    
    
    /**
     * Função global helper para fazer upload de foto
     * Pode ser chamada pelo código existente após cadastro bem-sucedido
     * 
     * @param {number} cadastroId - ID do cadastro criado
     * @param {string} tipo - 'visitante' ou 'prestador'
     * @returns {Promise}
     */
    window.uploadPhotoIfNeeded = function(cadastroId, tipo) {
        const photoCapture = tipo === 'visitante' ? window.visitantePhotoCapture : window.prestadorPhotoCapture;
        const photoDataUrl = photoCapture ? photoCapture.getCapturedImage() : null;
        
        if (!photoDataUrl) {
            console.log(`ℹ️ Nenhuma foto capturada para ${tipo}`);
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
    };
    
    console.log('✅ Dashboard Photo Integration inicializado');
});
