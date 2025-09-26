<?php

/**
 * Cookie Service - Gerenciamento de Banner LGPD
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Serviço para incluir banner de cookies LGPD em todas as páginas
 * Versão: 2.1.0
 */
class CookieService
{
    /**
     * Renderiza o banner de cookies LGPD
     * 
     * @param array $options Configurações opcionais
     * @return string HTML do banner e scripts necessários
     */
    public static function renderCookieBanner($options = [])
    {
        // Configurações padrão
        $defaults = [
            'showBanner' => true,
            'autoShow' => true,
            'includeModal' => true,
            'customCSS' => '',
            'environment' => $_ENV['ENVIRONMENT'] ?? 'development'
        ];
        
        $config = array_merge($defaults, $options);
        
        // Se o usuário já deu consentimento, não renderiza
        if (isset($_COOKIE['renner_cookie_consent']) && !empty($_COOKIE['renner_cookie_consent'])) {
            try {
                $consent = json_decode($_COOKIE['renner_cookie_consent'], true);
                if ($consent && isset($consent['hasConsented']) && $consent['hasConsented']) {
                    $config['showBanner'] = false;
                }
            } catch (Exception $e) {
                // Se erro no JSON, mostra banner
                $config['showBanner'] = true;
            }
        }
        
        ob_start();
        
        // Inclui o componente do banner
        if ($config['showBanner'] || $config['includeModal']) {
            include BASE_PATH . '/views/components/cookie-banner.php';
        }
        
        // Adiciona script JavaScript com verificação de arquivo
        $jsFile = PUBLIC_PATH . '/assets/js/cookie-consent.js';
        $version = file_exists($jsFile) ? filemtime($jsFile) : time();
        echo '<script src="/assets/js/cookie-consent.js?v=' . $version . '"></script>';
        
        // CSS personalizado se fornecido
        if (!empty($config['customCSS'])) {
            echo '<style>' . $config['customCSS'] . '</style>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Renderiza apenas o script JS (para páginas que já têm o HTML)
     * 
     * @return string Script tag para o JavaScript
     */
    public static function renderCookieScript()
    {
        $version = file_exists(PUBLIC_PATH . '/assets/js/cookie-consent.js') 
                  ? filemtime(PUBLIC_PATH . '/assets/js/cookie-consent.js') 
                  : time();
        
        return '<script src="/assets/js/cookie-consent.js?v=' . $version . '"></script>';
    }
    
    /**
     * Verifica se o usuário já deu consentimento
     * 
     * @param string $type Tipo de cookie (functional, performance)
     * @return bool True se consentimento foi dado
     */
    public static function hasConsentFor($type = null)
    {
        if (!isset($_COOKIE['renner_cookie_consent'])) {
            return false;
        }
        
        try {
            $consent = json_decode($_COOKIE['renner_cookie_consent'], true);
            
            if (!$consent || !isset($consent['hasConsented']) || !$consent['hasConsented']) {
                return false;
            }
            
            // Se tipo específico não foi fornecido, retorna se tem consentimento geral
            if ($type === null) {
                return true;
            }
            
            // Cookies essenciais sempre são permitidos
            if ($type === 'essential') {
                return true;
            }
            
            // Verifica tipo específico
            return isset($consent[$type]) && $consent[$type] === true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém dados completos de consentimento
     * 
     * @return array|null Dados de consentimento ou null se não existe
     */
    public static function getConsentData()
    {
        if (!isset($_COOKIE['renner_cookie_consent'])) {
            return null;
        }
        
        try {
            return json_decode($_COOKIE['renner_cookie_consent'], true);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Define um cookie se o usuário deu consentimento para o tipo
     * 
     * @param string $name Nome do cookie
     * @param string $value Valor do cookie
     * @param int $expire Tempo de expiração (timestamp)
     * @param string $type Tipo de cookie (functional, performance)
     * @return bool True se cookie foi definido
     */
    public static function setCookieIfConsented($name, $value, $expire, $type = 'functional')
    {
        if (!self::hasConsentFor($type)) {
            return false;
        }
        
        return setcookie($name, $value, $expire, '/', '', 
                        isset($_SERVER['HTTPS']), true);
    }
    
    /**
     * Helper para incluir o banner em qualquer view facilmente
     * 
     * @param array $options Configurações opcionais
     */
    public static function includeBanner($options = [])
    {
        echo self::renderCookieBanner($options);
    }
    
    /**
     * Gera configuração JavaScript para outras partes do sistema
     * 
     * @return string Script com configuração
     */
    public static function generateJSConfig()
    {
        $consent = self::getConsentData();
        $hasConsent = $consent !== null;
        
        $config = [
            'hasConsent' => $hasConsent,
            'functional' => $hasConsent ? ($consent['functional'] ?? false) : false,
            'performance' => $hasConsent ? ($consent['performance'] ?? false) : false,
            'timestamp' => $hasConsent ? ($consent['timestamp'] ?? null) : null
        ];
        
        return '<script>window.COOKIE_CONSENT_STATUS = ' . json_encode($config) . ';</script>';
    }
    
    /**
     * Limpa cookies não consentidos (útil para mudanças de consentimento)
     */
    public static function cleanUnconsentedCookies()
    {
        if (!self::hasConsentFor('functional')) {
            $functionalCookies = ['language_pref', 'theme_mode', 'dashboard_layout'];
            foreach ($functionalCookies as $cookie) {
                if (isset($_COOKIE[$cookie])) {
                    setcookie($cookie, '', time() - 3600, '/');
                    unset($_COOKIE[$cookie]);
                }
            }
        }
        
        if (!self::hasConsentFor('performance')) {
            $performanceCookies = ['analytics_session', 'error_tracking'];
            foreach ($performanceCookies as $cookie) {
                if (isset($_COOKIE[$cookie])) {
                    setcookie($cookie, '', time() - 3600, '/');
                    unset($_COOKIE[$cookie]);
                }
            }
        }
    }
}