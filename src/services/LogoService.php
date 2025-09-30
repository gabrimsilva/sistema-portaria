<?php

/**
 * 🎨 LogoService 3.0 - Gestão Avançada de Assets
 * 
 * Sistema completo de gerenciamento de logos com:
 * - Responsividade automática
 * - Fallbacks inteligentes 
 * - Lazy loading
 * - Dark/Light mode
 * - CDN ready
 * - Integração com configurações da organização
 */
class LogoService
{
    /**
     * @var ConfigService|null
     */
    private static $configService = null;
    
    /**
     * Inicializa o ConfigService (lazy loading)
     */
    private static function initConfigService()
    {
        if (self::$configService === null) {
            require_once __DIR__ . '/../utils/CnpjValidator.php';
            require_once __DIR__ . '/ConfigService.php';
            self::$configService = new ConfigService();
        }
    }
    
    /**
     * Obtém a logo configurada da organização
     * 
     * @return string|null URL da logo ou null se não configurada
     */
    private static function getOrganizationLogo()
    {
        try {
            self::initConfigService();
            $settings = self::$configService->getOrganizationSettings();
            
            if (!empty($settings['logo_url'])) {
                $logoUrl = $settings['logo_url'];
                
                // SEGURANÇA: Validar que a URL da logo está em diretório permitido
                // Remove tentativas de path traversal
                $logoUrl = str_replace(['../', '..\\'], '', $logoUrl);
                
                // Garante que começa com /
                if (!str_starts_with($logoUrl, '/')) {
                    $logoUrl = '/' . $logoUrl;
                }
                
                // Whitelist: apenas /assets/logos/ é permitido
                if (!str_starts_with($logoUrl, '/assets/logos/')) {
                    error_log('LogoService: Logo URL fora do diretório permitido: ' . $logoUrl);
                    return null;
                }
                
                // SEGURANÇA: Validar extensão (apenas imagens permitidas)
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                $extension = strtolower(pathinfo($logoUrl, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions)) {
                    error_log('LogoService: Extensão não permitida: ' . $extension);
                    return null;
                }
                
                // Verificar se arquivo existe fisicamente
                $logoPath = $_SERVER['DOCUMENT_ROOT'] . $logoUrl;
                $realPath = realpath($logoPath);
                $allowedDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/assets/logos/');
                
                // SEGURANÇA: Validação rigorosa de diretório com DIRECTORY_SEPARATOR
                if ($realPath && $allowedDir) {
                    $allowedDirNormalized = rtrim($allowedDir, DIRECTORY_SEPARATOR);
                    $isInAllowedDir = ($realPath === $allowedDirNormalized) || 
                                     str_starts_with($realPath, $allowedDirNormalized . DIRECTORY_SEPARATOR);
                    
                    if ($isInAllowedDir && is_file($realPath)) {
                        return $logoUrl;
                    }
                }
            }
        } catch (Exception $e) {
            // Em caso de erro, continuar com fallback
            error_log('LogoService: Erro ao buscar logo da organização: ' . $e->getMessage());
        }
        
        return null;
    }
    /**
     * Configurações avançadas de logos
     */
    private static $logos = [
        'renner' => [
            'primary' => '/assets/logos/logo-renner.png',
            'webp' => '/assets/logos/logo-renner.webp',
            'dark' => '/assets/logos/logo-renner-dark.png',
            'fallback' => '/assets/logos/logo-empresa.png',
            'icon' => 'fas fa-building',
            'alt' => 'Renner Logo',
            'brand' => 'Renner Coatings'
        ],
        'sistema' => [
            'primary' => '/assets/logos/logo-sistema.png',
            'webp' => '/assets/logos/logo-sistema.webp',
            'dark' => '/assets/logos/logo-sistema-dark.png',
            'fallback' => '/assets/logos/logo-renner.png',
            'icon' => 'fas fa-shield-alt',
            'alt' => 'Sistema Logo',
            'brand' => 'Sistema de Controle'
        ],
        'empresa' => [
            'primary' => '/assets/logos/logo-empresa.png',
            'webp' => '/assets/logos/logo-empresa.webp',
            'dark' => '/assets/logos/logo-empresa-dark.png',
            'fallback' => '/assets/logos/logo-renner.png',
            'icon' => 'fas fa-industry',
            'alt' => 'Empresa Logo',
            'brand' => 'Empresa'
        ]
    ];

    /**
     * Configurações para diferentes contextos (compatível com métodos legados)
     */
    private static $contexts = [
        'login' => [
            'width' => '150px',
            'height' => 'auto',
            'classes' => 'img-fluid logo-responsive',
            'style' => 'max-height: 80px;',
            'loading' => 'lazy',
            'responsive' => [
                'desktop' => ['width' => '180px', 'maxHeight' => '100px'],
                'tablet' => ['width' => '150px', 'maxHeight' => '80px'],
                'mobile' => ['width' => '120px', 'maxHeight' => '60px']
            ]
        ],
        'sidebar' => [
            'width' => '33px',
            'height' => '33px',
            'classes' => 'brand-image img-circle elevation-3 logo-sidebar',
            'style' => 'object-fit: contain;',
            'loading' => 'eager',
            'responsive' => [
                'desktop' => ['width' => '33px', 'height' => '33px'],
                'tablet' => ['width' => '30px', 'height' => '30px'],
                'mobile' => ['width' => '28px', 'height' => '28px']
            ]
        ],
        'header' => [
            'width' => '40px',
            'height' => '40px',
            'classes' => 'img-fluid logo-header',
            'style' => 'object-fit: contain;',
            'loading' => 'eager',
            'responsive' => [
                'desktop' => ['width' => '45px', 'height' => '45px'],
                'tablet' => ['width' => '40px', 'height' => '40px'],
                'mobile' => ['width' => '35px', 'height' => '35px']
            ]
        ],
        'card' => [
            'width' => '60px',
            'height' => '60px',
            'classes' => 'img-fluid logo-card',
            'style' => 'object-fit: contain;',
            'loading' => 'lazy',
            'responsive' => [
                'desktop' => ['width' => '60px', 'height' => '60px'],
                'tablet' => ['width' => '50px', 'height' => '50px'],
                'mobile' => ['width' => '40px', 'height' => '40px']
            ]
        ],
        'footer' => [
            'width' => '120px',
            'height' => 'auto',
            'classes' => 'img-fluid logo-footer',
            'style' => 'max-height: 60px;',
            'loading' => 'lazy',
            'responsive' => [
                'desktop' => ['width' => '120px', 'maxHeight' => '60px'],
                'tablet' => ['width' => '100px', 'maxHeight' => '50px'],
                'mobile' => ['width' => '80px', 'maxHeight' => '40px']
            ]
        ]
    ];

    /**
     * Obtém a URL do logo otimizada (WebP se suportado)
     * 
     * @param string $type Tipo do logo ('renner', 'sistema', 'empresa')
     * @param string $theme Tema ('light', 'dark', 'auto')
     * @param bool $preferWebP Usar WebP se disponível
     * @return string URL do logo otimizada
     */
    public static function getLogoUrl($type = 'renner', $theme = 'light', $preferWebP = true)
    {
        // PRIORIDADE 1: Logo configurada da organização (do banco de dados)
        $organizationLogo = self::getOrganizationLogo();
        if ($organizationLogo !== null) {
            return $organizationLogo;
        }
        
        // PRIORIDADE 2: Logos hardcoded (fallback)
        $logo = self::$logos[$type] ?? self::$logos['renner'];
        
        // Dark mode
        if ($theme === 'dark' && isset($logo['dark']) && self::fileExists($logo['dark'])) {
            return $logo['dark'];
        }
        
        // WebP se suportado e disponível
        if ($preferWebP && isset($logo['webp']) && self::fileExists($logo['webp'])) {
            return $logo['webp'];
        }
        
        // Logo principal
        if (self::fileExists($logo['primary'])) {
            return $logo['primary'];
        }
        
        // Fallback
        if (isset($logo['fallback']) && self::fileExists($logo['fallback'])) {
            return $logo['fallback'];
        }
        
        // Ultimate fallback (ícone FontAwesome)
        return self::$logos['renner']['primary'];
    }

    /**
     * Obtém a URL do logo de fallback
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @return string URL do logo de fallback
     */
    public static function getFallbackUrl($type = 'renner')
    {
        return self::$logos[$type]['fallback'] ?? self::$logos['renner']['fallback'];
    }

    /**
     * Obtém o ícone FontAwesome de fallback
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @return string Classe do ícone FontAwesome
     */
    public static function getFallbackIcon($type = 'renner')
    {
        return self::$logos[$type]['icon'] ?? self::$logos['renner']['icon'];
    }

    /**
     * Gera HTML completo para o logo com fallbacks
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @param string $context Contexto de uso ('login', 'sidebar', 'header')
     * @param array $customAttributes Atributos customizados
     * @return string HTML do logo com fallbacks
     */
    public static function renderLogo($type = 'renner', $context = 'sidebar', $customAttributes = [])
    {
        $logoUrl = self::getLogoUrl($type);
        $fallbackIcon = self::getFallbackIcon($type);
        $contextConfig = self::$contexts[$context] ?? self::$contexts['sidebar'];
        
        // Mescla configurações do contexto com atributos customizados
        $attributes = array_merge($contextConfig, $customAttributes);
        
        // Gera ID único para o fallback
        $uniqueId = 'logo_' . $type . '_' . $context . '_' . uniqid();
        
        $html = '<div class="logo-container">';
        
        // Imagem principal
        $html .= sprintf(
            '<img id="%s" src="%s" alt="%s Logo" class="%s" style="%s; width: %s; height: %s;" onload="this.style.display=\'block\'; document.getElementById(\'%s_fallback\').style.display=\'none\';" onerror="this.style.display=\'none\'; document.getElementById(\'%s_fallback\').style.display=\'inline-block\';">',
            $uniqueId,
            htmlspecialchars($logoUrl),
            ucfirst($type),
            htmlspecialchars($attributes['classes']),
            htmlspecialchars($attributes['style']),
            htmlspecialchars($attributes['width']),
            htmlspecialchars($attributes['height']),
            $uniqueId,
            $uniqueId
        );
        
        // Ícone de fallback
        $html .= sprintf(
            '<i id="%s_fallback" class="%s" style="display: none; font-size: %s; color: #dc3545; width: %s; height: %s; text-align: center; line-height: %s;"></i>',
            $uniqueId,
            htmlspecialchars($fallbackIcon),
            $context === 'login' ? '35px' : '20px',
            htmlspecialchars($attributes['width']),
            htmlspecialchars($attributes['height']),
            htmlspecialchars($attributes['height'])
        );
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Gera apenas a tag img simples (sem fallback)
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @param string $context Contexto de uso ('login', 'sidebar', 'header')
     * @param array $customAttributes Atributos customizados
     * @return string HTML da tag img
     */
    public static function renderSimpleLogo($type = 'renner', $context = 'sidebar', $customAttributes = [])
    {
        $logoUrl = self::getLogoUrl($type);
        $contextConfig = self::$contexts[$context] ?? self::$contexts['sidebar'];
        
        // Mescla configurações do contexto com atributos customizados
        $attributes = array_merge($contextConfig, $customAttributes);
        
        return sprintf(
            '<img src="%s" alt="%s Logo" class="%s" style="%s; width: %s; height: %s;">',
            htmlspecialchars($logoUrl),
            ucfirst($type),
            htmlspecialchars($attributes['classes']),
            htmlspecialchars($attributes['style']),
            htmlspecialchars($attributes['width']),
            htmlspecialchars($attributes['height'])
        );
    }

    /**
     * Renderiza logo responsivo avançado com fallbacks inteligentes
     * 
     * @param string $type Tipo do logo
     * @param string $context Contexto de uso
     * @param array $options Opções customizadas
     * @return string HTML do logo responsivo
     */
    public static function renderAdvancedLogo($type = 'renner', $context = 'sidebar', $options = [])
    {
        $defaults = [
            'theme' => 'auto',
            'lazy' => true,
            'responsive' => true,
            'fallbackIcon' => true,
            'cdnPrefix' => '',
            'srcset' => true
        ];
        
        $config = array_merge($defaults, $options);
        $logo = self::$logos[$type] ?? self::$logos['renner'];
        $contextConfig = self::$contexts[$context] ?? self::$contexts['sidebar'];
        
        $uniqueId = 'logo_' . $type . '_' . $context . '_' . uniqid();
        
        // URLs para diferentes formatos
        $primaryUrl = self::getLogoUrl($type, 'light', false);
        $webpUrl = self::getLogoUrl($type, 'light', true);
        $darkUrl = self::getLogoUrl($type, 'dark', false);
        
        $html = '<div class="logo-container ' . htmlspecialchars($contextConfig['classes']) . '">';
        
        // Picture element com order correta: Dark primeiro, depois WebP
        $html .= '<picture id="' . $uniqueId . '">';
        
        // Dark mode sources (primeiro - mais específico)
        if (self::fileExists($darkUrl)) {
            $html .= '<source srcset="' . htmlspecialchars($darkUrl) . '" media="(prefers-color-scheme: dark)">';
            
            // Dark WebP se existir
            $darkWebpUrl = str_replace('.png', '-dark.webp', $webpUrl);
            if (self::fileExists($darkWebpUrl)) {
                $html .= '<source srcset="' . htmlspecialchars($darkWebpUrl) . '" media="(prefers-color-scheme: dark)" type="image/webp">';
            }
        }
        
        // Light WebP sources
        if ($config['srcset'] && self::fileExists($webpUrl)) {
            $html .= '<source srcset="' . htmlspecialchars($webpUrl) . '" type="image/webp">';
        }
        
        // Main image
        $html .= '<img ';
        $html .= 'src="' . htmlspecialchars($primaryUrl) . '" ';
        $html .= 'alt="' . htmlspecialchars($logo['alt']) . '" ';
        $html .= 'title="' . htmlspecialchars($logo['brand']) . '" ';
        $html .= 'class="' . htmlspecialchars($contextConfig['classes']) . '" ';
        
        // Responsividade
        if ($config['responsive']) {
            $html .= 'style="' . self::generateResponsiveStyles($contextConfig) . '" ';
        }
        
        // Loading
        if ($config['lazy'] && $contextConfig['loading'] === 'lazy') {
            $html .= 'loading="lazy" ';
        }
        
        // Eventos de erro para fallback
        if ($config['fallbackIcon']) {
            $fallbackIcon = $logo['icon'];
            $html .= 'onerror="this.style.display=\'none\'; ';
            $html .= 'document.getElementById(\'' . $uniqueId . '_fallback\').style.display=\'inline-block\';" ';
        }
        
        $html .= '>';
        $html .= '</picture>';
        
        // Fallback icon
        if ($config['fallbackIcon']) {
            $html .= '<i id="' . $uniqueId . '_fallback" ';
            $html .= 'class="' . htmlspecialchars($logo['icon']) . '" ';
            $html .= 'style="display: none; font-size: ' . self::getIconSize($context) . '; color: #6c757d;" ';
            $html .= 'title="' . htmlspecialchars($logo['brand']) . '"></i>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gera estilos CSS responsivos melhorados
     * 
     * @param array $contextConfig Configuração do contexto
     * @return string CSS inline
     */
    private static function generateResponsiveStyles($contextConfig)
    {
        $styles = [];
        
        // Estilos base do contexto (compatibilidade)
        if (isset($contextConfig['width'])) {
            $styles[] = 'width: ' . $contextConfig['width'];
        }
        if (isset($contextConfig['height'])) {
            $styles[] = 'height: ' . $contextConfig['height'];
        }
        if (isset($contextConfig['style'])) {
            $styles[] = trim($contextConfig['style'], ';');
        }
        
        // Responsive adicional para desktop (padrão)
        if (isset($contextConfig['responsive']['desktop'])) {
            foreach ($contextConfig['responsive']['desktop'] as $prop => $value) {
                $cssProp = self::camelToKebab($prop);
                $styles[] = $cssProp . ': ' . $value;
            }
        }
        
        return implode('; ', $styles);
    }
    
    /**
     * Converte camelCase para kebab-case
     */
    private static function camelToKebab($string)
    {
        $kebab = [
            'maxHeight' => 'max-height',
            'maxWidth' => 'max-width',
            'minHeight' => 'min-height',
            'minWidth' => 'min-width'
        ];
        
        return $kebab[$string] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $string));
    }
    
    /**
     * Determina tamanho do ícone de fallback
     */
    private static function getIconSize($context)
    {
        $sizes = [
            'login' => '48px',
            'header' => '24px',
            'sidebar' => '20px',
            'card' => '30px',
            'footer' => '24px'
        ];
        
        return $sizes[$context] ?? '20px';
    }
    
    /**
     * Verifica se arquivo existe fisicamente
     * 
     * @param string $url URL do arquivo
     * @return bool True se existe
     */
    private static function fileExists($url)
    {
        if (!$url) return false;
        
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $url;
        return file_exists($filePath) && is_file($filePath);
    }
    
    /**
     * Detecta automaticamente logos disponíveis na pasta
     * 
     * @return array Lista de logos encontrados
     */
    public static function scanAvailableLogos()
    {
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/logos/';
        $found = [];
        
        if (!is_dir($logoPath)) {
            return $found;
        }
        
        $files = glob($logoPath . '*.{png,jpg,jpeg,webp,svg}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $filename = basename($file);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            
            // Detectar tipo baseado no nome
            if (strpos($name, 'renner') !== false) {
                $found['renner'][] = '/assets/logos/' . $filename;
            } elseif (strpos($name, 'sistema') !== false) {
                $found['sistema'][] = '/assets/logos/' . $filename;
            } elseif (strpos($name, 'empresa') !== false) {
                $found['empresa'][] = '/assets/logos/' . $filename;
            } else {
                $found['outros'][] = '/assets/logos/' . $filename;
            }
        }
        
        return $found;
    }
    
    /**
     * Gera CSS responsivo para logos
     * 
     * @return string CSS completo
     */
    public static function generateLogoCSS()
    {
        $css = "
/* 🎨 LogoService 3.0 - CSS Responsivo */
.logo-container {
    display: inline-block;
    position: relative;
}

.logo-responsive {
    transition: all 0.3s ease;
}

.logo-sidebar {
    object-fit: contain;
    transition: transform 0.2s ease;
}

.logo-sidebar:hover {
    transform: scale(1.1);
}

.logo-header {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.logo-card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.logo-footer {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.logo-footer:hover {
    opacity: 1;
}

/* Responsividade */
@media (max-width: 768px) {
    .logo-container img {
        max-width: 100%;
        height: auto;
    }
}

@media (max-width: 576px) {
    .logo-responsive {
        max-width: 80%;
    }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .logo-header, .logo-card {
        filter: brightness(0.9);
    }
}

/* Print */
@media print {
    .logo-container {
        filter: grayscale(100%);
    }
}
";
        
        return $css;
    }

    /**
     * Lista todos os tipos de logo disponíveis
     * 
     * @return array Lista dos tipos de logo
     */
    public static function getAvailableTypes()
    {
        return array_keys(self::$logos);
    }

    /**
     * Lista todos os contextos disponíveis
     * 
     * @return array Lista dos contextos
     */
    public static function getAvailableContexts()
    {
        return array_keys(self::$contexts);
    }
}