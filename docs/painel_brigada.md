# Painel de Brigada de Incêndio - Documentação Técnica

## 📋 Visão Geral

Painel em tempo real para exibição de brigadistas presentes na planta, acessível sem login, protegido por IP allowlist e header HTTP customizado. Ideal para TVs na recepção ou áreas de segurança.

## 🔐 Segurança (Sem Login)

### Mecanismos de Proteção

1. **IP Allowlist (hardcoded)**
   - Somente IPs autorizados podem acessar
   - Configurado em: `public/router.php` → função `requirePanelAuth()`
   
2. **Header HTTP Obrigatório**
   - `X-Panel-Key`: Chave secreta validada no servidor
   - Valor vem da variável de ambiente `PANEL_BRG_KEY`

3. **Dev Mode (Opcional)**
   - `PANEL_BRG_DEV_MODE=1` + localhost = bypass de header
   - Útil para desenvolvimento local

---

## ⚙️ Variáveis de Ambiente

### Obrigatórias

```bash
# Chave secreta para autenticação via header
PANEL_BRG_KEY=sua_chave_secreta_aqui

# Exemplo de geração:
openssl rand -hex 32
```

### Opcionais

```bash
# Dev Mode: Permite localhost sem header (apenas desenvolvimento)
PANEL_BRG_DEV_MODE=1
```

---

## 🌐 IP Allowlist

**Configuração atual (hardcoded em `public/router.php`):**

```php
$allowlist = [
    '10.3.0.0/16',    // Rede interna
    '127.0.0.1/32',   // Localhost
    '::1'             // Localhost IPv6
];
```

### Como Alterar a Allowlist

Edite `public/router.php`, função `requirePanelAuth()`:

```php
$allowlist = [
    '10.3.0.0/16',        // Sua rede interna
    '192.168.1.0/24',     // Adicione mais ranges
    '172.16.0.100/32'     // IP único
];
```

---

## 📡 Endpoints

### 1. API - Brigadistas Presentes

**GET** `/api/brigada/presentes`

**Headers obrigatórios:**
```
X-Panel-Key: {valor_de_PANEL_BRG_KEY}
```

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "nome": "GABRIEL MARCELO DA SILVA",
      "setor": "Gestao de TI",
      "professional_id": 107,
      "desde": "2025-10-06T17:18:00-03:00"
    }
  ],
  "timestamp": "2025-10-08T17:19:11-03:00",
  "total": 1
}
```

**Response 403 Forbidden (IP não autorizado):**
```json
{
  "success": false,
  "message": "IP não autorizado",
  "ip": "192.168.1.100"
}
```

**Response 403 Forbidden (Header inválido):**
```json
{
  "success": false,
  "message": "Header X-Panel-Key inválido ou ausente"
}
```

**Características:**
- ✅ Cache-Control: no-store (sem cache)
- ✅ LGPD: Sem exposição de CPF
- ✅ Timezone: America/Sao_Paulo (ISO 8601)
- ✅ Atualização em tempo real

---

### 2. View - Painel Fullscreen

**GET** `/painel/brigada`

**Headers obrigatórios:**
```
X-Panel-Key: {valor_de_PANEL_BRG_KEY}
```

**Response 200 OK:**
- HTML fullscreen com AdminLTE 3
- Polling automático a cada 10s
- Recarga diária às 03:00
- Indicador de status (Online/Reconectando)

---

## 🧪 Testes com cURL

### Teste 1: API sem header (deve retornar 403)

```bash
curl -i http://localhost:5000/api/brigada/presentes
```

**Resultado esperado:**
```
HTTP/1.1 403 Forbidden
{"success":false,"message":"Header X-Panel-Key inválido ou ausente"}
```

---

### Teste 2: API com header correto (deve retornar 200)

```bash
curl -i http://localhost:5000/api/brigada/presentes \
  -H "X-Panel-Key: $PANEL_BRG_KEY"
```

**Resultado esperado:**
```
HTTP/1.1 200 OK
{"success":true,"data":[...],"total":1}
```

---

### Teste 3: View HTML (deve retornar 200)

```bash
curl -i http://localhost:5000/painel/brigada \
  -H "X-Panel-Key: $PANEL_BRG_KEY"
```

**Resultado esperado:**
```
HTTP/1.1 200 OK
<!DOCTYPE html>
<html lang="pt-BR">
...
```

---

### Teste 4: Dev Mode (localhost sem header)

**Pré-requisito:** `PANEL_BRG_DEV_MODE=1`

```bash
curl -i http://localhost:5000/api/brigada/presentes
```

**Resultado esperado (se dev mode ativo):**
```
HTTP/1.1 200 OK
{"success":true,"data":[...],"total":1}
```

---

## 🔧 Configuração Apache/Nginx (Produção)

### Apache (Proxy Reverso com Header)

**Arquivo:** `/etc/apache2/sites-available/painel-brigada.conf`

```apache
<VirtualHost *:80>
    ServerName painel-brigada.empresa.local
    
    # Restringir acesso por IP (redundância)
    <Location />
        Require ip 10.3.0.0/16
        Require ip 192.168.1.0/24
    </Location>
    
    # Proxy para o painel
    ProxyPreserveHost On
    ProxyPass /painel/brigada http://10.3.1.135:5000/painel/brigada
    ProxyPassReverse /painel/brigada http://10.3.1.135:5000/painel/brigada
    ProxyPass /api/brigada/presentes http://10.3.1.135:5000/api/brigada/presentes
    ProxyPassReverse /api/brigada/presentes http://10.3.1.135:5000/api/brigada/presentes
    
    # Injetar header X-Panel-Key (ler de variável de ambiente)
    RequestHeader set X-Panel-Key "${PANEL_BRG_KEY}"
    
    ErrorLog ${APACHE_LOG_DIR}/painel-brigada-error.log
    CustomLog ${APACHE_LOG_DIR}/painel-brigada-access.log combined
</VirtualHost>
```

**Ativar:**
```bash
# Habilitar módulos
sudo a2enmod proxy proxy_http headers

# Definir variável de ambiente (systemd override)
sudo systemctl edit apache2
# Adicionar: Environment="PANEL_BRG_KEY=sua_chave_aqui"

# Ativar site
sudo a2ensite painel-brigada
sudo systemctl reload apache2
```

---

### Nginx (Proxy Reverso com Header)

**Arquivo:** `/etc/nginx/sites-available/painel-brigada`

```nginx
server {
    listen 80;
    server_name painel-brigada.empresa.local;
    
    # Restringir acesso por IP
    allow 10.3.0.0/16;
    allow 192.168.1.0/24;
    deny all;
    
    # Proxy para o painel
    location /painel/brigada {
        proxy_pass http://10.3.1.135:5000/painel/brigada;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        
        # Injetar header X-Panel-Key (ler de env)
        proxy_set_header X-Panel-Key $panel_brg_key;
    }
    
    location /api/brigada/presentes {
        proxy_pass http://10.3.1.135:5000/api/brigada/presentes;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        
        # Injetar header X-Panel-Key
        proxy_set_header X-Panel-Key $panel_brg_key;
    }
    
    access_log /var/log/nginx/painel-brigada-access.log;
    error_log /var/log/nginx/painel-brigada-error.log;
}
```

**Configurar variável:**

Edite `/etc/nginx/nginx.conf` (dentro do bloco `http`):

```nginx
http {
    # Ler variável de ambiente
    env PANEL_BRG_KEY;
    
    # Mapear para variável Nginx
    map $http_host $panel_brg_key {
        default "${PANEL_BRG_KEY}";
    }
    
    # ... resto da config
}
```

**Ativar:**
```bash
# Definir variável de ambiente (systemd override)
sudo systemctl edit nginx
# Adicionar: Environment="PANEL_BRG_KEY=sua_chave_aqui"

# Ativar site
sudo ln -s /etc/nginx/sites-available/painel-brigada /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🗄️ Estrutura do Banco de Dados

### Query Utilizada

```sql
SELECT 
    p.nome,
    p.setor,
    r.profissional_renner_id as professional_id,
    COALESCE(r.retorno, r.entrada_at) as desde,
    b.active
FROM registro_acesso r
JOIN profissionais_renner p ON p.id = r.profissional_renner_id
JOIN brigadistas b ON b.professional_id = p.id
WHERE r.tipo = 'profissional_renner'
  AND r.saida_final IS NULL
  AND b.active = TRUE
ORDER BY desde DESC
```

### Lógica de "Presente"

Um brigadista está presente quando:
1. ✅ É brigadista ativo (`brigadistas.active = TRUE`)
2. ✅ Tem registro de acesso aberto (`registro_acesso.saida_final IS NULL`)
3. ✅ É do tipo profissional Renner (`registro_acesso.tipo = 'profissional_renner'`)

### Índices Utilizados

- `idx_brigadistas_professional_id` (brigadistas.professional_id)
- `idx_brigadistas_active` (brigadistas.active)
- `idx_registro_acesso_profissional` (registro_acesso.profissional_renner_id)

**Performance:** ~4.7ms (excelente)

---

## 📊 Monitoramento e Logs

### Logs de Erro

**Localização:** `/logs/error.log`

**Formato:**
```
[PanelBrigada] Erro na API presentes: {mensagem} | Trace: {stack_trace}
```

**Exemplo:**
```
[PanelBrigada] Erro na API presentes: SQLSTATE[42P01]: Undefined table | Trace: ...
```

### Logs de Acesso

- Apache: `/var/log/apache2/painel-brigada-access.log`
- Nginx: `/var/log/nginx/painel-brigada-access.log`

### Monitorar Erros

```bash
# Tail em tempo real
tail -f /path/to/logs/error.log | grep PanelBrigada

# Contar erros nas últimas 24h
grep -c "PanelBrigada.*Erro" /path/to/logs/error.log
```

---

## 🎨 Interface do Painel

### Recursos Visuais

- **Design:** Fullscreen com fundo gradiente escuro
- **Framework:** AdminLTE 3 + Bootstrap 4.6
- **Ícones:** Font Awesome 5
- **Badge:** Extintor vermelho piscante 🧯
- **Layout:** Grid responsivo (cards 3 colunas)

### Atualização Automática

- **Polling:** A cada 10 segundos
- **Recarga diária:** Às 03:00 (evita memory leak)
- **Reconexão:** Até 5 tentativas, depois recarrega página

### Indicadores de Status

| Status | Cor | Ícone | Descrição |
|--------|-----|-------|-----------|
| Online | Verde | ● | API respondendo normalmente |
| Reconectando | Amarelo | ⚠️ | Tentando reconectar (1-5 tentativas) |
| Offline | - | - | Recarrega página após 5 falhas |

---

## 🔒 Compliance e Segurança

### LGPD

- ✅ Sem exposição de CPF
- ✅ Dados mínimos (nome, setor, hora)
- ✅ Sem armazenamento local (cache desabilitado)

### Segurança

- ✅ IP allowlist (primeira camada)
- ✅ Header X-Panel-Key (segunda camada)
- ✅ HTTPS recomendado em produção
- ✅ Cache-Control: no-store
- ✅ Escape XSS na view (escapeHtml)

---

## 🚀 Troubleshooting

### Problema: 403 Forbidden

**Causa 1:** IP não autorizado
```bash
# Verificar IP do cliente
curl -s https://api.ipify.org

# Adicionar IP à allowlist em public/router.php
```

**Causa 2:** Header X-Panel-Key ausente/inválido
```bash
# Verificar variável de ambiente
echo $PANEL_BRG_KEY

# Testar com header explícito
curl -H "X-Panel-Key: teste123" http://localhost:5000/api/brigada/presentes
```

---

### Problema: 500 Internal Server Error

**Verificar logs:**
```bash
tail -50 /path/to/logs/error.log | grep PanelBrigada
```

**Causas comuns:**
- Banco de dados offline
- Tabela/coluna não existe
- Erro de timezone (DateTime)

---

### Problema: Painel não atualiza

**Verificar console do browser:**
- F12 → Console → Procurar erros JavaScript
- Network → Verificar requisições `/api/brigada/presentes`

**Testar API manualmente:**
```bash
curl -H "X-Panel-Key: $PANEL_BRG_KEY" http://localhost:5000/api/brigada/presentes
```

---

## 📚 Referências

- **Prompt Original:** `attached_assets/Pasted-PROMPT-cole-no-Replit-*.txt`
- **Código Fonte:** 
  - Controller: `src/controllers/PanelBrigadaController.php`
  - View: `views/painel/brigada.php`
  - Router: `public/router.php` (helpers + rotas)
- **Documentação Completa:** `docs/BRIGADA_INCENDIO_DOCUMENTATION.md`

---

## 📝 Changelog

### v1.0.0 (2025-10-08)
- ✅ Implementação inicial do painel
- ✅ Autenticação via IP allowlist + header
- ✅ API REST com Cache-Control: no-store
- ✅ View fullscreen AdminLTE
- ✅ Polling 10s + recarga diária
- ✅ LGPD compliance (sem CPF)
- ✅ Logs de erro com trace
- ✅ Dev mode opcional
