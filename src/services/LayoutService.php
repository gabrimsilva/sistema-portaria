<?php

/**
 * 🎯 LayoutService - Sistema de Layout Base Unificado
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Centraliza toda a estrutura HTML do sistema, incluindo head, body,
 * navegação, footers e scripts. Elimina duplicação de código e
 * garante consistência em todas as páginas.
 * 
 * Versão: 3.0 - Layout Base + LGPD Integration
 */
class LayoutService
{
    /**
     * Renderiza o HTML head completo
     * 
     * @param array $options Configurações do head
     * @return string HTML do head
     */
    public static function renderHead($options = [])
    {
        $defaults = [
            'title' => 'Sistema de Controle de Acesso',
            'description' => 'Sistema de controle de acesso empresarial',
            'additionalMeta' => [],
            'additionalCSS' => [],
            'includeCSRF' => true
        ];
        
        $config = array_merge($defaults, $options);
        
        $html = '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        
        if ($config['includeCSRF']) {
            $html .= '<meta name="csrf-token" content="' . CSRFProtection::generateToken() . '">';
        }
        
        $html .= '<meta name="description" content="' . htmlspecialchars($config['description']) . '">';
        
        // Additional meta tags
        foreach ($config['additionalMeta'] as $meta) {
            $html .= '<meta';
            foreach ($meta as $attr => $value) {
                $html .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
            $html .= '>';
        }
        
        $html .= '<title>' . htmlspecialchars($config['title']) . '</title>';
        
        // CSS Framework (Bootstrap + AdminLTE)
        $html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">';
        $html .= '<link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">';
        $html .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">';
        
        // Additional CSS
        foreach ($config['additionalCSS'] as $css) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($css) . '">';
        }
        
        $html .= '</head>';
        
        return $html;
    }
    
    /**
     * Renderiza abertura do body + wrapper
     * 
     * @param array $options Configurações do body
     * @return string HTML do body opening
     */
    public static function renderBodyStart($options = [])
    {
        $defaults = [
            'bodyClass' => 'hold-transition sidebar-mini layout-fixed',
            'wrapperClass' => 'wrapper'
        ];
        
        $config = array_merge($defaults, $options);
        
        $html = '<body class="' . htmlspecialchars($config['bodyClass']) . '">';
        $html .= '<div class="' . htmlspecialchars($config['wrapperClass']) . '">';
        
        return $html;
    }
    
    /**
     * Renderiza navegação completa (Header + Sidebar)
     * 
     * @param string $context Contexto da página para navbar
     * @return string HTML da navegação
     */
    public static function renderNavigation($context = 'default')
    {
        $navbarConfig = HeaderService::getNavbarConfig($context);
        
        $html = HeaderService::renderNavbar($navbarConfig);
        $html .= NavigationService::renderSidebar();
        
        return $html;
    }
    
    /**
     * Renderiza content wrapper start
     * 
     * @param string $title Título da página (auto se null)
     * @param array $customBreadcrumbs Breadcrumbs personalizados
     * @param array $headerActions Ações no header
     * @return string HTML do content wrapper start
     */
    public static function renderContentStart($title = null, $customBreadcrumbs = null, $headerActions = [])
    {
        $html = '<div class="content-wrapper">';
        $html .= HeaderService::renderContentHeader($title, $customBreadcrumbs, $headerActions);
        $html .= '<section class="content">';
        $html .= '<div class="container-fluid">';
        
        return $html;
    }
    
    /**
     * Renderiza fechamento do content wrapper
     * 
     * @return string HTML do content wrapper end
     */
    public static function renderContentEnd()
    {
        return '</div></section></div>';
    }
    
    /**
     * Renderiza scripts JavaScript básicos
     * 
     * @param array $additionalScripts Scripts adicionais
     * @return string HTML dos scripts
     */
    public static function renderScripts($additionalScripts = [])
    {
        $html = '';
        
        // jQuery e Bootstrap
        $html .= '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>';
        
        // Additional scripts
        foreach ($additionalScripts as $script) {
            if (is_array($script)) {
                $html .= '<script';
                foreach ($script as $attr => $value) {
                    if ($attr !== 'content') {
                        $html .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
                    }
                }
                $html .= '>';
                if (isset($script['content'])) {
                    $html .= $script['content'];
                }
                $html .= '</script>';
            } else {
                $html .= '<script src="' . htmlspecialchars($script) . '"></script>';
            }
        }
        
        return $html;
    }
    
    /**
     * Renderiza fechamento completo (wrapper + body + html)
     * 
     * @return string HTML do fechamento
     */
    public static function renderBodyEnd()
    {
        return '</div></body></html>';
    }
    
    /**
     * Renderiza página completa com layout padrão
     * 
     * @param string $content Conteúdo principal da página
     * @param array $pageConfig Configurações da página
     * @return string HTML completo da página
     */
    public static function renderCompletePage($content, $pageConfig = [])
    {
        $defaults = [
            'title' => 'Sistema de Controle de Acesso',
            'context' => 'default',
            'pageTitle' => null,
            'breadcrumbs' => null,
            'headerActions' => [],
            'additionalScripts' => [],
            'additionalCSS' => [],
            'includeUniversal' => true
        ];
        
        $config = array_merge($defaults, $pageConfig);
        
        $html = '<!DOCTYPE html><html lang="pt-BR">';
        
        // Head
        $html .= self::renderHead([
            'title' => $config['title'],
            'additionalCSS' => $config['additionalCSS']
        ]);
        
        // Body start
        $html .= self::renderBodyStart();
        
        // Navigation
        $html .= self::renderNavigation($config['context']);
        
        // Content wrapper
        $html .= self::renderContentStart(
            $config['pageTitle'], 
            $config['breadcrumbs'], 
            $config['headerActions']
        );
        
        // Main content
        $html .= $content;
        
        // Content end
        $html .= self::renderContentEnd();
        
        // Scripts
        $html .= self::renderScripts($config['additionalScripts']);
        
        // Universal components
        if ($config['includeUniversal']) {
            $html .= self::renderUniversalComponents();
        }
        
        // Body end
        $html .= self::renderBodyEnd();
        
        return $html;
    }
    
    /**
     * Renderiza apenas o esqueleto da página (para conteúdo customizado)
     * 
     * @param array $pageConfig Configurações da página
     * @return array ['start' => string, 'end' => string]
     */
    public static function renderPageSkeleton($pageConfig = [])
    {
        $defaults = [
            'title' => 'Sistema de Controle de Acesso',
            'context' => 'default',
            'pageTitle' => null,
            'breadcrumbs' => null,
            'headerActions' => [],
            'additionalScripts' => [],
            'additionalCSS' => [],
            'includeUniversal' => true
        ];
        
        $config = array_merge($defaults, $pageConfig);
        
        $start = '<!DOCTYPE html><html lang="pt-BR">';
        $start .= self::renderHead([
            'title' => $config['title'],
            'additionalCSS' => $config['additionalCSS']
        ]);
        $start .= self::renderBodyStart();
        $start .= self::renderNavigation($config['context']);
        $start .= self::renderContentStart(
            $config['pageTitle'], 
            $config['breadcrumbs'], 
            $config['headerActions']
        );
        
        $end = self::renderContentEnd();
        $end .= self::renderScripts($config['additionalScripts']);
        
        if ($config['includeUniversal']) {
            $end .= self::renderUniversalComponents();
        }
        
        $end .= self::renderBodyEnd();
        
        return ['start' => $start, 'end' => $end];
    }

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