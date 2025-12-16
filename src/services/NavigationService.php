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
            'icon' => 'fas fa-phone-alt',
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
            'id' => 'privacy',
            'label' => 'Privacidade & LGPD',
            'url' => '/privacy',
            'icon' => 'fas fa-shield-alt',
            'permission' => 'all',
            'target' => '_blank',
            'children' => []
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
        
        // Calendário e Relógio
        $html .= self::renderSidebarCalendarClock();
        
        $html .= '</div>';
        $html .= '</aside>';
        
        return $html;
    }
    
    /**
     * Renderiza o calendário e relógio no sidebar
     * 
     * @return string HTML do calendário e relógio
     */
    private static function renderSidebarCalendarClock()
    {
        $html = '
        <div class="sidebar-calendar-clock mt-3 px-3 pb-3">
            <!-- Relógio Digital -->
            <div class="sidebar-clock text-center mb-2">
                <div id="sidebar-clock" class="clock-display">
                    <span class="clock-time">--:--:--</span>
                </div>
                <div class="clock-date text-muted small" id="sidebar-date">--/--/----</div>
            </div>
            
            <!-- Mini Calendário -->
            <div class="sidebar-mini-calendar">
                <div class="calendar-header d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="btn btn-xs btn-outline-light" id="cal-prev"><i class="fas fa-chevron-left"></i></button>
                    <span class="calendar-month-year text-white font-weight-bold" id="cal-month-year"></span>
                    <button type="button" class="btn btn-xs btn-outline-light" id="cal-next"><i class="fas fa-chevron-right"></i></button>
                </div>
                <table class="calendar-table w-100 text-center">
                    <thead>
                        <tr class="text-muted small">
                            <th>D</th><th>S</th><th>T</th><th>Q</th><th>Q</th><th>S</th><th>S</th>
                        </tr>
                    </thead>
                    <tbody id="cal-body"></tbody>
                </table>
            </div>
        </div>
        
        <style>
            .sidebar-calendar-clock {
                border-top: 1px solid rgba(255,255,255,0.1);
                background: #343a40;
                padding-top: 15px;
                margin-top: 30px;
            }
            .clock-display {
                background: linear-gradient(135deg, #1a73e8, #0d47a1);
                border-radius: 8px;
                padding: 10px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            }
            .clock-time {
                font-size: 1.8rem;
                font-weight: bold;
                color: #fff;
                font-family: "Courier New", monospace;
                letter-spacing: 2px;
            }
            .clock-date {
                color: rgba(255,255,255,0.7) !important;
            }
            .sidebar-mini-calendar {
                background: rgba(255,255,255,0.05);
                border-radius: 8px;
                padding: 10px;
            }
            .calendar-table th, .calendar-table td {
                padding: 3px;
                font-size: 0.75rem;
            }
            .calendar-table td {
                color: rgba(255,255,255,0.8);
                cursor: default;
            }
            .calendar-table td.today {
                background: #1a73e8;
                border-radius: 50%;
                color: #fff;
                font-weight: bold;
            }
            .calendar-table td.other-month {
                color: rgba(255,255,255,0.3);
            }
            #cal-prev, #cal-next {
                padding: 2px 6px;
                font-size: 0.7rem;
            }
            .calendar-month-year {
                font-size: 0.85rem;
            }
        </style>
        
        <script>
            (function() {
                // Relógio
                function updateClock() {
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString("pt-BR", {hour: "2-digit", minute: "2-digit", second: "2-digit"});
                    const dateStr = now.toLocaleDateString("pt-BR", {weekday: "long", day: "2-digit", month: "long", year: "numeric"});
                    
                    const clockEl = document.querySelector("#sidebar-clock .clock-time");
                    const dateEl = document.getElementById("sidebar-date");
                    
                    if (clockEl) clockEl.textContent = timeStr;
                    if (dateEl) dateEl.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
                }
                
                // Calendário
                let currentDate = new Date();
                
                function renderCalendar(date) {
                    const year = date.getFullYear();
                    const month = date.getMonth();
                    const today = new Date();
                    
                    const monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", 
                                        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
                    
                    const monthYearEl = document.getElementById("cal-month-year");
                    if (monthYearEl) monthYearEl.textContent = monthNames[month] + " " + year;
                    
                    const firstDay = new Date(year, month, 1).getDay();
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const daysInPrevMonth = new Date(year, month, 0).getDate();
                    
                    let html = "";
                    let day = 1;
                    let nextMonthDay = 1;
                    
                    for (let i = 0; i < 6; i++) {
                        html += "<tr>";
                        for (let j = 0; j < 7; j++) {
                            if (i === 0 && j < firstDay) {
                                const prevDay = daysInPrevMonth - firstDay + j + 1;
                                html += "<td class=\"other-month\">" + prevDay + "</td>";
                            } else if (day > daysInMonth) {
                                html += "<td class=\"other-month\">" + nextMonthDay++ + "</td>";
                            } else {
                                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                                html += "<td class=\"" + (isToday ? "today" : "") + "\">" + day++ + "</td>";
                            }
                        }
                        html += "</tr>";
                        if (day > daysInMonth && nextMonthDay > 1) break;
                    }
                    
                    const calBody = document.getElementById("cal-body");
                    if (calBody) calBody.innerHTML = html;
                }
                
                // Inicializar
                document.addEventListener("DOMContentLoaded", function() {
                    updateClock();
                    setInterval(updateClock, 1000);
                    
                    renderCalendar(currentDate);
                    
                    const prevBtn = document.getElementById("cal-prev");
                    const nextBtn = document.getElementById("cal-next");
                    
                    if (prevBtn) {
                        prevBtn.addEventListener("click", function(e) {
                            e.preventDefault();
                            currentDate.setMonth(currentDate.getMonth() - 1);
                            renderCalendar(currentDate);
                        });
                    }
                    
                    if (nextBtn) {
                        nextBtn.addEventListener("click", function(e) {
                            e.preventDefault();
                            currentDate.setMonth(currentDate.getMonth() + 1);
                            renderCalendar(currentDate);
                        });
                    }
                });
            })();
        </script>';
        
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