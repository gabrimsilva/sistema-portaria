<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            border: none;
        }
        
        .login-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            padding: 30px 30px 25px;
            text-align: center;
            position: relative;
            border-bottom: 3px solid #e9ecef;
        }
        
        .company-logo {
            width: 100%;
            max-width: 350px;
            height: 90px;
            background: white;
            border-radius: 12px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 2px solid #f0f0f0;
            padding: 10px;
        }
        
        .company-logo img {
            width: 320px;
            height: auto;
            max-height: 70px;
            object-fit: contain;
        }
        
        .system-info {
            text-align: center;
            margin-top: 15px;
        }
        
        .system-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 5px 0;
            letter-spacing: 0.3px;
        }
        
        .system-version {
            font-size: 13px;
            color: #6c757d;
            font-weight: 400;
            margin: 0;
            opacity: 0.85;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .login-description {
            text-align: center;
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-control {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0 20px 0 50px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
            background: white;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
            z-index: 10;
        }
        
        .remember-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
        }
        
        .form-check-input {
            margin-right: 8px;
            transform: scale(1.2);
        }
        
        .form-check-label {
            color: #6c757d;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }
        
        .forgot-password {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }
            
            .login-header {
                padding: 30px 15px 25px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .system-title {
                font-size: 17px;
            }
            
            .system-version {
                font-size: 12px;
            }
            
            .company-logo {
                width: 280px;
                height: 90px;
                margin-bottom: 15px;
            }
            
            .company-logo img {
                width: 260px;
                max-height: 70px;
            }
            
            .login-card {
                max-width: 95%;
            }
        }
        
        @media (max-width: 400px) {
            .company-logo {
                width: 240px;
                height: 80px;
            }
            
            .company-logo img {
                width: 220px;
                max-height: 60px;
            }
        }
        
        /* Loading animation */
        .btn-login.loading {
            pointer-events: none;
        }
        
        .btn-login.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            display: inline-block;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header com logo e título -->
            <div class="login-header">
                <div class="company-logo">
                    <?= LogoService::renderLogo('renner', 'login'); ?>
                </div>
                <div class="system-info">
                    <h1 class="system-title">Sistema de Controle de Acesso</h1>
                    <p class="system-version">Versão 1.1.0</p>
                </div>
            </div>
            
            <!-- Formulário de login -->
            <div class="login-body">
                <p class="login-description">Faça login para iniciar sua sessão</p>
                
                <!-- Mensagem de erro -->
                <?php 
                $error = $error ?? null;
                if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário -->
                <form method="post" id="loginForm">
                    <?php require_once __DIR__ . '/../../config/csrf.php'; ?>
                    <?= CSRFProtection::getHiddenInput() ?>
                    
                    <!-- Campo Email -->
                    <div class="form-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               id="email"
                               placeholder="Email" 
                               required 
                               autocomplete="email">
                    </div>
                    
                    <!-- Campo Senha -->
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Senha" 
                               required 
                               autocomplete="current-password">
                    </div>
                    
                    <!-- Lembrar de mim -->
                    <div class="remember-section">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="remember" 
                                   id="remember">
                            <label class="form-check-label" for="remember">
                                Lembrar de mim
                            </label>
                        </div>
                    </div>
                    
                    <!-- Botão de login -->
                    <button type="submit" class="btn btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Entrar
                    </button>
                </form>
                
                <!-- Link esqueci senha -->
                <div class="forgot-password">
                    <a href="#" onclick="showForgotPassword()">
                        <i class="fas fa-key mr-1"></i>
                        Esqueci minha senha
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
        // Animação de loading no botão
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Entrando...';
        });
        
        // Função para esqueci senha
        function showForgotPassword() {
            // Criar modal de recuperação de senha
            const modalHtml = `
                <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                            <div class="modal-header" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; border-radius: 15px 15px 0 0; border: none;">
                                <h5 class="modal-title" id="forgotPasswordModalLabel">
                                    <i class="fas fa-key mr-2"></i>
                                    Recuperar Senha
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true" style="color: white;">&times;</span></button>
                            </div>
                            <div class="modal-body" style="padding: 30px;">
                                <p class="text-center text-muted mb-4">
                                    Digite seu email para receber as instruções de recuperação de senha
                                </p>
                                
                                <form id="forgotPasswordForm">
                                    <div class="form-group mb-3">
                                        <label for="recoveryEmail" >Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text" style="background: #f8f9fa; border-radius: 8px 0 0 8px;">
                                                <i class="fas fa-envelope text-muted"></i>
                                            </span>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="recoveryEmail" 
                                                   name="email"
                                                   placeholder="seu-email@exemplo.com" 
                                                   required
                                                   style="border-radius: 0 8px 8px 0; border-left: none;">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <button type="submit" class="btn btn-primary" style="border-radius: 8px; padding: 12px; font-weight: 600;">
                                            <i class="fas fa-paper-plane mr-2"></i>
                                            Enviar Instruções
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        Lembrou da senha? 
                                        <a href="#" onclick="$('#forgotPasswordModal').modal('hide');" class="text-decoration-none">
                                            Voltar ao login
                                        </a>
                                    </small>
                                </div>
                                
                                <!-- Informações de demonstração -->
                                <div class="alert alert-info mt-4 mb-0" style="border-radius: 10px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle mr-2 text-info"></i>
                                        <div>
                                            <strong>Acesso de Demonstração</strong><br>
                                            <small>Email: admin@sistema.com | Senha: admin123</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal existente se houver
            const existingModal = document.getElementById('forgotPasswordModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Adicionar modal ao body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
            modal.show();
            
            // Configurar evento de submit do formulário
            document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                handlePasswordRecovery();
            });
            
            // Auto-focus no campo email quando modal abrir
            document.getElementById('forgotPasswordModal').addEventListener('shown.bs.modal', function() {
                document.getElementById('recoveryEmail').focus();
            });
        }
        
        // Função para processar recuperação de senha
        function handlePasswordRecovery() {
            const email = document.getElementById('recoveryEmail').value;
            const submitBtn = document.querySelector('#forgotPasswordForm button[type="submit"]');
            
            // Validar email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('Por favor, digite um email válido.', 'warning');
                return;
            }
            
            // Mostrar loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
            submitBtn.disabled = true;
            
            // Enviar requisição real para o backend
            fetch('/forgot-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                modal.hide();
                
                if (data.success) {
                    // Mostrar mensagem de sucesso
                    showSuccessMessage(email);
                } else {
                    showAlert(data.message || 'Erro ao enviar email de recuperação', 'warning');
                }
                
                // Restaurar botão
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Enviar Instruções';
                submitBtn.disabled = false;
            })
            .catch(error => {
                if (typeof ErrorHandler !== 'undefined') {
                    ErrorHandler.handle(error, 'fetch');
                } else {
                    console.error('Erro:', error);
                }
                showAlert('Erro de comunicação com o servidor', 'warning');
                
                // Fechar modal e restaurar botão
                const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
                modal.hide();
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Enviar Instruções';
                submitBtn.disabled = false;
            });
        }
        
        // Função para mostrar mensagem de sucesso
        function showSuccessMessage(email) {
            const successModalHtml = `
                <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 15px; border: none; text-align: center;">
                            <div class="modal-body" style="padding: 40px 30px;">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle" style="font-size: 64px; color: #28a745;"></i>
                                </div>
                                <h4 class="mb-3">Email Enviado!</h4>
                                <p class="text-muted mb-4">
                                    Enviamos as instruções de recuperação de senha para:<br>
                                    <strong>${email}</strong>
                                </p>
                                <p class="text-muted small mb-4">
                                    Verifique sua caixa de entrada e spam. O email pode levar alguns minutos para chegar.
                                </p>
                                <button type="button" class="btn btn-primary" data-dismiss="modal" style="border-radius: 8px; padding: 10px 30px;">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar ao Login
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal existente se houver
            const existingModal = document.getElementById('successModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Adicionar e mostrar modal
            document.body.insertAdjacentHTML('beforeend', successModalHtml);
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        }
        
        // Função para mostrar alertas
        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; border-radius: 8px;">
                    <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-remover após 5 segundos
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Auto-focus no campo email
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Validação em tempo real
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#dc3545';
                this.style.background = '#fff5f5';
            } else {
                this.style.borderColor = '#e9ecef';
                this.style.background = '#f8f9fa';
            }
        });
        
        // Efeitos visuais nos campos
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#007bff';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#6c757d';
            });
        });
        
        // Animação de entrada
        window.addEventListener('load', function() {
            document.querySelector('.login-card').style.animation = 'slideInUp 0.6s ease-out';
        });
        
        // CSS para animação de entrada
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <?php
    // Incluir rodapé LGPD
    require_once __DIR__ . '/../components/lgpd-footer.php';
    
    // Banner de Cookies LGPD
    require_once BASE_PATH . '/src/services/CookieService.php';
    CookieService::includeBanner();
    ?>
</body>
</html>