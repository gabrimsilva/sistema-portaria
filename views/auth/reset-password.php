<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            border: none;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
        }
        
        .reset-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8, #007bff);
        }
        
        .reset-icon {
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
        
        .reset-icon i {
            font-size: 35px;
            color: white;
        }
        
        .reset-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .reset-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 5px 0 0 0;
            font-weight: 300;
        }
        
        .reset-body {
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
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
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
        
        .btn-reset {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        
        .btn-reset:hover:not(:disabled) {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40,167,69,0.3);
        }
        
        .btn-reset:disabled {
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
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
            color: #6c757d;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement i {
            margin-right: 8px;
            width: 16px;
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
    <div class="reset-container">
        <div class="reset-card">
            <!-- Header -->
            <div class="reset-header">
                <div class="reset-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="reset-title">Redefinir Senha</h1>
                <p class="reset-subtitle">Defina sua nova senha</p>
            </div>
            
            <!-- Formulário -->
            <div class="reset-body">
                <!-- Mensagens de feedback -->
                <div id="alertContainer"></div>
                
                <form id="resetPasswordForm">
                    <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
                    
                    <!-- Nova Senha -->
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               placeholder="Nova senha" 
                               required 
                               minlength="6">
                    </div>
                    
                    <!-- Confirmar Senha -->
                    <div class="form-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               class="form-control" 
                               name="confirm_password" 
                               id="confirmPassword"
                               placeholder="Confirmar nova senha" 
                               required 
                               minlength="6">
                    </div>
                    
                    <!-- Requisitos de senha -->
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">
                            <i class="fas fa-times"></i>
                            <span>Pelo menos 6 caracteres</span>
                        </div>
                        <div class="requirement" id="req-match">
                            <i class="fas fa-times"></i>
                            <span>As senhas devem coincidir</span>
                        </div>
                    </div>
                    
                    <!-- Botão de redefinição -->
                    <button type="submit" class="btn btn-reset" id="resetBtn">
                        <i class="fas fa-check me-2"></i>
                        Redefinir Senha
                    </button>
                </form>
                
                <!-- Link de volta ao login -->
                <div class="back-login">
                    <a href="/login">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar ao Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('resetPasswordForm');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirmPassword');
            const resetBtn = document.getElementById('resetBtn');
            
            // Validação em tempo real
            function validatePassword() {
                const password = passwordInput.value;
                const confirmPassword = confirmInput.value;
                
                // Validar comprimento
                const lengthReq = document.getElementById('req-length');
                if (password.length >= 6) {
                    lengthReq.classList.add('valid');
                    lengthReq.querySelector('i').className = 'fas fa-check';
                } else {
                    lengthReq.classList.remove('valid');
                    lengthReq.querySelector('i').className = 'fas fa-times';
                }
                
                // Validar correspondência
                const matchReq = document.getElementById('req-match');
                if (password && confirmPassword && password === confirmPassword) {
                    matchReq.classList.add('valid');
                    matchReq.querySelector('i').className = 'fas fa-check';
                } else {
                    matchReq.classList.remove('valid');
                    matchReq.querySelector('i').className = 'fas fa-times';
                }
                
                // Habilitar/desabilitar botão
                const isValid = password.length >= 6 && password === confirmPassword;
                resetBtn.disabled = !isValid;
            }
            
            passwordInput.addEventListener('input', validatePassword);
            confirmInput.addEventListener('input', validatePassword);
            
            // Submissão do formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const password = passwordInput.value;
                const confirmPassword = confirmInput.value;
                const token = document.getElementById('token').value;
                
                // Validações finais
                if (password.length < 6) {
                    showAlert('A senha deve ter pelo menos 6 caracteres', 'danger');
                    return;
                }
                
                if (password !== confirmPassword) {
                    showAlert('As senhas não coincidem', 'danger');
                    return;
                }
                
                // Loading state
                resetBtn.disabled = true;
                resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Redefinindo...';
                
                // Enviar requisição
                fetch('/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token: token,
                        password: password,
                        confirm_password: confirmPassword
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Senha redefinida com sucesso! Redirecionando...', 'success');
                        setTimeout(() => {
                            window.location.href = '/login?reset=success';
                        }, 2000);
                    } else {
                        showAlert(data.message || 'Erro ao redefinir senha', 'danger');
                        resetBtn.disabled = false;
                        resetBtn.innerHTML = '<i class="fas fa-check me-2"></i>Redefinir Senha';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showAlert('Erro de comunicação com o servidor', 'danger');
                    resetBtn.disabled = false;
                    resetBtn.innerHTML = '<i class="fas fa-check me-2"></i>Redefinir Senha';
                });
            });
            
            // Função para mostrar alertas
            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
            
            // Auto-focus na senha
            passwordInput.focus();
            
            // Verificar se token está presente
            const token = document.getElementById('token').value;
            if (!token) {
                showAlert('Token de redefinição não encontrado', 'danger');
                resetBtn.disabled = true;
            }
        });
        
        // Efeitos visuais nos campos
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#28a745';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#6c757d';
            });
        });
    </script>
</body>
</html>