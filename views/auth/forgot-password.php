<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            border: none;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
        }
        
        .forgot-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #007bff, #6f42c1, #e83e8c);
        }
        
        .forgot-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .forgot-icon i {
            font-size: 35px;
            color: white;
        }
        
        .forgot-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .forgot-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 5px 0 0 0;
            font-weight: 300;
        }
        
        .forgot-body {
            padding: 40px 30px;
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
            border-color: #17a2b8;
            box-shadow: 0 0 0 0.2rem rgba(23,162,184,0.25);
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
        
        .btn-forgot {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
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
        
        .btn-forgot:hover:not(:disabled) {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(23,162,184,0.3);
        }
        
        .btn-forgot:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .back-login {
            text-align: center;
        }
        
        .back-login a {
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-login a:hover {
            color: #495057;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <!-- Header -->
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h1 class="forgot-title">Esqueci Minha Senha</h1>
                <p class="forgot-subtitle">Recupere o acesso à sua conta</p>
            </div>
            
            <!-- Formulário -->
            <div class="forgot-body">
                <!-- Mensagens de feedback -->
                <div id="alertContainer"></div>
                
                <form id="forgotPasswordForm">
                    <!-- Email -->
                    <div class="form-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               id="email"
                               placeholder="Digite seu email" 
                               required>
                    </div>
                    
                    <!-- Informações -->
                    <div class="info-box">
                        <i class="fas fa-info-circle mr-2"></i>
                        Enviaremos as instruções de recuperação para o email informado
                    </div>
                    
                    <!-- Botão de envio -->
                    <button type="submit" class="btn btn-forgot" id="forgotBtn">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Instruções
                    </button>
                </form>
                
                <!-- Link de volta ao login -->
                <div class="back-login">
                    <a href="/login">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Voltar ao Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotPasswordForm');
            const emailInput = document.getElementById('email');
            const forgotBtn = document.getElementById('forgotBtn');
            
            // Submissão do formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                
                // Validações
                if (!email) {
                    showAlert('Por favor, digite seu email', 'danger');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    showAlert('Por favor, digite um email válido', 'danger');
                    return;
                }
                
                // Loading state
                forgotBtn.disabled = true;
                forgotBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
                
                // Enviar requisição
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
                    if (data.success) {
                        showAlert('Se uma conta com este email existir, você receberá as instruções de recuperação.', 'success');
                        // Limpar formulário
                        emailInput.value = '';
                        // Redirecionar após 3 segundos
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 3000);
                    } else {
                        showAlert(data.message || 'Erro ao enviar instruções', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showAlert('Erro de comunicação com o servidor', 'danger');
                })
                .finally(() => {
                    forgotBtn.disabled = false;
                    forgotBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Enviar Instruções';
                });
            });
            
            // Função para validar email
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Função para mostrar alertas
            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                `;
                alertContainer.innerHTML = alertHtml;
                
                // Auto-remover após 5 segundos
                setTimeout(() => {
                    const alert = alertContainer.querySelector('.alert');
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
            
            // Auto-focus no email
            emailInput.focus();
            
            // Validação em tempo real do email
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                if (email && !isValidEmail(email)) {
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#e9ecef';
                }
            });
        });
        
        // Efeitos visuais nos campos
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#17a2b8';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#6c757d';
            });
        });
    </script>
</body>
</html>