# 🔄 Rollback - Painel de Brigada de Incêndio

## ⚠️ ATENÇÃO

Este documento contém instruções para **REVERTER** completamente a implementação do Painel de Brigada. Use apenas em caso de emergência.

---

## 📋 O que será removido

- ✅ Helpers de segurança (IP allowlist, header validation)
- ✅ Rotas `/painel/brigada` e `/api/brigada/presentes`
- ✅ Controller `PanelBrigadaController.php`
- ✅ View `views/painel/brigada.php`
- ✅ Diretório `views/painel/`
- ✅ Documentação `docs/painel_brigada.md`
- ✅ Rascunhos em `feature_painel_brigada/drafts/`

---

## 🛠️ Instruções de Rollback

### Passo 1: Aplicar patch de reversão

```bash
# Ir para o diretório do projeto
cd /path/to/projeto

# Aplicar patch que remove mudanças do router.php
patch -p1 < feature_painel_brigada/revert_painel_brigada.patch

# OU manualmente restaurar router.php (backup)
# cp public/router.php.backup public/router.php
```

---

### Passo 2: Remover arquivos criados

```bash
# Remover controller
rm -f src/controllers/PanelBrigadaController.php

# Remover view e diretório
rm -f views/painel/brigada.php
rmdir views/painel  # Remove se estiver vazio

# Remover documentação
rm -f docs/painel_brigada.md

# Remover diretório de feature completo (opcional)
rm -rf feature_painel_brigada
```

---

### Passo 3: Remover variáveis de ambiente

**Replit Secrets:**
1. Abrir painel de Secrets
2. Deletar `PANEL_BRG_KEY`
3. Deletar `PANEL_BRG_DEV_MODE` (se existir)

**Ou via CLI (se aplicável):**
```bash
# Remover do .env
sed -i '/PANEL_BRG_KEY/d' .env
sed -i '/PANEL_BRG_DEV_MODE/d' .env
```

---

### Passo 4: Reiniciar servidor

```bash
# Replit: Reiniciar workflow
# Ou manualmente:
pkill -f "php.*5000"
cd public && php -S 0.0.0.0:5000 router.php
```

---

### Passo 5: Verificar reversão

```bash
# Teste 1: API não deve mais existir (404 ou 302 redirect)
curl -i http://localhost:5000/api/brigada/presentes

# Teste 2: View não deve mais existir (404 ou 302 redirect)
curl -i http://localhost:5000/painel/brigada

# Resultado esperado: HTTP 404 ou redirecionamento para /login
```

---

## 📝 Script Automático de Rollback

Salve este script como `rollback_painel.sh`:

```bash
#!/bin/bash

echo "🔄 Iniciando rollback do Painel de Brigada..."

# 1. Aplicar patch
echo "📝 Revertendo router.php..."
patch -p1 < feature_painel_brigada/revert_painel_brigada.patch

# 2. Remover arquivos
echo "🗑️  Removendo arquivos criados..."
rm -f src/controllers/PanelBrigadaController.php
rm -f views/painel/brigada.php
rmdir views/painel 2>/dev/null
rm -f docs/painel_brigada.md

# 3. Remover feature completa
echo "📦 Removendo diretório de feature..."
rm -rf feature_painel_brigada

# 4. Verificar
echo "✅ Verificando reversão..."
if [ ! -f src/controllers/PanelBrigadaController.php ]; then
    echo "✅ Controller removido"
else
    echo "❌ Erro: Controller ainda existe"
fi

if [ ! -f views/painel/brigada.php ]; then
    echo "✅ View removida"
else
    echo "❌ Erro: View ainda existe"
fi

echo ""
echo "⚠️  Lembrete: Remova manualmente as variáveis de ambiente:"
echo "   - PANEL_BRG_KEY"
echo "   - PANEL_BRG_DEV_MODE (se existir)"
echo ""
echo "🔄 Rollback concluído! Reinicie o servidor PHP."
```

**Executar:**
```bash
chmod +x rollback_painel.sh
./rollback_painel.sh
```

---

## 🔍 Verificação Pós-Rollback

### Checklist

- [ ] `src/controllers/PanelBrigadaController.php` removido
- [ ] `views/painel/brigada.php` removido
- [ ] `views/painel/` diretório removido (se vazio)
- [ ] `docs/painel_brigada.md` removido
- [ ] `feature_painel_brigada/` diretório removido
- [ ] `PANEL_BRG_KEY` removida dos secrets
- [ ] `PANEL_BRG_DEV_MODE` removida dos secrets
- [ ] Servidor PHP reiniciado
- [ ] Rotas `/api/brigada/presentes` e `/painel/brigada` não acessíveis

### Comandos de Verificação

```bash
# Verificar se arquivos ainda existem
ls -la src/controllers/PanelBrigadaController.php 2>/dev/null && echo "❌ Controller ainda existe" || echo "✅ Controller removido"

ls -la views/painel/brigada.php 2>/dev/null && echo "❌ View ainda existe" || echo "✅ View removida"

# Verificar rotas
curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/api/brigada/presentes
# Esperado: 404 ou 302

curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/painel/brigada
# Esperado: 404 ou 302
```

---

## 📞 Suporte

Se houver problemas durante o rollback:

1. **Backup não funciona:** Restaure manualmente o `router.php` original
2. **Arquivos não deletam:** Verifique permissões (`chmod 644`)
3. **Rotas ainda acessíveis:** Force restart do PHP (`pkill -9 php`)

---

## 📚 Arquivos de Referência

- **Patch de reversão:** `feature_painel_brigada/revert_painel_brigada.patch`
- **Documentação original:** `docs/BRIGADA_INCENDIO_DOCUMENTATION.md`
- **Prompt original:** `attached_assets/Pasted-PROMPT-*.txt`

---

**Última atualização:** 2025-10-08  
**Versão do painel:** 1.0.0
