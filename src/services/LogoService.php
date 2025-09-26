<?php

/**
 * Serviço centralizado para gerenciamento de logos do sistema
 * 
 * Fornece URLs e configurações consistentes para todos os logos
 * usados no sistema, incluindo fallbacks e diferentes contextos.
 */
class LogoService
{
    /**
     * Configurações de logos disponíveis
     */
    private static $logos = [
        'renner' => [
            'primary' => '/assets/logos/logo-renner.png',
            'fallback' => '/assets/logos/logo-renner-fallback.png',
            'icon' => 'fas fa-building'
        ],
        'sistema' => [
            'primary' => '/assets/logos/logo-sistema.png',
            'fallback' => '/assets/logos/logo-sistema-fallback.png', 
            'icon' => 'fas fa-shield-alt'
        ]
    ];

    /**
     * Configurações padrão para diferentes contextos
     */
    private static $contexts = [
        'login' => [
            'width' => '150px',
            'height' => 'auto',
            'classes' => 'img-fluid',
            'style' => 'max-height: 80px;'
        ],
        'sidebar' => [
            'width' => '33px',
            'height' => '33px',
            'classes' => 'brand-image img-circle elevation-3',
            'style' => 'object-fit: contain;'
        ],
        'header' => [
            'width' => '40px',
            'height' => '40px',
            'classes' => 'img-fluid',
            'style' => 'object-fit: contain;'
        ]
    ];

    /**
     * Obtém a URL do logo principal
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @return string URL do logo
     */
    public static function getLogoUrl($type = 'renner')
    {
        return self::$logos[$type]['primary'] ?? self::$logos['renner']['primary'];
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
     * Verifica se um logo existe fisicamente
     * 
     * @param string $type Tipo do logo ('renner', 'sistema')
     * @return bool True se o arquivo existe
     */
    public static function logoExists($type = 'renner')
    {
        $logoUrl = self::getLogoUrl($type);
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $logoUrl;
        return file_exists($filePath);
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