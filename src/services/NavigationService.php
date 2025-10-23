<?php

/**
 * Serviço centralizado para navegação do sistema
 * 
 * Gerencia toda a estrutura de navegação de forma unificada,
 * incluindo permissões de usuário, detecção de página ativa
 * e renderização consistente.
 */
class NavigationService
{
    /**
     * Estrutura completa de navegação do sistema
     */
    private static $navigationStructure = [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'url' => '/dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'permission' => 'all',
            'children' => []
        ],
        [
            'id' => 'reports',
            'label' => 'Relatórios',
            'url' => '#',
            'icon' => 'fas fa-chart-bar',
            'permission' => 'all',
            'children' => [
                [
                    'id' => 'reports-profissionais',
                    'label' => 'Profissionais Renner',
                    'url' => '/reports/profissionais-renner',
                    'icon' => 'fas fa-user-tie'
                ],
                [
                    'id' => 'reports-visitantes',
                    'label' => 'Visitantes',
                    'url' => '/reports/visitantes',
                    'icon' => 'fas fa-users'
                ],
                [
                    'id' => 'reports-prestadores',
                    'label' => 'Prestador de Serviços',
                    'url' => '/reports/prestadores-servico',
                    'icon' => 'fas fa-tools'
                ]
            ]
        ],
        [
            'id' => 'privacy',
            'label' => 'Privacidade & LGPD',
            'url' => '/privacy',
            'icon' => 'fas fa-shield-alt',
            'permission' => 'all',
            'target' => '_blank',
            'children' => []
        ],
        [
            'id' => 'brigada',
            'label' => 'Brigada de Incêndio',
            'url' => '/brigada',
            'icon' => 'fas fa-fire-extinguisher',
            'permission' => ['administrador', 'seguranca', 'rh'],
            'children' => []
        ],
        [
            'id' => 'ramais',
            'label' => 'Ramais',
            'url' => '/ramais',
            'icon' => 'bi-telephone',
            'permission' => 'all',
            'children' => []
        ],
        [
            'id' => 'importacao',
            'label' => 'Importação',
            'url' => '/importacao',
            'icon' => 'fas fa-file-import',
            'permission' => ['administrador'],
            'children' => []
        ],
        [
            'id' => 'pre-cadastros',
            'label' => 'Pré-Cadastros',
            'url' => '#',
            'icon' => 'fas fa-address-card',
            'permission' => ['administrador', 'porteiro'],
            'children' => [
                [
                    'id' => 'pre-cadastros-visitantes',
                    'label' => 'Visitantes',
                    'url' => '/pre-cadastros/visitantes',
                    'icon' => 'fas fa-users'
                ],
                [
                    'id' => 'pre-cadastros-prestadores',
                    'label' => 'Prestadores de Serviço',
                    'url' => '/pre-cadastros/prestadores',
                    'icon' => 'fas fa-tools'
                ]
            ]
        ],
        [
            'id' => 'config',
            'label' => 'Configurações',
            'url' => '/config',
            'icon' => 'fas fa-cogs',
            'permission' => ['administrador', 'seguranca'],
            'children' => []
        ]
    ];

    /**
     * Mapeamento de URLs para detecção automática de página ativa
     */
    private static $urlMapping = [
        '/dashboard' => ['dashboard'],
        '/visitors' => ['visitors'],
        '/visitantes' => ['visitors'],
        '/visitantes_novo' => ['visitors'],
        '/employees' => ['employees'],
        '/prestadores-servico' => ['prestadores'],
        '/prestadores_servico' => ['prestadores'],
        '/access' => ['access'],
        '/access/register' => ['access'],
        '/access/scan' => ['access'],
        '/access/history' => ['access'],
        '/reports/profissionais-renner' => ['reports', 'reports-profissionais'],
        '/reports/visitantes' => ['reports', 'reports-visitantes'],
        '/reports/prestadores-servico' => ['reports', 'reports-prestadores'],
        '/profissionais-renner' => ['reports', 'reports-profissionais'],
        '/privacy' => ['privacy'],
        '/privacy/portal' => ['privacy'],
        '/privacy/cookies' => ['privacy'],
        '/brigada' => ['brigada'],
        '/ramais' => ['ramais'],
        '/importacao' => ['importacao'],
        '/pre-cadastros/visitantes' => ['pre-cadastros', 'pre-cadastros-visitantes'],
        '/pre-cadastros/prestadores' => ['pre-cadastros', 'pre-cadastros-prestadores'],
        '/config' => ['config']
    ];

    /**
     * Detecta automaticamente a página ativa baseada na URL atual
     * 
     * @return array IDs dos itens ativos (incluindo parent)
     */
    public static function detectActivePage()
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $cleanPath = parse_url($currentPath, PHP_URL_PATH);
        
        // Remove base URL se existir
        if (defined('BASE_URL') && strpos($cleanPath, BASE_URL) === 0) {
            $cleanPath = substr($cleanPath, strlen(BASE_URL));
        }
        
        $cleanPath = trim($cleanPath, '/');
        if (empty($cleanPath)) {
            $cleanPath = '/dashboard';
        } else {
            $cleanPath = '/' . $cleanPath;
        }
        
        // Busca mapeamento exato
        if (isset(self::$urlMapping[$cleanPath])) {
            return self::$urlMapping[$cleanPath];
        }
        
        // Busca por padrões (ex: /reports/qualquer-coisa)
        foreach (self::$urlMapping as $pattern => $activeIds) {
            if (strpos($cleanPath, $pattern) === 0) {
                return $activeIds;
            }
        }
        
        // Default: dashboard
        return ['dashboard'];
    }

    /**
     * Verifica se usuário tem permissão para ver um item de menu
     * 
     * @param array $item Item de navegação
     * @return bool True se tem permissão
     */
    public static function hasPermission($item)
    {
        $permission = $item['permission'] ?? 'all';
        
        if ($permission === 'all') {
            return true;
        }
        
        $userProfile = $_SESSION['user_profile'] ?? 'porteiro';
        
        if (is_array($permission)) {
            return in_array($userProfile, $permission);
        }
        
        return $userProfile === $permission;
    }

    /**
     * Renderiza a navegação sidebar completa
     * 
     * @return string HTML da navegação
     */
    public static function renderSidebar()
    {
        $activePages = self::detectActivePage();
        
        $html = '<aside class="main-sidebar sidebar-dark-primary elevation-4">';
        
        // Brand Link
        $html .= '<a href="/dashboard" class="brand-link">';
        $html .= LogoService::renderSimpleLogo('renner', 'sidebar');
        $html .= '<span class="brand-text font-weight-light">Controle Acesso</span>';
        $html .= '</a>';
        
        // Sidebar Navigation
        $html .= '<div class="sidebar">';
        $html .= '<nav class="mt-2">';
        $html .= '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">';
        
        foreach (self::$navigationStructure as $item) {
            if (!self::hasPermission($item)) {
                continue;
            }
            
            $html .= self::renderNavigationItem($item, $activePages);
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        $html .= '</div>';
        $html .= '</aside>';
        
        return $html;
    }

    /**
     * Renderiza um item individual da navegação
     * 
     * @param array $item Item de navegação
     * @param array $activePages IDs das páginas ativas
     * @return string HTML do item
     */
    private static function renderNavigationItem($item, $activePages)
    {
        $isActive = in_array($item['id'], $activePages);
        $hasChildren = !empty($item['children']);
        $isExpanded = $hasChildren && $isActive;
        
        $html = '<li class="nav-item';
        if ($hasChildren) {
            $html .= ' has-treeview';
            if ($isExpanded) {
                $html .= ' menu-open';
            }
        }
        $html .= '">';
        
        // Link principal
        $linkClass = 'nav-link';
        if ($isActive && !$hasChildren) {
            $linkClass .= ' active';
        } elseif ($isExpanded) {
            $linkClass .= ' active';
        }
        
        $target = isset($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        
        $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="' . $linkClass . '"' . $target . '>';
        $html .= '<i class="nav-icon ' . htmlspecialchars($item['icon']) . '"></i>';
        $html .= '<p style="font-size: 1.05rem; font-weight: 500;">' . htmlspecialchars($item['label']);
        
        if ($hasChildren) {
            $html .= ' <i class="fas fa-angle-left right"></i>';
        }
        
        $html .= '</p>';
        $html .= '</a>';
        
        // Submenu (se existir)
        if ($hasChildren) {
            $html .= '<ul class="nav nav-treeview">';
            
            foreach ($item['children'] as $child) {
                $childIsActive = in_array($child['id'], $activePages);
                $childLinkClass = 'nav-link' . ($childIsActive ? ' active' : '');
                
                $html .= '<li class="nav-item">';
                $html .= '<a href="' . htmlspecialchars($child['url']) . '" class="' . $childLinkClass . '">';
                $html .= '<i class="nav-icon ' . htmlspecialchars($child['icon']) . '" style="font-size: 0.85rem;"></i>';
                $html .= '<p style="font-size: 0.92rem; font-weight: 400; padding-left: 8px;">' . htmlspecialchars($child['label']) . '</p>';
                $html .= '</a>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
        }
        
        $html .= '</li>';
        
        return $html;
    }

    /**
     * Obtém breadcrumbs baseado na página atual
     * 
     * @return array Array de breadcrumbs
     */
    public static function getBreadcrumbs()
    {
        $activePages = self::detectActivePage();
        $breadcrumbs = [['name' => 'Início', 'url' => '/dashboard']];
        
        foreach (self::$navigationStructure as $item) {
            if (in_array($item['id'], $activePages)) {
                if (!empty($item['children'])) {
                    // Item pai tem filhos - adiciona o pai
                    $breadcrumbs[] = ['name' => $item['label'], 'url' => null];
                    
                    // Procura filho ativo
                    foreach ($item['children'] as $child) {
                        if (in_array($child['id'], $activePages)) {
                            $breadcrumbs[] = ['name' => $child['label'], 'url' => $child['url'], 'active' => true];
                            break;
                        }
                    }
                } else {
                    // Item simples
                    $breadcrumbs[] = ['name' => $item['label'], 'url' => $item['url'], 'active' => true];
                }
                break;
            }
        }
        
        return $breadcrumbs;
    }

    /**
     * Obtém título da página atual
     * 
     * @return string Título da página
     */
    public static function getPageTitle()
    {
        $activePages = self::detectActivePage();
        
        foreach (self::$navigationStructure as $item) {
            if (in_array($item['id'], $activePages)) {
                if (!empty($item['children'])) {
                    // Busca filho ativo
                    foreach ($item['children'] as $child) {
                        if (in_array($child['id'], $activePages)) {
                            return $child['label'];
                        }
                    }
                }
                return $item['label'];
            }
        }
        
        return 'Dashboard';
    }

    /**
     * Lista todos os itens de navegação para debug
     * 
     * @return array Estrutura completa
     */
    public static function getNavigationStructure()
    {
        return self::$navigationStructure;
    }
}