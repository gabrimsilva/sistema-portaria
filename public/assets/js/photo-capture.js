/**
 * 📸 Photo Capture Component
 * 
 * Componente reutilizável para captura de fotos via webcam
 * Usado em Visitantes, Prestadores e outros módulos que necessitam foto
 * 
 * @version 1.0.0
 */

class PhotoCapture {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container ${containerId} não encontrado`);
        }
        
        this.options = {
            width: options.width || 320,
            height: options.height || 240,
            facingMode: options.facingMode || 'user', // 'user' para frontal, 'environment' para traseira
            onCapture: options.onCapture || null,
            onError: options.onError || null
        };
        
        this.stream = null;
        this.videoElement = null;
        this.canvasElement = null;
        this.capturedImage = null;
        
        this.init();
    }
    
    init() {
        this.createElements();
        this.attachEvents();
    }
    
    createElements() {
        // Criar estrutura HTML
        this.container.innerHTML = `
            <div class="photo-capture-wrapper">
                <div class="photo-preview-area">
                    <video id="photo-video-${this.getUniqueId()}" autoplay playsinline style="display: none;"></video>
                    <canvas id="photo-canvas-${this.getUniqueId()}" style="display: none;"></canvas>
                    <img id="photo-preview-${this.getUniqueId()}" class="captured-photo" style="display: none; max-width: 100%;" />
                    <div class="no-photo-placeholder text-center py-4" id="no-photo-placeholder-${this.getUniqueId()}">
                        <i class="fas fa-camera fa-3x text-muted mb-2"></i>
                        <p class="text-muted">Nenhuma foto capturada</p>
                    </div>
                </div>
                <div class="photo-controls mt-3">
                    <button type="button" class="btn btn-primary btn-sm" id="start-camera-${this.getUniqueId()}">
                        <i class="fas fa-camera"></i> Iniciar Câmera
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="capture-photo-${this.getUniqueId()}" style="display: none;">
                        <i class="fas fa-camera"></i> Capturar
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="retake-photo-${this.getUniqueId()}" style="display: none;">
                        <i class="fas fa-redo"></i> Refazer
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="stop-camera-${this.getUniqueId()}" style="display: none;">
                        <i class="fas fa-stop"></i> Parar Câmera
                    </button>
                </div>
            </div>
        `;
        
        // Obter referências dos elementos
        this.videoElement = this.container.querySelector(`#photo-video-${this.getUniqueId()}`);
        this.canvasElement = this.container.querySelector(`#photo-canvas-${this.getUniqueId()}`);
        this.previewElement = this.container.querySelector(`#photo-preview-${this.getUniqueId()}`);
        this.placeholderElement = this.container.querySelector(`#no-photo-placeholder-${this.getUniqueId()}`);
        
        this.startBtn = this.container.querySelector(`#start-camera-${this.getUniqueId()}`);
        this.captureBtn = this.container.querySelector(`#capture-photo-${this.getUniqueId()}`);
        this.retakeBtn = this.container.querySelector(`#retake-photo-${this.getUniqueId()}`);
        this.stopBtn = this.container.querySelector(`#stop-camera-${this.getUniqueId()}`);
    }
    
    getUniqueId() {
        if (!this._uniqueId) {
            this._uniqueId = Math.random().toString(36).substr(2, 9);
        }
        return this._uniqueId;
    }
    
    attachEvents() {
        this.startBtn.addEventListener('click', () => this.startCamera());
        this.captureBtn.addEventListener('click', () => this.capturePhoto());
        this.retakeBtn.addEventListener('click', () => this.retakePhoto());
        this.stopBtn.addEventListener('click', () => this.stopCamera());
    }
    
    async startCamera() {
        try {
            const constraints = {
                video: {
                    width: { ideal: this.options.width },
                    height: { ideal: this.options.height },
                    facingMode: this.options.facingMode
                }
            };
            
            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.videoElement.srcObject = this.stream;
            this.videoElement.style.display = 'block';
            this.placeholderElement.style.display = 'none';
            
            this.startBtn.style.display = 'none';
            this.captureBtn.style.display = 'inline-block';
            this.stopBtn.style.display = 'inline-block';
            
        } catch (error) {
            console.error('Erro ao acessar câmera:', error);
            const message = 'Não foi possível acessar a câmera. Verifique as permissões do navegador.';
            alert(message);
            if (this.options.onError) {
                this.options.onError(error);
            }
        }
    }
    
    capturePhoto() {
        if (!this.stream) {
            alert('Câmera não está ativa');
            return;
        }
        
        // Configurar canvas com dimensões do vídeo
        this.canvasElement.width = this.videoElement.videoWidth;
        this.canvasElement.height = this.videoElement.videoHeight;
        
        // Desenhar frame do vídeo no canvas
        const context = this.canvasElement.getContext('2d');
        context.drawImage(this.videoElement, 0, 0);
        
        // Converter para imagem
        this.capturedImage = this.canvasElement.toDataURL('image/jpeg', 0.9);
        
        // Exibir preview
        this.previewElement.src = this.capturedImage;
        this.previewElement.style.display = 'block';
        this.videoElement.style.display = 'none';
        
        // Atualizar controles
        this.captureBtn.style.display = 'none';
        this.retakeBtn.style.display = 'inline-block';
        
        // Callback
        if (this.options.onCapture) {
            this.options.onCapture(this.capturedImage);
        }
    }
    
    retakePhoto() {
        this.capturedImage = null;
        this.previewElement.style.display = 'none';
        this.videoElement.style.display = 'block';
        
        this.captureBtn.style.display = 'inline-block';
        this.retakeBtn.style.display = 'none';
    }
    
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        this.videoElement.style.display = 'none';
        this.previewElement.style.display = 'none';
        this.placeholderElement.style.display = 'block';
        
        this.startBtn.style.display = 'inline-block';
        this.captureBtn.style.display = 'none';
        this.stopBtn.style.display = 'none';
        this.retakeBtn.style.display = 'none';
    }
    
    getCapturedImage() {
        return this.capturedImage;
    }
    
    setCapturedImage(dataUrl) {
        this.capturedImage = dataUrl;
        if (dataUrl) {
            this.previewElement.src = dataUrl;
            this.previewElement.style.display = 'block';
            this.placeholderElement.style.display = 'none';
        }
    }
    
    reset() {
        this.stopCamera();
        this.capturedImage = null;
        this.previewElement.src = '';
    }
    
    destroy() {
        this.stopCamera();
        this.container.innerHTML = '';
    }
}

// Registrar no escopo global
window.PhotoCapture = PhotoCapture;
