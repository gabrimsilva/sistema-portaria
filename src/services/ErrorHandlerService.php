<?php

/**
 * 🛡️ ErrorHandlerService - Centralização de Tratamento de Erros JavaScript
 * 
 * Versão simplificada para corrigir erro de sintaxe
 */
class ErrorHandlerService
{
    /**
     * Renderiza script completo de tratamento de erros
     * 
     * @param array $options Configurações opcionais
     * @return string JavaScript do ErrorHandler
     */
    public static function renderErrorHandlerScript($options = [])
    {
        $defaults = [
            'enableConsoleLogging' => ($_ENV['ENVIRONMENT'] ?? 'development') !== 'production',
            'enableGlobalHandlers' => true,
            'enableToasts' => true
        ];
        
        $config = array_merge($defaults, $options);
        $configJson = json_encode($config);
        
        return "
<script>
/**
 * ErrorHandler Global - Tratamento Centralizado de Erros JavaScript
 * Versão: 2.3.0
 */
window.ErrorHandler = {
    config: {$configJson},
    
    /**
     * Função principal de tratamento de erro
     */
    handle: function(error, context) {
        try {
            const normalizedError = this.normalizeError(error);
            const userMessage = this.getUserFriendlyMessage(context || 'unknown', normalizedError.message);
            
            if (this.config.enableConsoleLogging) {
                console.error('🚨 Erro capturado:', normalizedError);
            }
            
            if (this.config.enableToasts && typeof $ !== 'undefined' && $.fn.alert) {
                this.showBootstrapAlert(userMessage, 'warning');
            } else {
                alert(userMessage);
            }
        } catch (e) {
            console.error('Erro no ErrorHandler:', e);
        }
    },
    
    /**
     * Normaliza diferentes tipos de erro em formato padrão
     */
    normalizeError: function(error) {
        if (error instanceof Error) {
            return {
                name: error.name,
                message: error.message,
                stack: error.stack
            };
        }
        
        if (typeof error === 'string') {
            return {
                name: 'StringError',
                message: error,
                stack: null
            };
        }
        
        if (typeof error === 'object' && error !== null) {
            return {
                name: error.name || 'ObjectError',
                message: error.message || JSON.stringify(error),
                stack: error.stack || null
            };
        }
        
        return {
            name: 'UnknownError',
            message: String(error),
            stack: null
        };
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
    showBootstrapAlert: function(message, type) {
        type = type || 'warning';
        
        const alertHtml = '<div class=\"alert alert-' + type + ' alert-dismissible fade show\" role=\"alert\">' +
            '<i class=\"fas fa-exclamation-triangle mr-2\"></i>' +
            message +
            '<button type=\"button\" class=\"close\" data-dismiss=\"alert\">' +
            '<span>&times;</span>' +
            '</button>' +
            '</div>';
        
        var container = document.querySelector('.alert-container') || 
                       document.querySelector('.content-wrapper') || 
                       document.body;
        
        const alertDiv = document.createElement('div');
        alertDiv.innerHTML = alertHtml;
        container.insertBefore(alertDiv.firstElementChild, container.firstChild);
        
        setTimeout(function() {
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
    }
};

// Configurar handlers globais se habilitado
if (ErrorHandler.config.enableGlobalHandlers) {
    window.addEventListener('error', function(event) {
        ErrorHandler.handle(event.error || event.message, 'global');
    });
    
    window.addEventListener('unhandledrejection', function(event) {
        ErrorHandler.handle(event.reason, 'promise');
    });
}

if (ErrorHandler.config.enableConsoleLogging) {
    console.log('✅ ErrorHandler inicializado');
}
</script>
";
    }
    
    /**
     * Renderiza apenas limpeza de console para produção
     */
    public static function renderProductionConsoleCleanup()
    {
        if (($_ENV['ENVIRONMENT'] ?? 'development') === 'production') {
            return "
<script>
// Limpeza de console em produção
if (typeof console !== 'undefined') {
    ['log', 'warn', 'info', 'debug'].forEach(function(method) {
        console[method] = function() {};
    });
}
</script>
";
        }
        
        return '';
    }
    
    /**
     * Renderiza script completo (ErrorHandler + Limpeza Produção)
     */
    public static function renderComplete($options = [])
    {
        return self::renderErrorHandlerScript($options) . self::renderProductionConsoleCleanup();
    }
}