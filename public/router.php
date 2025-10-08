<?php
/**
 * 🛡️ ROUTER SEGURO PARA PROTEÇÃO DE DADOS BIOMÉTRICOS
 * 
 * Este router intercepta TODAS as requisições, incluindo arquivos estáticos,
 * garantindo que dados biométricos nunca sejam servidos diretamente.
 * 
 * ESSENCIAL para LGPD - Proteção obrigatória de dados sensíveis.
 */

// ========================================
// HELPERS PARA PAINEL DE BRIGADA SEM LOGIN
// ========================================

/**
 * Obtém o IP real do cliente considerando proxies
 */
function getClientIp(): string {
    $headers = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Verifica se um IP está dentro de um range CIDR
 */
function ipInCidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    
    list($subnet, $mask) = explode('/', $cidr);
    $ipBin = ip2long($ip);
    $subnetBin = ip2long($subnet);
    
    if ($ipBin === false || $subnetBin === false) {
        return false;
    }
    
    $maskBin = -1 << (32 - (int)$mask);
    return ($ipBin & $maskBin) === ($subnetBin & $maskBin);
}

/**
 * Verifica se IP está na allowlist
 */
function ipAllowed(string $ip, array $allowlist): bool {
    foreach ($allowlist as $allowed) {
        if (ipInCidr($ip, $allowed)) {
            return true;
        }
    }
    return false;
}

/**
 * Valida autenticação do painel (IP + header)
 */
function requirePanelAuth(): void {
    $allowlist = [
        '10.3.0.0/16',    // Rede interna
        '127.0.0.1/32',   // Localhost
        '::1'             // Localhost IPv6
    ];
    
    $clientIp = getClientIp();
    
    // Dev Mode: Permite 127.0.0.1 sem header se PANEL_BRG_DEV_MODE=1
    $devMode = ($_ENV['PANEL_BRG_DEV_MODE'] ?? '0') === '1';
    if ($devMode && ($clientIp === '127.0.0.1' || $clientIp === '::1')) {
        return; // Bypass em dev mode
    }
    
    // Verificar IP allowlist
    if (!ipAllowed($clientIp, $allowlist)) {
        http_response_code(403);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        die(json_encode([
            'success' => false,
            'message' => 'IP não autorizado',
            'ip' => $clientIp
        ]));
    }
    
    // Verificar header X-Panel-Key
    $expectedKey = $_ENV['PANEL_BRG_KEY'] ?? '';
    $receivedKey = $_SERVER['HTTP_X_PANEL_KEY'] ?? '';
    
    if (empty($expectedKey)) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'message' => 'PANEL_BRG_KEY não configurada no servidor'
        ]));
    }
    
    if (!hash_equals($expectedKey, $receivedKey)) {
        http_response_code(403);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        die(json_encode([
            'success' => false,
            'message' => 'Header X-Panel-Key inválido ou ausente'
        ]));
    }
}

// 🚫 BLOQUEIO CRÍTICO: Impedir acesso direto a uploads
$uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($uri, PHP_URL_PATH) ?? '';

// Verificar se é tentativa de acesso a uploads
if (strpos($path, '/uploads/') !== false) {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode([
        'error' => '🚫 ACESSO NEGADO',
        'message' => 'Dados biométricos protegidos pela LGPD',
        'code' => 'BIOMETRIC_ACCESS_DENIED'
    ]));
}

// Para arquivos estáticos permitidos (CSS, JS, imagens públicas)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$/i', $path)) {
    $filePath = __DIR__ . $path;
    if (file_exists($filePath) && is_file($filePath)) {
        // Servir arquivo estático permitido
        return false;
    }
}

// ========================================
// ROTAS DO PAINEL DE BRIGADA (SEM LOGIN)
// ========================================

// API: GET /api/brigada/presentes
if ($uri === '/api/brigada/presentes') {
    requirePanelAuth();
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../src/controllers/PanelBrigadaController.php';
    $controller = new PanelBrigadaController();
    $controller->presentesApi();
    exit;
}

// VIEW: GET /painel/brigada
if ($uri === '/painel/brigada') {
    requirePanelAuth();
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../src/controllers/PanelBrigadaController.php';
    $controller = new PanelBrigadaController();
    $controller->painel();
    exit;
}

// Todas as outras requisições passam pelo index.php
require_once __DIR__ . '/index.php';