/**
 * Script Blocker LGPD
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Bloqueia scripts opcionais até consentimento do usuário
 * Versão: 2.3.0
 */

window.ScriptBlocker = (function() {
    'use strict';
    
    // Configuração de scripts por categoria
    const SCRIPT_CATEGORIES = {
        essential: {
            // Scripts essenciais sempre carregam
            scripts: [
                '/assets/js/cookie-consent.js',
                '/assets/js/csrf-protection.js',
                'jquery',
                'bootstrap',
                'adminlte'
            ],
            blocked: false
        },
        functional: {
            scripts: [
                '/assets/js/theme-manager.js',
                '/assets/js/layout-preferences.js',
                '/assets/js/user-settings.js'
            ],
            blocked: true
        },
        performance: {
            scripts: [
                '/assets/js/analytics.js',
                '/assets/js/performance-monitor.js',
                '/assets/js/error-tracking.js'
            ],
            blocked: true
        },
        marketing: {
            scripts: [
                '/assets/js/marketing-tracking.js',
                '/assets/js/social-widgets.js'
            ],
            blocked: true
        }
    };
    
    // Lista de scripts bloqueados aguardando consentimento
    let blockedScripts = [];
    let originalCreateElement = null;
    
    /**
     * Inicializa o sistema de bloqueio
     */
    function init() {
        try {
            // Interceptar criação de elementos script
            interceptScriptCreation();
            
            // Processar scripts já existentes no DOM
            processExistingScripts();
            
            // Escutar mudanças de consentimento
            listenForConsentChanges();
            
            console.log('✅ ScriptBlocker inicializado com sucesso');
        } catch (error) {
            console.error('❌ Erro ao inicializar ScriptBlocker:', error);
        }
    }
    
    /**
     * Intercepta criação de novos elementos script
     */
    function interceptScriptCreation() {
        // Salvar referência original
        originalCreateElement = document.createElement;
        
        // Interceptar document.createElement
        document.createElement = function(tagName) {
            const element = originalCreateElement.call(document, tagName);
            
            if (tagName.toLowerCase() === 'script') {
                // Adicionar interceptor quando src é definido
                Object.defineProperty(element, 'src', {
                    set: function(value) {
                        const category = getScriptCategory(value);
                        
                        if (shouldBlockScript(category)) {
                            console.log('🚫 Script bloqueado:', value, '(categoria:', category + ')');
                            blockedScripts.push({
                                element: element,
                                src: value,
                                category: category
                            });
                            return; // Não define src, bloqueia o script
                        }
                        
                        // Script permitido, define src normalmente
                        this._src = value;
                        if (this.parentNode) {
                            this.setAttribute('src', value);
                        }
                    },
                    get: function() {
                        return this._src;
                    }
                });
            }
            
            return element;
        };
    }
    
    /**
     * Processa scripts já existentes no DOM
     */
    function processExistingScripts() {
        const scripts = document.querySelectorAll('script[src]');
        
        scripts.forEach(script => {
            const category = getScriptCategory(script.src);
            
            if (shouldBlockScript(category)) {
                console.log('🚫 Script existente bloqueado:', script.src, '(categoria:', category + ')');
                
                // Remover script do DOM
                const parent = script.parentNode;
                if (parent) {
                    parent.removeChild(script);
                    
                    // Adicionar à lista de bloqueados
                    blockedScripts.push({
                        element: script.cloneNode(true),
                        src: script.src,
                        category: category,
                        parent: parent
                    });
                }
            }
        });
    }
    
    /**
     * Determina categoria do script baseado na URL
     */
    function getScriptCategory(src) {
        if (!src) return 'essential';
        
        // Verificar cada categoria
        for (const [category, config] of Object.entries(SCRIPT_CATEGORIES)) {
            for (const pattern of config.scripts) {
                if (src.includes(pattern) || src.match(new RegExp(pattern))) {
                    return category;
                }
            }
        }
        
        // Scripts externos não reconhecidos são bloqueados como performance
        if (src.startsWith('http') && !src.includes(location.hostname)) {
            return 'performance';
        }
        
        return 'essential'; // Por padrão, scripts locais são essenciais
    }
    
    /**
     * Verifica se script deve ser bloqueado
     */
    function shouldBlockScript(category) {
        const config = SCRIPT_CATEGORIES[category];
        if (!config) return false;
        
        // Se categoria não está bloqueada, permite
        if (!config.blocked) return false;
        
        // Verificar consentimento do usuário
        const consent = getConsentStatus();
        
        switch (category) {
            case 'functional':
                return !consent.functional;
            case 'performance':
                return !consent.performance;
            case 'marketing':
                return !consent.marketing;
            default:
                return false; // Essenciais nunca são bloqueados
        }
    }
    
    /**
     * Obtém status de consentimento atual
     */
    function getConsentStatus() {
        const defaultConsent = {
            essential: true,
            functional: false,
            performance: false,
            marketing: false,
            hasConsented: false
        };
        
        try {
            const cookieValue = getCookie('renner_cookie_consent');
            if (cookieValue) {
                const consent = JSON.parse(cookieValue);
                return Object.assign(defaultConsent, consent);
            }
        } catch (error) {
            console.warn('⚠️ Erro ao ler consentimento:', error);
        }
        
        return defaultConsent;
    }
    
    /**
     * Escuta mudanças no consentimento
     */
    function listenForConsentChanges() {
        // Escutar eventos customizados do CookieConsent
        document.addEventListener('cookieConsentChanged', function(event) {
            const consent = event.detail || getConsentStatus();
            unblockAllowedScripts(consent);
        });
        
        // Também escutar evento de atualização (fallback)
        document.addEventListener('cookieConsentUpdated', function(event) {
            const consent = event.detail || getConsentStatus();
            unblockAllowedScripts(consent);
        });
        
        // Verificar mudanças no cookie periodicamente
        setInterval(function() {
            const currentConsent = getConsentStatus();
            if (currentConsent.hasConsented) {
                unblockAllowedScripts(currentConsent);
            }
        }, 2000);
    }
    
    /**
     * Desbloqueia scripts permitidos baseado no consentimento
     */
    function unblockAllowedScripts(consent) {
        const scriptsToLoad = [];
        
        blockedScripts.forEach((blockedScript, index) => {
            const shouldUnblock = checkShouldUnblock(blockedScript.category, consent);
            
            if (shouldUnblock) {
                scriptsToLoad.push(blockedScript);
                blockedScripts.splice(index, 1);
            }
        });
        
        // Carregar scripts desbloqueados
        scriptsToLoad.forEach(scriptData => {
            loadScript(scriptData);
        });
        
        if (scriptsToLoad.length > 0) {
            console.log('✅ Scripts desbloqueados:', scriptsToLoad.length);
        }
    }
    
    /**
     * Verifica se script deve ser desbloqueado
     */
    function checkShouldUnblock(category, consent) {
        switch (category) {
            case 'essential':
                return true;
            case 'functional':
                return consent.functional;
            case 'performance':
                return consent.performance;
            case 'marketing':
                return consent.marketing;
            default:
                return false;
        }
    }
    
    /**
     * Carrega script desbloqueado
     */
    function loadScript(scriptData) {
        try {
            console.log('🔓 Carregando script desbloqueado:', scriptData.src);
            
            const script = document.createElement('script');
            script.src = scriptData.src;
            script.async = scriptData.element.async || false;
            script.defer = scriptData.element.defer || false;
            
            // Copiar outros atributos
            Array.from(scriptData.element.attributes).forEach(attr => {
                if (attr.name !== 'src') {
                    script.setAttribute(attr.name, attr.value);
                }
            });
            
            // Inserir no DOM
            const parent = scriptData.parent || document.head;
            parent.appendChild(script);
            
        } catch (error) {
            console.error('❌ Erro ao carregar script:', scriptData.src, error);
        }
    }
    
    /**
     * Utilitário para ler cookies
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }
    
    /**
     * API pública
     */
    return {
        init: init,
        getBlockedScripts: function() { return blockedScripts; },
        getScriptCategories: function() { return SCRIPT_CATEGORIES; },
        unblockCategory: function(category) {
            const consent = getConsentStatus();
            consent[category] = true;
            unblockAllowedScripts(consent);
        }
    };
})();

// Auto-inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ScriptBlocker.init);
} else {
    ScriptBlocker.init();
}