# 📋 CHECKLIST: Deploy do Painel de Brigada na VM 10.3.1.135

## ✅ Pré-requisitos
- [ ] VM 10.3.1.135 com Apache ou Nginx instalado
- [ ] PHP 8.0+ com extensões: PDO, pdo_pgsql, mbstring
- [ ] Acesso SSH à VM
- [ ] Banco PostgreSQL acessível da VM

---

## 🔧 ETAPA 1: Configurar Secrets na VM

### 1.1 Criar arquivo de ambiente
```bash
# Na VM 10.3.1.135
cd /var/www/html  # ou diretório do projeto
nano .env
```

### 1.2 Adicionar variáveis (SUBSTITUA os valores reais):
```bash
PANEL_BRG_KEY=SUA_CHAVE_SECRETA_FORTE_AQUI
DATABASE_URL=postgresql://user:pass@host:5432/database

# NÃO ADICIONAR: PANEL_BRG_DEV_MODE (somente para testes locais)
```

**⚠️ IMPORTANTE:** 
- `PANEL_BRG_KEY` deve ser uma string aleatória forte (ex: `openssl rand -base64 32`)
- **NUNCA** use `PANEL_BRG_DEV_MODE=1` em produção

---

## 🌐 ETAPA 2: Configurar Apache/Nginx

### Opção A: Apache (VirtualHost)
```apache
<VirtualHost *:80>
    ServerName painel-brigada.empresa.local
    DocumentRoot /var/www/html/public
    
    <Location /painel/brigada>
        # Injetar header X-Panel-Key automaticamente
        RequestHeader set X-Panel-Key "SUA_CHAVE_SECRETA_FORTE_AQUI"
    </Location>
    
    <Location /api/brigada>
        # Injetar header nas APIs também
        RequestHeader set X-Panel-Key "SUA_CHAVE_SECRETA_FORTE_AQUI"
    </Location>
</VirtualHost>
```

### Opção B: Nginx (Server Block)
```nginx
server {
    listen 80;
    server_name painel-brigada.empresa.local;
    root /var/www/html/public;
    
    location ~ ^/(painel|api)/brigada {
        # Injetar header X-Panel-Key automaticamente
        proxy_set_header X-Panel-Key "SUA_CHAVE_SECRETA_FORTE_AQUI";
        
        # Se usar PHP-FPM direto (sem proxy)
        fastcgi_param HTTP_X_PANEL_KEY "SUA_CHAVE_SECRETA_FORTE_AQUI";
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        include fastcgi_params;
    }
}
```

### Aplicar configuração:
```bash
# Apache
sudo a2enmod headers
sudo systemctl reload apache2

# Nginx
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 ETAPA 3: Validar Segurança

### 3.1 Testar acesso interno (da VM):
```bash
# Deve retornar 200 OK com dados
curl -H "X-Panel-Key: SUA_CHAVE_AQUI" http://localhost/api/brigada/presentes

# Sem header = deve retornar 403 Forbidden
curl http://localhost/api/brigada/presentes
```

### 3.2 Testar de outro IP da rede 10.3.x.x:
```bash
# De outra máquina na rede 10.3.0.0/16
curl http://10.3.1.135/painel/brigada
# Deve carregar o painel (Apache/Nginx injeta header)
```

### 3.3 Testar de IP externo (fora 10.3.0.0/16):
```bash
# De IP público ou outra rede
curl http://10.3.1.135/api/brigada/presentes
# Deve retornar 403 Forbidden (IP bloqueado)
```

---

## 📺 ETAPA 4: Configurar TV/Tablet

### 4.1 Acessar URL no navegador da TV/tablet:
```
http://10.3.1.135/painel/brigada
```

### 4.2 Configurar modo kiosk (opcional):
- **Chrome:** `chrome --kiosk http://10.3.1.135/painel/brigada`
- **Firefox:** F11 para fullscreen
- **Android/iPad:** Apps como "Fully Kiosk Browser"

### 4.3 Prevenir sleep/screensaver:
- Configurar TV para nunca desligar
- Android: Apps de "Keep Screen On"
- Windows: `powercfg /change monitor-timeout-ac 0`

---

## 🧪 ETAPA 5: Testes Funcionais

### 5.1 Teste de atualização em tempo real:
1. Abrir painel na TV: `http://10.3.1.135/painel/brigada`
2. Registrar entrada de brigadista na portaria
3. **Aguardar 10 segundos** (polling automático)
4. Verificar se brigadista apareceu no painel

### 5.2 Teste de reconexão:
1. Parar servidor PHP temporariamente
2. Verificar se painel mostra "Reconectando..."
3. Reiniciar servidor
4. Verificar se painel volta ao normal

---

## 📊 ETAPA 6: Monitoramento

### 6.1 Logs do servidor:
```bash
# Apache
sudo tail -f /var/log/apache2/access.log | grep brigada

# Nginx
sudo tail -f /var/log/nginx/access.log | grep brigada

# PHP errors
sudo tail -f /var/log/php8.1-fpm.log
```

### 6.2 Verificar performance:
```bash
# Testar latência da API
time curl -H "X-Panel-Key: SUA_CHAVE" http://localhost/api/brigada/presentes
# Meta: < 100ms
```

---

## 🚨 Troubleshooting

### Erro 403 Forbidden:
- [ ] Verificar se `PANEL_BRG_KEY` está configurado no `.env`
- [ ] Verificar se Apache/Nginx está injetando o header
- [ ] Verificar se IP está na allowlist (10.3.0.0/16)

### Painel não atualiza:
- [ ] Verificar logs do navegador (F12 > Console)
- [ ] Testar API manualmente: `curl http://10.3.1.135/api/brigada/presentes`
- [ ] Verificar conexão de rede da TV/tablet

### Erro 500 Internal Server:
- [ ] Verificar `DATABASE_URL` no `.env`
- [ ] Testar conexão PostgreSQL: `psql $DATABASE_URL -c "SELECT 1"`
- [ ] Verificar logs PHP: `sudo tail -f /var/log/php8.1-fpm.log`

---

## 📝 Checklist Final

- [ ] `.env` configurado com `PANEL_BRG_KEY` forte
- [ ] Apache/Nginx injetando header `X-Panel-Key`
- [ ] Firewall permitindo porta 80 na rede 10.3.0.0/16
- [ ] Painel acessível em `http://10.3.1.135/painel/brigada`
- [ ] Teste end-to-end: entrada brigadista → aparece no painel
- [ ] TV/tablet configurado em fullscreen/kiosk mode
- [ ] Logs de monitoramento ativados

---

## 🎯 Próximos Passos (Opcional)

- [ ] **HTTPS:** Configurar certificado SSL para `https://painel-brigada.empresa.local`
- [ ] **Alertas:** Notificação quando total brigadistas < 2
- [ ] **Analytics:** Dashboard de presença histórica
- [ ] **Backup:** Cron job para backup diário do `.env`
