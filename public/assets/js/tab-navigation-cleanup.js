/**
 * TAB NAVIGATION CLEANUP - ETAPA 6: Higiene UX
 * 
 * Gerencia limpeza automática ao navegar entre abas/módulos
 * Previne memory leaks e garante estado limpo
 */

(function() {
    'use strict';

    if (!window.CleanupManager) {
        console.warn('[TabNavigation] CleanupManager não disponível');
        return;
    }

    // Mapea modulos ativos
    const activeModules = new Set();
    let currentModule = null;

    // Observa mudanças de URL (para SPAs)
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;

    history.pushState = function(...args) {
        originalPushState.apply(this, args);
        detectModuleChange();
    };

    history.replaceState = function(...args) {
        originalReplaceState.apply(this, args);
        detectModuleChange();
    };

    window.addEventListener('popstate', detectModuleChange);

    // Detecta mudança de módulo pela URL
    function detectModuleChange() {
        const path = window.location.pathname;
        let newModule = 'default';

        if (path.includes('/config')) newModule = 'config';
        else if (path.includes('/dashboard')) newModule = 'dashboard';
        else if (path.includes('/visitantes')) newModule = 'visitantes';
        else if (path.includes('/prestadores')) newModule = 'prestadores';
        else if (path.includes('/profissionais')) newModule = 'profissionais';
        else if (path.includes('/ramais')) newModule = 'ramais';
        else if (path.includes('/brigada')) newModule = 'brigada';

        if (newModule !== currentModule) {
            const oldModule = currentModule;
            currentModule = newModule;
            onModuleChange(oldModule, newModule);
        }
    }

    // Ao mudar de módulo: limpar todos exceto o atual
    function onModuleChange(oldModule, newModule) {
        console.log(`[TabNavigation] Mudando de ${oldModule} → ${newModule}`);
        
        // Limpar módulos antigos
        CleanupManager.cleanupExcept(newModule);
        
        // Fechar modais abertos
        closeAllModals();
        
        // Limpar tooltips/popovers
        cleanupBootstrapComponents();
    }

    // Fechar todos os modais
    function closeAllModals() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }

    // Limpar componentes Bootstrap
    function cleanupBootstrapComponents() {
        // Tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => {
            const tooltip = bootstrap.Tooltip.getInstance(el);
            if (tooltip) tooltip.dispose();
        });

        // Popovers
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(el => {
            const popover = bootstrap.Popover.getInstance(el);
            if (popover) popover.dispose();
        });

        // Remove backdrop do modal
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remove classes do body
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    // Observa cliques em links de navegação
    function setupNavigationObserver() {
        const navLinks = document.querySelectorAll('.nav-link, [data-module]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const targetModule = this.dataset.module || 
                                    this.getAttribute('href')?.split('/')[1] || 
                                    'default';
                
                if (targetModule && targetModule !== currentModule) {
                    // Pequeno delay para permitir transição suave
                    setTimeout(() => detectModuleChange(), 50);
                }
            });
        });
    }

    // Limpar tudo ao sair da página
    window.addEventListener('beforeunload', function() {
        console.log('[TabNavigation] Limpando tudo antes de sair');
        CleanupManager.cleanupAll();
        closeAllModals();
        cleanupBootstrapComponents();
    });

    // Limpar ao fechar aba/janela
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            console.log('[TabNavigation] Página oculta - limpando recursos');
            CleanupManager.cleanupAll();
        }
    });

    // Inicialização
    document.addEventListener('DOMContentLoaded', function() {
        detectModuleChange(); // Detecta módulo inicial
        setupNavigationObserver();
        
        console.log('[TabNavigation] Sistema de navegação com cleanup ativo');
        console.log('[TabNavigation] Módulo atual:', currentModule);
    });

    // API pública
    window.TabNavigationCleanup = {
        getCurrentModule: () => currentModule,
        getActiveModules: () => Array.from(activeModules),
        cleanupModule: (module) => CleanupManager.cleanup(module),
        cleanupAll: () => CleanupManager.cleanupAll()
    };

})();
