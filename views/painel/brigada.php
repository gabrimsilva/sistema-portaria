<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brigada de Incêndio - Painel em Tempo Real</title>
    
    <!-- AdminLTE 3 + Bootstrap 4.6 -->
    <link rel="stylesheet" href="/assets/adminlte/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-painel {
            background: rgba(220, 53, 69, 0.1);
            border-bottom: 3px solid #dc3545;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .card-brigadista {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 1.3rem;
        }
        
        .card-brigadista:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
            border-color: #dc3545;
        }
        
        .badge-fire {
            font-size: 1.5rem;
            padding: 10px 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .status-online {
            color: #28a745;
            animation: blink 1.5s infinite;
        }
        
        .status-reconnecting {
            color: #ffc107;
            animation: blink 0.8s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-painel text-center">
            <h1 class="display-4 mb-2">
                <i class="fas fa-fire-extinguisher text-danger"></i>
                BRIGADA DE INCÊNDIO
            </h1>
            <p class="lead mb-0">Brigadistas Presentes na Planta</p>
        </div>
        
        <!-- Grid de Brigadistas -->
        <div id="brigadistasGrid" class="row">
            <!-- Preenchido via JavaScript -->
        </div>
        
        <!-- Empty State (quando não há brigadistas) -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-user-slash"></i>
            <h3>Nenhum brigadista presente no momento</h3>
            <p>Os brigadistas aparecerão aqui quando registrarem entrada na portaria</p>
        </div>
        
        <!-- Status Bar -->
        <div id="statusBar" class="text-center mt-4 pb-3">
            <small class="status-online">
                <i class="fas fa-circle"></i> 
                Online | 0 brigadistas | <span id="lastUpdate">-</span>
            </small>
        </div>
    </div>
    
    <script>
        let failCount = 0;
        const MAX_RETRIES = 5;
        
        /**
         * Atualiza o painel com dados da API
         */
        async function atualizarPainel() {
            try {
                const resp = await fetch('/api/brigada/presentes', {
                    cache: 'no-store',
                    headers: {
                        'X-Panel-Key': '' // VAZIO no front - validação server-side
                    }
                });
                
                if (!resp.ok) {
                    throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
                }
                
                const json = await resp.json();
                
                if (!json.success) {
                    throw new Error(json.message || 'Erro desconhecido');
                }
                
                renderGrid(json.data);
                updateStatusBar(json.data.length, true);
                failCount = 0;
                
            } catch (err) {
                console.error('Erro ao atualizar painel:', err);
                failCount++;
                updateStatusBar(0, false, err.message);
                
                // Se falhar muito, recarregar página
                if (failCount >= MAX_RETRIES) {
                    console.warn('Muitas falhas, recarregando página...');
                    setTimeout(() => location.reload(), 3000);
                }
            }
        }
        
        /**
         * Renderiza grid de brigadistas
         */
        function renderGrid(data) {
            const grid = document.getElementById('brigadistasGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (!data || data.length === 0) {
                grid.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            
            grid.innerHTML = data.map(b => `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card card-brigadista">
                        <div class="card-body">
                            <h4 class="mb-3">
                                <span class="badge badge-danger badge-fire">
                                    <i class="fas fa-fire-extinguisher"></i>
                                </span>
                                ${escapeHtml(b.nome)}
                            </h4>
                            <p class="mb-2">
                                <i class="fas fa-building text-info"></i> 
                                <strong>Setor:</strong> ${escapeHtml(b.setor)}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="far fa-clock"></i> 
                                <small>Desde: ${formatTime(b.desde)}</small>
                            </p>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        /**
         * Atualiza barra de status
         */
        function updateStatusBar(count, online, errorMsg = null) {
            const statusBar = document.getElementById('statusBar');
            const now = new Date().toLocaleTimeString('pt-BR');
            
            if (online) {
                statusBar.innerHTML = `
                    <small class="status-online">
                        <i class="fas fa-circle"></i> 
                        Online | ${count} brigadista${count !== 1 ? 's' : ''} | ${now}
                    </small>
                `;
            } else {
                statusBar.innerHTML = `
                    <small class="status-reconnecting">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Reconectando... (tentativa ${failCount}/${MAX_RETRIES})
                        ${errorMsg ? `<br>${errorMsg}` : ''}
                    </small>
                `;
            }
        }
        
        /**
         * Formata timestamp para HH:MM
         */
        function formatTime(isoString) {
            try {
                const date = new Date(isoString);
                return date.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return isoString;
            }
        }
        
        /**
         * Escape HTML para prevenir XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Recarrega página 1x por dia (às 03:00)
         */
        function checkDailyReload() {
            const now = new Date();
            if (now.getHours() === 3 && now.getMinutes() === 0) {
                console.log('Recarga diária programada');
                location.reload();
            }
        }
        
        // Polling a cada 10 segundos
        setInterval(atualizarPainel, 10000);
        
        // Verificar recarga diária a cada minuto
        setInterval(checkDailyReload, 60000);
        
        // Execução inicial
        atualizarPainel();
    </script>
</body>
</html>
