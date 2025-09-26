<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 40px 30px;
            text-align: center;
            position: relative;
            border-bottom: 3px solid #e9ecef;
        }
        
        .company-logo {
            width: 100%;
            max-width: 350px;
            height: 120px;
            background: white;
            border-radius: 12px;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 2px solid #f0f0f0;
        }
        
        .company-logo img {
            width: 320px;
            height: auto;
            max-height: 100px;
            object-fit: contain;
        }
        
        .system-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .system-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 5px 0 0 0;
            font-weight: 300;
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
                font-size: 20px;
            }
            
            .company-logo {
                width: 280px;
                height: 90px;
                margin-bottom: 20px;
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
                    <img src="/logo-renner.png" alt="Renner Coatings" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <i class="fas fa-building" style="font-size: 35px; color: #dc3545; display: none;"></i>
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
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário -->
                <form method="post" id="loginForm">
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
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Entrar
                    </button>
                </form>
                
                <!-- Link esqueci senha -->
                <div class="forgot-password">
                    <a href="#" onclick="showForgotPassword()">
                        <i class="fas fa-key me-1"></i>
                        Esqueci minha senha
                    </a>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
        // Animação de loading no botão
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Entrando...';
        });
        
        // Função para esqueci senha (placeholder)
        function showForgotPassword() {
            alert('Funcionalidade de recuperação de senha será implementada em breve.\n\nPara acesso de demonstração, use:\nEmail: admin@sistema.com\nSenha: admin123');
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
</body>
</html>