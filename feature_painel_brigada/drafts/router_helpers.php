<?php
/**
 * HELPERS PARA PAINEL DE BRIGADA SEM LOGIN
 * Adicionar no topo de public/router.php (após comentários iniciais)
 */

/**
 * Obtém o IP real do cliente considerando proxies
 * @return string IP do cliente
 */
function getClientIp(): string {
    // Ordem de prioridade de headers (common reverse proxy headers)
    $headers = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            // X-Forwarded-For pode conter múltiplos IPs (client, proxy1, proxy2)
            // Pegar o primeiro (cliente original)
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            
            // Validar formato de IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            
            // Permitir IPs privados também (intranet)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Verifica se um IP está dentro de um range CIDR
 * @param string $ip IP a verificar
 * @param string $cidr Range CIDR (ex: 10.3.0.0/16)
 * @return bool
 */
function ipInCidr(string $ip, string $cidr): bool {
    // IP único (sem CIDR)
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    
    list($subnet, $mask) = explode('/', $cidr);
    
    // Converter IPs para formato binário
    $ipBin = ip2long($ip);
    $subnetBin = ip2long($subnet);
    
    if ($ipBin === false || $subnetBin === false) {
        return false;
    }
    
    // Calcular máscara
    $maskBin = -1 << (32 - (int)$mask);
    
    // Comparar
    return ($ipBin & $maskBin) === ($subnetBin & $maskBin);
}

/**
 * Verifica se IP está na allowlist
 * @param string $ip IP a verificar
 * @param array $allowlist Array de IPs/CIDRs permitidos
 * @return bool
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
 * Bloqueia requisição se não autorizado
 */
function requirePanelAuth(): void {
    // Configuração de IP allowlist (hardcoded por segurança)
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
    
    // Autenticação OK
}
