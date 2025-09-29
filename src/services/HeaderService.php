<?php

/**
 * 🎯 HeaderService - Centralização da Navbar/Header
 * 
 * Gerencia toda a estrutura de header/navbar do sistema de forma
 * unificada, incluindo user info, botões contextuais e responsividade.
 */
class HeaderService
{
    /**
     * Renderiza a navbar completa do sistema
     * 
     * @param array $options Opções para customização
     * @return string HTML da navbar
     */
    public static function renderNavbar($options = [])
    {
        $defaults = [
            'showScanButton' => false,
            'additionalButtons' => [],
            'customClasses' => 'navbar-white navbar-light'
        ];
        
        $config = array_merge($defaults, $options);
        
        $html = '<nav class="main-header navbar navbar-expand ' . htmlspecialchars($config['customClasses']) . '">';
        
        // Left side - No toggle button (hover-only sidebar)
        $html .= '<ul class="navbar-nav">';
        // Toggle button completely removed for hover-only behavior
        $html .= '</ul>';
        
        // Right side - User menu + additional buttons
        $html .= '<ul class="navbar-nav ml-auto">';
        
        // Scanner QR button (contextual)
        if ($config['showScanButton']) {
            $html .= '<li class="nav-item">';
            $html .= '<a href="/access?action=scan" class="nav-link">';
            $html .= '<i class="fas fa-qrcode"></i>';
            $html .= '<span class="d-none d-md-inline">Scanner QR</span>';
            $html .= '</a>';
            $html .= '</li>';
        }
        
        // Additional buttons (custom)
        foreach ($config['additionalButtons'] as $button) {
            $html .= '<li class="nav-item">';
            $html .= '<a href="' . htmlspecialchars($button['url']) . '" class="nav-link"';
            if (isset($button['target'])) {
                $html .= ' target="' . htmlspecialchars($button['target']) . '"';
            }
            $html .= '>';
            $html .= '<i class="' . htmlspecialchars($button['icon']) . '"></i>';
            $html .= '<span class="d-none d-md-inline">' . htmlspecialchars($button['label']) . '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }
        
        // User dropdown
        $html .= self::renderUserDropdown();
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Renderiza o dropdown do usuário
     * 
     * @return string HTML do dropdown
     */
    private static function renderUserDropdown()
    {
        $userName = $_SESSION['user_name'] ?? 'Usuário';
        $userProfile = $_SESSION['user_profile'] ?? 'Porteiro';
        
        $html = '<li class="nav-item dropdown">';
        $html .= '<a class="nav-link" data-toggle="dropdown" href="#">';
        $html .= '<i class="far fa-user"></i>';
        $html .= '<span class="d-none d-md-inline">' . htmlspecialchars($userName) . '</span>';
        $html .= '</a>';
        
        $html .= '<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">';
        $html .= '<span class="dropdown-item-text">' . htmlspecialchars($userProfile) . '</span>';
        $html .= '<div class="dropdown-divider"></div>';
        
        // User preferences/settings (future)
        $html .= '<a href="/config" class="dropdown-item">';
        $html .= '<i class="fas fa-cog mr-2"></i>Configurações';
        $html .= '</a>';
        
        $html .= '<div class="dropdown-divider"></div>';
        
        // Logout
        $html .= '<a href="/logout" class="dropdown-item">';
        $html .= '<i class="fas fa-sign-out-alt mr-2"></i>Sair';
        $html .= '</a>';
        
        $html .= '</div>';
        $html .= '</li>';
        
        return $html;
    }
    
    /**
     * Renderiza breadcrumbs automaticamente baseado na navegação
     * 
     * @param array $customBreadcrumbs Breadcrumbs personalizados (opcional)
     * @return string HTML dos breadcrumbs
     */
    public static function renderBreadcrumbs($customBreadcrumbs = null)
    {
        if ($customBreadcrumbs) {
            $breadcrumbs = $customBreadcrumbs;
        } else {
            $breadcrumbs = NavigationService::getBreadcrumbs();
        }
        
        if (empty($breadcrumbs)) {
            return '';
        }
        
        $html = '<ol class="breadcrumb float-sm-right">';
        
        foreach ($breadcrumbs as $breadcrumb) {
            if (isset($breadcrumb['active']) && $breadcrumb['active']) {
                $html .= '<li class="breadcrumb-item active">' . htmlspecialchars($breadcrumb['name']) . '</li>';
            } else {
                if ($breadcrumb['url']) {
                    $html .= '<li class="breadcrumb-item">';
                    $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '">' . htmlspecialchars($breadcrumb['name']) . '</a>';
                    $html .= '</li>';
                } else {
                    $html .= '<li class="breadcrumb-item">' . htmlspecialchars($breadcrumb['name']) . '</li>';
                }
            }
        }
        
        $html .= '</ol>';
        
        return $html;
    }
    
    /**
     * Renderiza content header completo (título + breadcrumbs)
     * 
     * @param string $title Título da página (auto se null)
     * @param array $customBreadcrumbs Breadcrumbs personalizados (opcional)
     * @param array $headerActions Ações no header (botões, etc)
     * @return string HTML do content header
     */
    public static function renderContentHeader($title = null, $customBreadcrumbs = null, $headerActions = [])
    {
        if (!$title) {
            $title = NavigationService::getPageTitle();
        }
        
        $html = '<div class="content-header">';
        $html .= '<div class="container-fluid">';
        $html .= '<div class="row mb-2">';
        
        // Title column
        $html .= '<div class="col-sm-6">';
        $html .= '<h1 class="m-0">' . htmlspecialchars($title) . '</h1>';
        $html .= '</div>';
        
        // Breadcrumbs + Actions column
        $html .= '<div class="col-sm-6">';
        
        // Header actions (botões, filtros, etc)
        if (!empty($headerActions)) {
            $html .= '<div class="float-sm-right mb-2">';
            foreach ($headerActions as $action) {
                $btnClass = $action['class'] ?? 'btn btn-primary';
                $html .= '<a href="' . htmlspecialchars($action['url']) . '" class="' . htmlspecialchars($btnClass) . '">';
                if (isset($action['icon'])) {
                    $html .= '<i class="' . htmlspecialchars($action['icon']) . '"></i> ';
                }
                $html .= htmlspecialchars($action['label']);
                $html .= '</a> ';
            }
            $html .= '</div>';
        }
        
        // Breadcrumbs
        $html .= self::renderBreadcrumbs($customBreadcrumbs);
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Configurações de navbar por contexto
     * 
     * @param string $context Contexto da página
     * @return array Configurações da navbar
     */
    public static function getNavbarConfig($context = 'default')
    {
        $configs = [
            'access' => [
                'showScanButton' => true,
                'additionalButtons' => []
            ],
            'register' => [
                'showScanButton' => true,
                'additionalButtons' => []
            ],
            'reports' => [
                'showScanButton' => false,
                'additionalButtons' => [
                    [
                        'url' => '/reports/export',
                        'icon' => 'fas fa-download',
                        'label' => 'Exportar'
                    ]
                ]
            ],
            'default' => [
                'showScanButton' => false,
                'additionalButtons' => []
            ]
        ];
        
        return $configs[$context] ?? $configs['default'];
    }
}