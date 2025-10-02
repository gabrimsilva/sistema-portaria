# 🚀 Sistema de Controle de Acesso - Deploy Docker

**Versão:** 1.0.0  
**Stack:** PHP 8.2 + Apache + PostgreSQL 15  
**Empresa:** Renner Coatings

---

## 📋 Requisitos

- **Sistema Operacional:** Ubuntu 20.04+ (Desktop ou Server)
- **Docker:** 20.10+
- **Docker Compose:** 1.29+
- **RAM:** 2GB mínimo
- **Disco:** 10GB disponível
- **Portas:** 8080 e 5432 livres

---

## ⚡ Deploy Rápido (5 minutos)

### **Passo 1: Atualizar sistema**
```bash
sudo apt update && sudo apt upgrade -y
```

### **Passo 2: Instalar Docker + Docker Compose**
```bash
# Instalar Docker
sudo apt install -y docker.io docker-compose

# Habilitar Docker
sudo systemctl enable docker
sudo systemctl start docker

# Adicionar usuário ao grupo docker (opcional, evita usar sudo)
sudo usermod -aG docker $USER
newgrp docker
```

### **Passo 3: Descompactar aplicação**
```bash
unzip controle-portaria.zip -d /opt/controle-portaria
cd /opt/controle-portaria
```

### **Passo 4: Subir containers**
```bash
sudo docker-compose up -d --build
```

⏳ **Aguarde ~2-3 minutos** para build e inicialização completa.

### **Passo 5: Verificar status**
```bash
sudo docker ps
```

**✅ Saída esperada:**
```
CONTAINER ID   IMAGE                        STATUS          PORTS
xxxxx          controle-portaria-app        Up (healthy)    0.0.0.0:8080->80/tcp
xxxxx          postgres:15-alpine           Up (healthy)    0.0.0.0:5432->5432/tcp
```

### **Passo 6: Acessar aplicação**

**Localhost:**
```
http://localhost:8080
```

**IP da VM:**
```
http://<IP-DA-VM>:8080
```

### **Passo 7: Login inicial**
```
📧 Email: gmsilva@rennercoatings.com
🔑 Senha: rhsa@2019
```

⚠️ **IMPORTANTE: ALTERE A SENHA IMEDIATAMENTE APÓS PRIMEIRO LOGIN!**

---

## 🔧 Comandos Úteis

### Ver logs em tempo real
```bash
# Aplicação
sudo docker logs -f controle-portaria-app

# Banco de dados
sudo docker logs -f controle-portaria-db

# Ambos
sudo docker-compose logs -f
```

### Reiniciar serviços
```bash
# Reiniciar todos
sudo docker-compose restart

# Reiniciar apenas app
sudo docker-compose restart app

# Reiniciar apenas banco
sudo docker-compose restart db
```

### Parar aplicação
```bash
sudo docker-compose down
```

### Parar e remover volumes (CUIDADO: apaga banco!)
```bash
sudo docker-compose down -v
```

### Atualizar aplicação
```bash
# Parar containers
sudo docker-compose down

# Rebuild com novas mudanças
sudo docker-compose up -d --build
```

---

## 💾 Backup e Restauração

### Backup do banco
```bash
# Criar backup com timestamp
sudo docker exec controle-portaria-db pg_dump -U postgres access_control > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup compactado
sudo docker exec controle-portaria-db pg_dump -U postgres access_control | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Restaurar backup
```bash
# De arquivo SQL
cat backup.sql | sudo docker exec -i controle-portaria-db psql -U postgres access_control

# De arquivo compactado
gunzip -c backup.sql.gz | sudo docker exec -i controle-portaria-db psql -U postgres access_control
```

### Backup de uploads (fotos)
```bash
tar -czf uploads_backup_$(date +%Y%m%d_%H%M%S).tar.gz uploads/
```

---

## 🛡️ Segurança

### Alterar senha do banco
1. Edite `docker-compose.yml`:
```yaml
environment:
  - PGPASSWORD=SUA_NOVA_SENHA_FORTE
  - POSTGRES_PASSWORD=SUA_NOVA_SENHA_FORTE
```

2. Recrie containers:
```bash
sudo docker-compose down
sudo docker-compose up -d --force-recreate
```

### Configurar Firewall (UFW)
```bash
# Permitir apenas porta 8080
sudo ufw allow 8080/tcp
sudo ufw enable

# Verificar status
sudo ufw status
```

### SSL/HTTPS com Nginx Reverse Proxy
```bash
# Instalar Nginx
sudo apt install -y nginx certbot python3-certbot-nginx

# Configurar proxy reverso
sudo nano /etc/nginx/sites-available/controle-portaria

# Conteúdo:
server {
    listen 80;
    server_name seu-dominio.com;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

# Ativar site
sudo ln -s /etc/nginx/sites-available/controle-portaria /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Obter certificado SSL
sudo certbot --nginx -d seu-dominio.com
```

---

## 📊 Monitoramento

### Status dos containers
```bash
sudo docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

### Uso de recursos
```bash
# Em tempo real
sudo docker stats

# Disco usado
sudo docker system df
```

### Verificar saúde (healthcheck)
```bash
sudo docker inspect controle-portaria-app | grep -A 10 Health
sudo docker inspect controle-portaria-db | grep -A 10 Health
```

---

## ❌ Troubleshooting

### Porta 8080 já em uso
```bash
# Identificar processo
sudo lsof -i :8080

# Matar processo
sudo kill -9 <PID>
```

### Banco não inicializa
```bash
# Ver logs detalhados
sudo docker logs controle-portaria-db

# Resetar banco (CUIDADO: apaga dados!)
sudo docker-compose down -v
sudo docker-compose up -d
```

### Permissões de arquivo
```bash
sudo chown -R www-data:www-data uploads storage logs
sudo chmod -R 775 uploads storage logs
```

### Rebuild completo (limpar cache)
```bash
sudo docker-compose down
sudo docker system prune -a
sudo docker-compose up -d --build
```

### App não responde
```bash
# Verificar logs
sudo docker logs controle-portaria-app

# Entrar no container
sudo docker exec -it controle-portaria-app bash

# Testar internamente
curl http://localhost/login
```

---

## 🔍 Acessar Shell dos Containers

### Container da aplicação
```bash
sudo docker exec -it controle-portaria-app bash
```

### Container do banco
```bash
# Shell bash
sudo docker exec -it controle-portaria-db sh

# Acesso direto ao PostgreSQL
sudo docker exec -it controle-portaria-db psql -U postgres access_control
```

---

## 🎯 Próximos Passos

1. ✅ Alterar senha padrão do admin
2. ✅ Configurar backup automático (cron)
3. ✅ Configurar SSL/HTTPS
4. ✅ Configurar domínio customizado
5. ✅ Implementar monitoramento (Prometheus/Grafana)
6. ✅ Configurar notificações de erro

---

## 📁 Estrutura do Projeto

```
/opt/controle-portaria/
├── docker/
│   ├── init.sql              # Schema do banco (28 tabelas)
│   ├── seed.sql              # Dados iniciais (admin)
│   └── apache-config.conf    # Configuração Apache
├── public/                   # DocumentRoot
├── src/                      # Código PHP
├── config/                   # Configurações
├── views/                    # Templates
├── storage/                  # Arquivos temporários
├── uploads/                  # Fotos (LGPD protegido)
├── logs/                     # Logs da aplicação
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## 📞 Informações Técnicas

- **PHP:** 8.2 com PDO PostgreSQL
- **Servidor Web:** Apache 2.4 com mod_rewrite
- **Banco de Dados:** PostgreSQL 15
- **Timezone:** America/Sao_Paulo
- **Locale:** pt_BR
- **Logs:** `/var/log/apache2/` (container)

---

## 📖 Documentação Adicional

- **LGPD Compliance:** Proteção de dados biométricos implementada
- **Auditoria:** Todos os acessos registrados em `audit_log`
- **RBAC:** Sistema completo de permissões por perfil
- **Backup:** Retenção de dados configurável

---

## ⚙️ Variáveis de Ambiente

### Aplicação (docker-compose.yml)
```yaml
TZ=America/Sao_Paulo          # Timezone
PGHOST=db                      # Host do banco
PGPORT=5432                    # Porta do banco
PGDATABASE=access_control      # Nome do banco
PGUSER=postgres                # Usuário do banco
PGPASSWORD=renner@postgres2024 # Senha do banco
```

---

## 🆘 Suporte

**Sistema:** Controle de Acesso v1.0.0  
**Empresa:** Renner Coatings  
**Documentação completa:** `/docs`

Em caso de problemas críticos:
1. Verifique os logs: `sudo docker-compose logs`
2. Consulte a seção Troubleshooting
3. Entre em contato com o suporte técnico

---

**✅ Sistema pronto para produção!**
