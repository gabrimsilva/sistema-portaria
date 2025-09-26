/**
 * Sistema de Consentimento de Cookies LGPD
 * Sistema de Controle de Acesso Renner Coatings
 * 
 * Gerencia consentimento de cookies conforme LGPD brasileira
 * Versão: 2.1.0
 */

window.CookieConsent = (function() {
    'use strict';
    
    // Configurações padrão
    const CONFIG = {
        bannerSelector: '#cookie-banner',
        modalSelector: '#cookiePreferencesModal',
        cookieName: 'renner_cookie_consent',
        cookieExpiry: 365, // dias
        showDelay: 1000, // ms para mostrar o banner
        autoHide: true,
        version: '2.1.0'
    };
    
    // Estado do consentimento
    let consentData = {
        version: CONFIG.version,
        timestamp: null,
        essential: true, // Sempre true, não pode ser desabilitado
        functional: false,
        performance: false,
        hasConsented: false
    };
    
    // Cache de elementos DOM
    let elements = {};
    
    /**
     * Inicializa o sistema de consentimento
     */
    function init() {
        try {
            // Cache elementos DOM
            cacheElements();
            
            // Carrega consentimento salvo
            loadConsentData();
            
            // Se não tem consentimento, mostra banner após delay
            if (!consentData.hasConsented) {
                setTimeout(showBanner, CONFIG.showDelay);
            } else {
                // Aplica configurações salvas
                applyConsentSettings();
            }
            
            // Setup event listeners
            setupEventListeners();
            
            console.log('✅ CookieConsent inicializado com sucesso');
        } catch (error) {
            console.error('❌ Erro ao inicializar CookieConsent:', error);
        }
    }
    
    /**
     * Cache elementos DOM para performance
     */
    function cacheElements() {
        elements = {
            banner: document.querySelector(CONFIG.bannerSelector),
            modal: document.querySelector(CONFIG.modalSelector),
            functionalCheckbox: document.getElementById('functionalCookies'),
            performanceCheckbox: document.getElementById('performanceCookies')
        };
        
        // Verifica se elementos essenciais existem
        if (!elements.banner) {
            console.warn('⚠️ Banner de cookies não encontrado');
        }
        
        if (!elements.modal) {
            console.warn('⚠️ Modal de preferências não encontrado');
        }
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Listener para mudanças nos checkboxes
        if (elements.functionalCheckbox) {
            elements.functionalCheckbox.addEventListener('change', updatePreviewState);
        }
        
        if (elements.performanceCheckbox) {
            elements.performanceCheckbox.addEventListener('change', updatePreviewState);
        }
        
        // Listener para fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && elements.modal) {
                const modalInstance = $(elements.modal);
                if (modalInstance.hasClass('show')) {
                    modalInstance.modal('hide');
                }
            }
        });
    }
    
    /**
     * Carrega dados de consentimento salvos
     */
    function loadConsentData() {
        try {
            const savedData = getCookie(CONFIG.cookieName);
            if (savedData) {
                const parsed = JSON.parse(savedData);
                
                // Verifica se versão é compatível
                if (parsed.version === CONFIG.version) {
                    consentData = { ...consentData, ...parsed };
                    console.log('📋 Consentimento carregado:', consentData);
                } else {
                    console.log('🔄 Nova versão detectada, resetando consentimento');
                    clearConsentData();
                }
            }
        } catch (error) {
            console.error('❌ Erro ao carregar consentimento:', error);
            clearConsentData();
        }
    }
    
    /**
     * Salva dados de consentimento
     */
    function saveConsentData() {
        try {
            consentData.timestamp = new Date().toISOString();
            consentData.hasConsented = true;
            
            const dataString = JSON.stringify(consentData);
            setCookie(CONFIG.cookieName, dataString, CONFIG.cookieExpiry);
            
            console.log('💾 Consentimento salvo:', consentData);
            
            // Aplica configurações
            applyConsentSettings();
            
            // Esconde banner
            hideBanner();
            
        } catch (error) {
            console.error('❌ Erro ao salvar consentimento:', error);
        }
    }
    
    /**
     * Aplica configurações de consentimento
     */
    function applyConsentSettings() {
        try {
            // Remove cookies não consentidos
            if (!consentData.functional) {
                removeCookiesByType('functional');
            }
            
            if (!consentData.performance) {
                removeCookiesByType('performance');
            }
            
            // Dispara evento personalizado para outros scripts
            const event = new CustomEvent('cookieConsentChanged', {
                detail: consentData
            });
            document.dispatchEvent(event);
            
            console.log('⚙️ Configurações aplicadas:', consentData);
            
        } catch (error) {
            console.error('❌ Erro ao aplicar configurações:', error);
        }
    }
    
    /**
     * Remove cookies por tipo
     */
    function removeCookiesByType(type) {
        const cookiesToRemove = {
            functional: ['language_pref', 'theme_mode', 'dashboard_layout'],
            performance: ['analytics_session', 'error_tracking']
        };
        
        if (cookiesToRemove[type]) {
            cookiesToRemove[type].forEach(cookieName => {
                deleteCookie(cookieName);
            });
            console.log(`🗑️ Cookies ${type} removidos`);
        }
    }
    
    /**
     * Mostra o banner de cookies
     */
    function showBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'block';
            console.log('👁️ Banner de cookies mostrado');
        }
    }
    
    /**
     * Esconde o banner de cookies
     */
    function hideBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'none';
            console.log('👁️‍🗨️ Banner de cookies escondido');
        }
    }
    
    /**
     * Mostra modal de preferências
     */
    function showPreferences() {
        if (!elements.modal) {
            console.warn('⚠️ Modal de preferências não encontrado');
            return;
        }
        
        // Atualiza checkboxes com valores atuais
        if (elements.functionalCheckbox) {
            elements.functionalCheckbox.checked = consentData.functional;
        }
        
        if (elements.performanceCheckbox) {
            elements.performanceCheckbox.checked = consentData.performance;
        }
        
        // Mostra modal usando Bootstrap
        $(elements.modal).modal('show');
        console.log('🔧 Modal de preferências mostrado');
    }
    
    /**
     * Aceita todos os cookies
     */
    function acceptAll() {
        consentData.functional = true;
        consentData.performance = true;
        saveConsentData();
        
        // Feedback visual
        showToast('✅ Todos os cookies foram aceitos', 'success');
        console.log('✅ Todos os cookies aceitos');
    }
    
    /**
     * Rejeita cookies opcionais (mantém apenas essenciais)
     */
    function rejectOptional() {
        consentData.functional = false;
        consentData.performance = false;
        saveConsentData();
        
        // Feedback visual
        showToast('ℹ️ Apenas cookies essenciais serão utilizados', 'info');
        console.log('ℹ️ Cookies opcionais rejeitados');
    }
    
    /**
     * Salva preferências do modal
     */
    function savePreferences() {
        if (elements.functionalCheckbox) {
            consentData.functional = elements.functionalCheckbox.checked;
        }
        
        if (elements.performanceCheckbox) {
            consentData.performance = elements.performanceCheckbox.checked;
        }
        
        saveConsentData();
        
        // Fecha modal
        $(elements.modal).modal('hide');
        
        // Feedback visual
        showToast('💾 Suas preferências foram salvas', 'success');
        console.log('💾 Preferências salvas via modal');
    }
    
    /**
     * Atualiza estado de preview no modal
     */
    function updatePreviewState() {
        // Aqui poderia adicionar preview em tempo real das mudanças
        console.log('🔄 Preview atualizado');
    }
    
    /**
     * Limpa dados de consentimento (para reset)
     */
    function clearConsentData() {
        consentData = {
            version: CONFIG.version,
            timestamp: null,
            essential: true,
            functional: false,
            performance: false,
            hasConsented: false
        };
        
        deleteCookie(CONFIG.cookieName);
        console.log('🧹 Dados de consentimento limpos');
    }
    
    /**
     * Mostra toast de notificação
     */
    function showToast(message, type = 'info') {
        // Verifica se existe sistema de toast (ex: AdminLTE)
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else if (typeof $ !== 'undefined') {
            // Fallback para alert simples se toastr não disponível
            const alertClass = type === 'success' ? 'alert-success' : 
                             type === 'error' ? 'alert-danger' : 'alert-info';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show cookie-toast" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            
            // Adiciona ao topo da página
            $('body').prepend(alertHtml);
            
            // Remove automaticamente após 4 segundos
            setTimeout(() => {
                $('.cookie-toast').alert('close');
            }, 4000);
        }
    }
    
    /**
     * Utilitários de cookie
     */
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }
    
    function deleteCookie(name) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
    }
    
    /**
     * API Pública
     */
    return {
        init: init,
        showPreferences: showPreferences,
        acceptAll: acceptAll,
        rejectOptional: rejectOptional,
        savePreferences: savePreferences,
        clearConsentData: clearConsentData,
        getConsentData: () => ({ ...consentData }), // Retorna cópia
        isConsentGiven: () => consentData.hasConsented,
        hasConsentFor: (type) => consentData[type] || false
    };
})();

// Auto-inicialização quando DOM estiver pronto
(function() {
    function initWhenReady() {
        if (typeof $ === 'undefined') {
            console.warn('⚠️ jQuery não encontrado, aguardando...');
            setTimeout(initWhenReady, 100);
            return;
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', CookieConsent.init);
        } else {
            CookieConsent.init();
        }
    }
    
    initWhenReady();
})();