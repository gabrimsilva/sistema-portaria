# Guia de Atualização - Sistema de Controle de Acesso v2.0

## Informações do Repositório
- **GitHub**: https://github.com/marlontomaz/Renner-Gestor-Portaria2.0
- **Versão**: 1.1.0

---

## Pré-requisitos do Servidor

- PHP 8.0 ou superior
- PostgreSQL 12 ou superior
- Extensões PHP: pdo_pgsql, mbstring, json, session
- Composer (para dependências PHP)

---

## Passo a Passo para Atualização

### 1. Backup do Sistema Atual

```bash
# No servidor da versão 1, faça backup do banco de dados
pg_dump -U seu_usuario -h localhost nome_banco > backup_v1_$(date +%Y%m%d).sql

# Backup dos arquivos (fotos e uploads)
tar -czvf backup_uploads_$(date +%Y%m%d).tar.gz uploads/
```

### 2. Clone ou Atualize o Repositório

**Se for primeira instalação:**
```bash
cd /var/www/
git clone https://github.com/marlontomaz/Renner-Gestor-Portaria2.0.git sistema-acesso
cd sistema-acesso
```

**Se for atualização:**
```bash
cd /var/www/sistema-acesso
git fetch origin
git pull origin main
```

### 3. Instalar Dependências

```bash
composer install --no-dev --optimize-autoloader
```

### 4. Configurar Ambiente

Copie e edite o arquivo de configuração:

```bash
cp config/database.example.php config/database.php
nano config/database.php
```

Configure as variáveis de banco de dados:
```php
<?php
return [
    'host' => 'localhost',
    'port' => '5432',
    'database' => 'seu_banco',
    'username' => 'seu_usuario',
    'password' => 'sua_senha'
];
```

Ou configure via variável de ambiente DATABASE_URL:
```bash
export DATABASE_URL="postgresql://usuario:senha@localhost:5432/nome_banco"
```

### 5. Executar Migrações do Banco de Dados

```bash
php scripts/migrate.php
```

### 6. Configurar Permissões

```bash
chmod -R 755 public/
chmod -R 777 uploads/
chmod -R 777 logs/
```

### 7. Configurar Apache/Nginx

**Apache (.htaccess já incluído em /public)**

Configuração do VirtualHost:
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    DocumentRoot /var/www/sistema-acesso/public
    
    <Directory /var/www/sistema-acesso/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /var/www/sistema-acesso/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /router.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 8. Reiniciar Serviços

```bash
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

---

## Estrutura de Diretórios

```
/
├── config/           # Configurações do sistema
├── public/           # Arquivos públicos (DocumentRoot)
│   ├── router.php    # Roteador principal
│   ├── css/          # Estilos
│   ├── js/           # JavaScript
│   └── uploads/      # Fotos e arquivos
├── src/              # Código fonte
│   ├── controllers/  # Controladores MVC
│   ├── models/       # Modelos
│   └── services/     # Serviços
├── views/            # Templates PHP
├── vendor/           # Dependências (Composer)
└── scripts/          # Scripts de migração
```

---

## Credenciais Padrão

| Usuário | Senha |
|---------|-------|
| admin@sistema.com | admin123 |

**IMPORTANTE:** Altere a senha após o primeiro login!

---

## Novas Funcionalidades da v2.0

1. **Pré-Cadastros v2.0** - Sistema de pré-registro com validade de 1 ano
2. **Módulo Ramais** - Lista telefônica interna com importação Excel
3. **Validação de Duplicidade** - Previne entradas duplicadas
4. **Campo de Observações** - Para todos os tipos de entrada
5. **Documentos Internacionais** - Suporte a 8 tipos de documentos
6. **Calendário/Relógio no Sidebar** - Widgets de data e hora
7. **Exportação CSV** - Com proteção contra injeção de fórmulas

---

## Suporte

Em caso de problemas, verifique:
1. Logs do PHP: `/var/log/php/error.log`
2. Logs do Apache: `/var/log/apache2/error.log`
3. Permissões dos diretórios
4. Conexão com banco de dados

---

## Migração de Dados (v1 para v2)

Se você tem dados na versão 1 que precisam ser migrados, execute:

```bash
php scripts/migrate_v1_to_v2.php
```

Este script irá:
- Criar novas tabelas necessárias
- Migrar dados existentes
- Atualizar estrutura do banco
