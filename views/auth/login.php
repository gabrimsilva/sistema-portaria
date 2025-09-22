<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Controle de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="hold-transition login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1 class="h4"><b>Sistema de Controle</b><br>de Acesso</h1>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Faça login para iniciar sua sessão</p>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Senha" required>
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Lembrar de mim</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                        </div>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p class="mb-1">
                        <a href="#" class="text-decoration-none">Esqueci minha senha</a>
                    </p>
                    <p class="text-muted small">
                        Usuário padrão: admin@sistema.com<br>
                        Senha padrão: admin123
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>