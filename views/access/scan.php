<?php
/**
 * 🎯 SCANNER QR - REFATORADO COM LAYOUTSERVICE
 * 
 * Página refatorada para usar o sistema unificado de layout,
 * eliminando duplicação de código de navegação.
 */

// Configuração da página
$pageConfig = [
    'title' => 'Scanner QR - Sistema de Controle de Acesso',
    'context' => 'access',
    'pageTitle' => 'Scanner QR',
    'breadcrumbs' => [
        ['name' => 'Início', 'url' => '/dashboard'],
        ['name' => 'Controle de Acesso', 'url' => '/access'],
        ['name' => 'Scanner', 'active' => true]
    ],
    'activeMenu' => 'access',
    'additionalCSS' => ['/assets/css/scanner.css']
];

// Renderizar layout start
$layout = LayoutService::renderPageSkeleton($pageConfig);
echo $layout['start'];
?>

<style>
#video {
    width: 100%;
    max-width: 500px;
    height: auto;
    border: 3px solid #007bff;
    border-radius: 10px;
}
.scanner-container {
    position: relative;
    display: inline-block;
}
.scanner-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    height: 200px;
    border: 2px solid #ff0000;
    border-radius: 10px;
    pointer-events: none;
}
.result-card {
    display: none;
}
</style>

<!-- 🎯 CONTEÚDO ESPECÍFICO DA PÁGINA -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-qrcode"></i> Scanner QR Code
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                    <br>
                    <button id="startScan" class="btn btn-primary">
                        <i class="fas fa-play"></i> Iniciar Scanner
                    </button>
                    <button id="stopScan" class="btn btn-danger" style="display: none;">
                        <i class="fas fa-stop"></i> Parar Scanner
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card result-card" id="resultCard">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-check"></i> Resultado
                    </h3>
                </div>
                <div class="card-body" id="resultContent">
                    <!-- Resultado do scan aparecerá aqui -->
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Instruções
                    </h3>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Clique em "Iniciar Scanner"</li>
                        <li>Permita acesso à câmera quando solicitado</li>
                        <li>Posicione o QR Code dentro do quadrado vermelho</li>
                        <li>O sistema identificará automaticamente a pessoa</li>
                        <li>Confirme o registro de entrada/saída</li>
                    </ol>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Dica:</strong> Certifique-se de que há boa iluminação para melhor leitura do QR Code.
                    </div>
                </div>
            </div>
        </div>
<?php
// Renderizar layout end
echo $layout['end'];
?>

<script src="https://unpkg.com/html5-qrcode@2.3.4/html5-qrcode.min.js"></script>
<script>
let html5QrcodeScanner;
let scanning = false;

document.addEventListener('DOMContentLoaded', function() {
    const startBtn = document.getElementById('startScan');
    const stopBtn = document.getElementById('stopScan');
    const resultCard = document.getElementById('resultCard');
    const resultContent = document.getElementById('resultContent');
    
    startBtn.addEventListener('click', startScanning);
    stopBtn.addEventListener('click', stopScanning);
    
    function startScanning() {
        if (scanning) return;
        
        const qrReaderDiv = document.getElementById('qr-reader');
        if (!qrReaderDiv) {
            console.error('Element #qr-reader not found');
            return;
        }
        
        html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader",
            { 
                fps: 10, 
                qrbox: {width: 250, height: 250},
                aspectRatio: 1.0
            },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanError);
        
        scanning = true;
        startBtn.style.display = 'none';
        stopBtn.style.display = 'inline-block';
    }
    
    function stopScanning() {
        if (!scanning) return;
        
        html5QrcodeScanner.clear();
        scanning = false;
        startBtn.style.display = 'inline-block';
        stopBtn.style.display = 'none';
    }
    
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`QR Code detectado: ${decodedText}`);
        
        // Processar resultado do QR Code
        processQRCode(decodedText);
        
        // Parar o scanner após sucesso
        stopScanning();
    }
    
    function onScanError(error) {
        // Ignorar erros comuns durante o scanning
        console.warn(`QR Code scan error: ${error}`);
    }
    
    function processQRCode(qrData) {
        resultCard.style.display = 'block';
        resultContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Processando...</div>';
        
        // Fazer request para processar o QR Code
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch('/access?action=process_qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({qr_data: qrData})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPersonResult(data.person, data.access_type);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showError('Erro ao processar QR Code');
        });
    }
    
    function showPersonResult(person, accessType) {
        const html = `
            <div class="text-center">
                ${person.foto ? `<img src="${person.foto}" class="img-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">` : ''}
                <h5>${person.nome}</h5>
                <p class="text-muted">${person.tipo} - ${person.identificacao}</p>
                <div class="alert alert-${accessType === 'entrada' ? 'success' : 'warning'}">
                    <strong>${accessType === 'entrada' ? 'ENTRADA' : 'SAÍDA'}</strong> registrada com sucesso
                </div>
                <button onclick="resetScanner()" class="btn btn-primary">
                    <i class="fas fa-qrcode"></i> Escanear Novamente
                </button>
            </div>
        `;
        resultContent.innerHTML = html;
    }
    
    function showError(message) {
        const html = `
            <div class="alert alert-danger">
                <strong>Erro:</strong> ${message}
            </div>
            <button onclick="resetScanner()" class="btn btn-primary">
                <i class="fas fa-redo"></i> Tentar Novamente
            </button>
        `;
        resultContent.innerHTML = html;
    }
    
    window.resetScanner = function() {
        resultCard.style.display = 'none';
        startScanning();
    };
});
</script>