<?php

/**
 * 🛡️ ErrorHandlerService - Centralização de Tratamento de Erros JavaScript
 * 
 * Padroniza o tratamento de erros JavaScript no frontend,
 * removendo console logs desnecessários e criando error handling robusto.
 * 
 * @author Sistema de Controle de Acesso
 * @version 1.6.0
 */
class ErrorHandlerService
{
    /**
     * Renderiza o script de inicialização do ErrorHandler
     * 
     * @param array $options Configurações do error handler
     * @return string Script HTML pronto para inserção
     */
    public static function renderErrorHandlerScript($options = [])
    {
        $isDevelopment = (!isset($_ENV['ENVIRONMENT']) || $_ENV['ENVIRONMENT'] !== 'production');
        $enableConsoleLog = $options['enableConsoleLog'] ?? $isDevelopment;
        $enableAlerts = $options['enableAlerts'] ?? false;
        
        // JSON-encode config para evitar problemas de sintaxe
        $config = json_encode([
            'enableConsoleLog' => $enableConsoleLog,
            'enableAlerts' => $enableAlerts,
            'isDevelopment' => $isDevelopment,
            'errorCount' => 0,
            'maxErrors' => 5
        ]);

        return "<script>
window.ErrorHandler = {
    config: {$config},
    
    /**
     * Trata erros de forma padronizada
     */
    handle: function(error, context = 'unknown', showUser = false) {
        this.config.errorCount++;
        
        // Prevenir spam de erros
        if (this.config.errorCount > this.config.maxErrors) {
            return;
        }
        
        // Garantir que temos um objeto de erro válido
        let errorObj = this.normalizeError(error);
        
        // Log apenas em desenvolvimento
        if (this.config.enableConsoleLog) {
            console.group('🚨 Erro JavaScript - ' + context);
            console.error('Mensagem:', errorObj.message);
            console.error('Stack:', errorObj.stack);
            console.error('Contexto:', context);
            console.groupEnd();
        }
        
        // Mostrar para usuário se solicitado
        if (showUser && this.config.enableAlerts) {
            this.showUserFriendlyError(errorObj, context);
        }
        
        return errorObj;
    },
    
    /**
     * Normaliza qualquer tipo de erro em objeto Error válido
     */
    normalizeError: function(error) {
        if (error instanceof Error) {
            return error;
        }
        
        if (typeof error === 'string') {
            return new Error(error);
        }
        
        if (error && typeof error === 'object') {
            const message = error.message || error.msg || error.error || 'Erro desconhecido';
            return new Error(message);
        }
        
        return new Error('Erro não identificado');
    },
    
    /**
     * Mostra erro amigável para o usuário
     */
    showUserFriendlyError: function(error, context) {
        const userMessage = this.getUserFriendlyMessage(context, error.message);
        
        // Se Bootstrap está disponível, usar toast/alert
        if (typeof $ !== 'undefined' && $.fn.alert) {
            this.showBootstrapAlert(userMessage, 'warning');
        } else {
            alert(userMessage);
        }
    },
    
    /**
     * Converte erros técnicos em mensagens amigáveis
     */
    getUserFriendlyMessage: function(context, technicalMessage) {
        const friendlyMessages = {
            'sessionStorage': 'Problema temporário com armazenamento local. Atualize a página.',
            'fetch': 'Erro de conexão. Verifique sua internet e tente novamente.',
            'camera': 'Erro ao acessar a câmera. Verifique as permissões.',
            'validation': 'Dados inválidos. Verifique os campos e tente novamente.',
            'unknown': 'Erro inesperado. Atualize a página e tente novamente.'
        };
        
        return friendlyMessages[context] || friendlyMessages['unknown'];
    },
    
    /**
     * Mostra alert usando Bootstrap se disponível
     */
    showBootstrapAlert: function(message, type = 'warning') {
        const alertHtml = \`
            <div class="alert alert-\${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                \${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        \`;
        
        // Procurar container para alertas
        let container = document.querySelector('.alert-container');
        if (!container) {
            container = document.querySelector('.content-wrapper');
        }
        if (!container) {
            container = document.body;
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertHtml;
        container.insertBefore(alertDiv.firstElementChild, container.firstChild);
        
        // Auto-remover após 5 segundos
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    },
    
    /**
     * Wrapper seguro para sessionStorage
     */
    safeSessionStorage: {
        setItem: function(key, value) {
            try {
                sessionStorage.setItem(key, value);
                return true;
            } catch (error) {
                ErrorHandler.handle(error, 'sessionStorage');
                return false;
            }
        },
        
        getItem: function(key) {
            try {
                return sessionStorage.getItem(key);
            } catch (error) {
                ErrorHandler.handle(error, 'sessionStorage');
                return null;
            }
        },
        
        removeItem: function(key) {
            try {
                sessionStorage.removeItem(key);
                return true;
            } catch (error) {
                ErrorHandler.handle(error, 'sessionStorage');
                return false;
            }
        }
    },
    
    /**
     * Wrapper seguro para fetch
     */
    safeFetch: async function(url, options = {}) {
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(\`HTTP Error: \${response.status} - \${response.statusText}\`);
            }
            
            return response;
        } catch (error) {
            ErrorHandler.handle(error, 'fetch');
            throw error;
        }
    },
    
    /**
     * Safe stringify que nunca falha
     */
    safeStringify: function(value) {
        try {
            // Tentar JSON.stringify primeiro
            return JSON.stringify(value);
        } catch (error) {
            try {
                // Fallback para String() com detalhes de tipo
                return \`[Type: \${typeof value}] \${String(value)}\`;
            } catch (error2) {
                // Último recurso - apenas o tipo
                return \`[Unserializable \${typeof value}]\`;
            }
        }
    }
};

// Configurar handler global para erros não capturados (incluindo non-Error objects)
window.addEventListener('error', function(event) {
    let errorToHandle = event.error || event.message || 'Unknown error';
    
    // Tratar especificamente o caso de non-Error objects
    if (!(errorToHandle instanceof Error)) {
        if (ErrorHandler.config.enableConsoleLog) {
            console.warn('🚨 Non-Error object detected:', typeof errorToHandle);
        }
        errorToHandle = new Error('Non-Error thrown: ' + ErrorHandler.safeStringify(errorToHandle));
    }
    
    ErrorHandler.handle(errorToHandle, 'global');
});

// Configurar handler para promises rejeitadas
window.addEventListener('unhandledrejection', function(event) {
    let reason = event.reason;
    
    // Normalizar non-Error objects em promises
    if (!(reason instanceof Error)) {
        if (ErrorHandler.config.enableConsoleLog) {
            console.warn('🚨 Promise rejected with non-Error:', typeof reason);
        }
        reason = new Error('Promise rejection (non-Error): ' + ErrorHandler.safeStringify(reason));
    }
    
    ErrorHandler.handle(reason, 'promise');
    event.preventDefault(); // Prevenir o log padrão
});


// Inicialização
if (ErrorHandler.config.enableConsoleLog) {
    console.log('✅ ErrorHandler inicializado (Desenvolvimento: ' + ErrorHandler.config.isDevelopment + ')');
}
</script>
HTML;
    }
    
    /**
     * Renderiza script específico para limpeza de console em produção
     */
    public static function renderProductionConsoleCleanup()
    {
        $isProduction = (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'production');
        
        if (!$isProduction) {
            return '';
        }
        
        return <<<'HTML'
<script>
// 🚫 Limpeza de Console para Produção
(function() {
    if (typeof console !== 'undefined') {
        const methods = ['log', 'debug', 'info', 'warn'];
        methods.forEach(function(method) {
            console[method] = function() {};
        });
    }
})();
</script>
HTML;
    }
    
    /**
     * Renderiza script completo de error handling
     */
    public static function renderComplete($options = [])
    {
        return self::renderErrorHandlerScript($options) . self::renderProductionConsoleCleanup();
    }
}