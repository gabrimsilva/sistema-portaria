<?php

/**
 * Layout Service - Sistema Universal de Layout
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Centraliza componentes comuns de layout como LGPD, cookies, footer
 * Versão: 2.3.0
 */
class LayoutService
{
    /**
     * Renderiza componentes de layout universais no final da página
     * Inclui: Banner LGPD, ScriptBlocker, Rodapé LGPD, etc.
     * 
     * @param array $options Configurações opcionais
     * @return string HTML dos componentes de layout
     */
    public static function renderUniversalComponents($options = [])
    {
        $defaults = [
            'includeCookieBanner' => true,
            'includeLgpdFooter' => true,
            'includeScriptBlocker' => true,
            'includeErrorHandler' => true,
            'page' => 'unknown'
        ];
        
        $config = array_merge($defaults, $options);
        
        ob_start();
        
        // 1. Sistema de Cookies LGPD
        if ($config['includeCookieBanner']) {
            try {
                require_once BASE_PATH . '/src/services/CookieService.php';
                echo CookieService::renderCookieBanner(['page' => $config['page']]);
            } catch (Exception $e) {
                error_log("Erro ao renderizar banner de cookies: " . $e->getMessage());
            }
        }
        
        // 2. ScriptBlocker para LGPD
        if ($config['includeScriptBlocker']) {
            $jsFile = PUBLIC_PATH . '/assets/js/script-blocker.js';
            if (file_exists($jsFile)) {
                $version = filemtime($jsFile);
                echo '<script src="/assets/js/script-blocker.js?v=' . $version . '"></script>';
            }
        }
        
        // 3. ErrorHandler global
        if ($config['includeErrorHandler']) {
            $jsFile = PUBLIC_PATH . '/assets/js/error-handler.js';
            if (file_exists($jsFile)) {
                $version = filemtime($jsFile);
                echo '<script src="/assets/js/error-handler.js?v=' . $version . '"></script>';
            }
        }
        
        // 4. Rodapé LGPD (se não for página de login)
        if ($config['includeLgpdFooter'] && $config['page'] !== 'login') {
            try {
                include BASE_PATH . '/views/components/lgpd-footer.php';
            } catch (Exception $e) {
                error_log("Erro ao incluir rodapé LGPD: " . $e->getMessage());
            }
        }
        
        return ob_get_clean();
    }
    
    /**
     * Include universal components (helper method)
     * 
     * @param array $options Configurações opcionais  
     */
    public static function includeUniversalComponents($options = [])
    {
        echo self::renderUniversalComponents($options);
    }
    
    /**
     * Renderiza apenas o sistema LGPD (cookies + footer)
     * 
     * @param string $page Nome da página atual
     * @return string HTML do sistema LGPD
     */
    public static function renderLgpdSystem($page = 'unknown')
    {
        return self::renderUniversalComponents([
            'includeCookieBanner' => true,
            'includeLgpdFooter' => true,
            'includeScriptBlocker' => true,
            'includeErrorHandler' => false,
            'page' => $page
        ]);
    }
    
    /**
     * Include LGPD system (helper method)
     * 
     * @param string $page Nome da página atual
     */
    public static function includeLgpdSystem($page = 'unknown')
    {
        echo self::renderLgpdSystem($page);
    }
    
    /**
     * Lista páginas que já têm sistema LGPD implementado
     * 
     * @return array Lista de páginas com LGPD
     */
    public static function getPagesWithLgpd()
    {
        return [
            'login' => '/views/auth/login.php',
            'dashboard' => '/views/dashboard/index.php',
            'access_scan' => '/views/access/scan.php',
            'privacy_index' => '/views/privacy/index.php',
            'privacy_cookies' => '/views/privacy/cookies.php',
            'privacy_portal' => '/views/privacy/portal.php'
        ];
    }
    
    /**
     * Verifica se página precisa de sistema LGPD
     * 
     * @param string $pageFile Caminho do arquivo da página
     * @return bool True se precisa, False se já tem ou não precisa
     */
    public static function needsLgpdSystem($pageFile)
    {
        $pagesWithLgpd = self::getPagesWithLgpd();
        
        // Verificar se página já tem sistema implementado
        foreach ($pagesWithLgpd as $implementedPage) {
            if (strpos($pageFile, $implementedPage) !== false) {
                return false; // Já tem sistema
            }
        }
        
        // Páginas que definitivamente precisam do sistema
        $needsLgpd = [
            '/views/employees/',
            '/views/reports/',
            '/views/config/',
            '/views/access/',
            '/views/profissionais_renner/',
            '/views/prestadores_servico/',
            '/views/visitantes_novo/'
        ];
        
        foreach ($needsLgpd as $path) {
            if (strpos($pageFile, $path) !== false) {
                return true;
            }
        }
        
        return false;
    }
}