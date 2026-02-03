# Sistema de Controle de Acesso

**Versao:** 2.0.0 (Build 1.1.0)  
**Stack:** PHP 8.2 + PostgreSQL 15  
**Empresa:** Renner Coatings

---

## Sobre o Sistema

Sistema completo de controle de acesso corporativo para gestao de entrada e saida de profissionais, visitantes e prestadores de servico. Desenvolvido com foco em seguranca, conformidade LGPD e facilidade de uso.

---

## Funcionalidades Principais

### Modulos de Acesso
- **Profissionais Renner** - Registro de funcionarios com foto, CPF e validacao de duplicidade
- **Visitantes** - Cadastro e controle de visitantes com pre-cadastro valido por 1 ano
- **Prestadores de Servico** - Gestao de terceirizados com documentacao internacional

### Pre-Cadastros v2.0
- Sistema de pre-registro com validade de 1 ano
- Renovacao automatica para visitantes/prestadores recorrentes
- Separacao entre dados cadastrais e eventos de acesso
- Validacao de duplicidade em tempo real

### Modulo Ramais
- Lista telefonica interna completa
- Importacao via Excel/CSV
- Interface publica somente leitura
- Edicao restrita a RH/Administradores

### Dashboard
- Visao geral de acessos ativos
- Cards coloridos por categoria
- Identificacao visual de brigadistas
- Calendario e relogio em tempo real

### Relatorios
- Exportacao CSV com protecao contra injecao de formulas
- Filtros avancados por periodo, tipo e pessoa
- Mascaramento LGPD para dados sensiveis
- Campo de observacoes para todos os tipos de entrada

### Seguranca
- Autenticacao por email e senha com hash
- RBAC (Role-Based Access Control)
- Protecao CSRF em todos os formularios
- Log de auditoria completo
- Conformidade LGPD

---

## Requisitos do Sistema

### Servidor
- **PHP:** 8.0 ou superior
- **PostgreSQL:** 12 ou superior
- **Extensoes PHP:** pdo_pgsql, mbstring, json, session
- **Memoria:** 2GB RAM minimo
- **Disco:** 10GB disponivel

### Opcional (Docker)
- Docker 20.10+
- Docker Compose 1.29+

---

## Instalacao

### Opcao 1: Instalacao Direta

```bash
# Clonar repositorio
git clone https://github.com/gabrimsilva/sistema-portaria.git
cd sistema-portaria

# Configurar banco de dados
cp config/database.example.php config/database.php
nano config/database.php

# Executar migracoes
php scripts/migrate.php

# Configurar permissoes
chmod -R 755 public/
chmod -R 777 uploads/
```

### Opcao 2: Docker

```bash
# Subir containers
docker-compose up -d --build

# Aguardar inicializacao
docker ps
```

---

## Configuracao do Servidor Web

### Apache
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    DocumentRoot /var/www/sistema-portaria/public
    
    <Directory /var/www/sistema-portaria/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /var/www/sistema-portaria/public;
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

---

## Credenciais Padrao

| Campo | Valor |
|-------|-------|
| **Email** | admin@sistema.com |
| **Senha** | admin123 |

**IMPORTANTE:** Altere a senha apos o primeiro login!

---

## Estrutura do Projeto

```
/
├── config/               # Configuracoes do sistema
│   ├── database.php      # Conexao com banco
│   ├── config.php        # Configuracoes gerais
│   └── migrations/       # Scripts de migracao
├── public/               # Arquivos publicos (DocumentRoot)
│   ├── router.php        # Roteador principal
│   ├── api/              # Endpoints da API
│   ├── assets/           # CSS, JS, imagens
│   └── uploads/          # Fotos capturadas
├── src/                  # Codigo fonte
│   ├── controllers/      # Controladores MVC
│   ├── models/           # Modelos de dados
│   └── services/         # Servicos do sistema
├── views/                # Templates PHP
│   ├── dashboard/        # Tela principal
│   ├── reports/          # Relatorios
│   ├── ramais/           # Modulo de ramais
│   └── privacy/          # Portal LGPD
├── scripts/              # Scripts utilitarios
│   └── migrate.php       # Migracao de banco
├── DEPLOY_GUIDE.md       # Guia de deploy
└── README.md             # Este arquivo
```

---

## Novidades da Versao 2.0

### Funcionalidades
- Pre-Cadastros v2.0 com validade de 1 ano
- Modulo Ramais com importacao Excel
- Validacao de duplicidade para profissionais
- Campo de observacoes em todos os registros
- Suporte a 8 tipos de documentos internacionais
- Calendario e relogio no sidebar

### Melhorias Tecnicas
- Separacao de tabelas cadastro/registro
- 18 indices especializados para performance
- Sistema de higiene UX (prevencao de memory leaks)
- Exportacao CSV com protecao contra formulas
- Log de auditoria com campos adicionais

### Seguranca
- Deteccao de entradas retroativas
- Justificativa obrigatoria para retroativos
- Mascaramento LGPD em exportacoes
- Validacao de documentos por tipo

---

## Backup e Restauracao

### Backup do Banco
```bash
pg_dump -U usuario -h localhost banco > backup_$(date +%Y%m%d).sql
```

### Backup de Uploads
```bash
tar -czvf uploads_backup.tar.gz uploads/
```

### Restaurar Backup
```bash
psql -U usuario -h localhost banco < backup.sql
tar -xzvf uploads_backup.tar.gz
```

---

## Atualizacao do Sistema

```bash
# No servidor
cd /var/www/sistema-portaria
git pull origin main
php scripts/migrate.php
```

---

## Suporte

### Logs do Sistema
- **PHP:** /var/log/php/error.log
- **Apache:** /var/log/apache2/error.log
- **Aplicacao:** logs/app.log

### Verificacoes
1. Conexao com banco de dados
2. Permissoes de diretorios
3. Extensoes PHP instaladas
4. Configuracao do servidor web

---

## Tecnologias

- **Backend:** PHP 8.2 com PDO
- **Banco de Dados:** PostgreSQL 15
- **Frontend:** AdminLTE 3, Bootstrap 4, jQuery
- **Bibliotecas:** PhpSpreadsheet, jQuery UI
- **Seguranca:** Password hashing, CSRF, XSS protection

---

## Licenca

Sistema proprietario - Renner Coatings  
Todos os direitos reservados.

---

**Versao 2.0.0** - Sistema pronto para producao!
