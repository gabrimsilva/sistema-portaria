<?php
/**
 * 🛡️ ROUTER SEGURO PARA PROTEÇÃO DE DADOS BIOMÉTRICOS
 * 
 * Este router intercepta TODAS as requisições, incluindo arquivos estáticos,
 * garantindo que dados biométricos nunca sejam servidos diretamente.
 * 
 * ESSENCIAL para LGPD - Proteção obrigatória de dados sensíveis.
 */

// 🚫 BLOQUEIO CRÍTICO: Impedir acesso direto a uploads
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

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

// Todas as outras requisições passam pelo index.php
require_once __DIR__ . '/index.php';