/**
 * CLEANUP MANAGER - Sistema Central de Higiene UX
 * 
 * Gerencia:
 * - AbortControllers para cancelar requisições AJAX
 * - Timers (setTimeout/setInterval) 
 * - Event listeners
 * - Estados isolados por aba/módulo
 * 
 * Uso:
 * const cleanup = CleanupManager.register('meu-modulo');
 * cleanup.fetch('/api/dados', { method: 'GET' });
 * cleanup.setTimeout(() => {}, 1000);
 * cleanup.addEventListener(element, 'click', handler);
 * 
 * CleanupManager.cleanup('meu-modulo'); // Limpa tudo
 */

class CleanupManager {
    static instances = new Map();
    static tabStates = {
        config: {},
        dashboard: {},
        visitantes: {},
        prestadores: {},
        profissionais: {},
        ramais: {},
        brigada: {}
    };

    constructor(moduleName) {
        this.moduleName = moduleName;
        this.abortController = new AbortController();
        this.timers = new Set();
        this.intervals = new Set();
        this.listeners = new Set();
        this.state = {};
    }

    /**
     * Registra um módulo no sistema de cleanup
     */
    static register(moduleName) {
        if (!this.instances.has(moduleName)) {
            this.instances.set(moduleName, new CleanupManager(moduleName));
        }
        return this.instances.get(moduleName);
    }

    /**
     * Fetch com AbortController automático
     */
    async fetch(url, options = {}) {
        const fetchOptions = {
            ...options,
            signal: this.abortController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, fetchOptions);
            return response;
        } catch (error) {
            if (error.name === 'AbortError') {
                console.log(`[${this.moduleName}] Request cancelada (cleanup)`);
                return null;
            }
            throw error;
        }
    }

    /**
     * setTimeout com cleanup automático
     */
    setTimeout(callback, delay, ...args) {
        const timerId = setTimeout((...params) => {
            this.timers.delete(timerId);
            callback(...params);
        }, delay, ...args);
        
        this.timers.add(timerId);
        return timerId;
    }

    /**
     * setInterval com cleanup automático
     */
    setInterval(callback, delay, ...args) {
        const intervalId = setInterval(callback, delay, ...args);
        this.intervals.add(intervalId);
        return intervalId;
    }

    /**
     * addEventListener com cleanup automático
     */
    addEventListener(element, event, handler, options) {
        if (!element) return;
        
        element.addEventListener(event, handler, options);
        this.listeners.add({ element, event, handler, options });
    }

    /**
     * Limpa um timer específico
     */
    clearTimeout(timerId) {
        clearTimeout(timerId);
        this.timers.delete(timerId);
    }

    /**
     * Limpa um interval específico
     */
    clearInterval(intervalId) {
        clearInterval(intervalId);
        this.intervals.delete(intervalId);
    }

    /**
     * Remove um listener específico
     */
    removeEventListener(element, event, handler, options) {
        if (!element) return;
        
        element.removeEventListener(event, handler, options);
        this.listeners = new Set(
            Array.from(this.listeners).filter(l => 
                l.element !== element || l.event !== event || l.handler !== handler
            )
        );
    }

    /**
     * Salva estado do módulo
     */
    setState(key, value) {
        this.state[key] = value;
    }

    /**
     * Recupera estado do módulo
     */
    getState(key) {
        return this.state[key];
    }

    /**
     * Limpa todos os recursos deste módulo
     */
    cleanup() {
        // Aborta requests pendentes
        this.abortController.abort();
        this.abortController = new AbortController();

        // Limpa timers
        this.timers.forEach(timerId => clearTimeout(timerId));
        this.timers.clear();

        // Limpa intervals
        this.intervals.forEach(intervalId => clearInterval(intervalId));
        this.intervals.clear();

        // Remove event listeners
        this.listeners.forEach(({ element, event, handler, options }) => {
            if (element && typeof element.removeEventListener === 'function') {
                element.removeEventListener(event, handler, options);
            }
        });
        this.listeners.clear();

        // Preserva estado para restauração
        console.log(`[${this.moduleName}] Cleanup completo - ${this.timers.size} timers, ${this.intervals.size} intervals, ${this.listeners.size} listeners removidos`);
    }

    /**
     * Limpa um módulo específico
     */
    static cleanup(moduleName) {
        const instance = this.instances.get(moduleName);
        if (instance) {
            instance.cleanup();
        }
    }

    /**
     * Limpa todos os módulos
     */
    static cleanupAll() {
        this.instances.forEach((instance, moduleName) => {
            console.log(`[CleanupManager] Limpando módulo: ${moduleName}`);
            instance.cleanup();
        });
    }

    /**
     * Limpa módulos exceto o especificado (útil ao trocar de aba)
     */
    static cleanupExcept(exceptModule) {
        this.instances.forEach((instance, moduleName) => {
            if (moduleName !== exceptModule) {
                instance.cleanup();
            }
        });
    }
}

// Auto-cleanup ao sair da página
if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
        CleanupManager.cleanupAll();
    });
}

// Export para uso global
if (typeof window !== 'undefined') {
    window.CleanupManager = CleanupManager;
}
