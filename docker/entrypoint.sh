#!/bin/bash
set -e

echo "🚀 Controle de Portaria - Inicializando..."

# Ajustar permissões (33 = www-data)
echo "📁 Ajustando permissões..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads /var/www/html/logs 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/uploads /var/www/html/logs 2>/dev/null || true

# Criar DATABASE_URL a partir das variáveis separadas (compatibilidade)
if [ -n "$PGUSER" ] && [ -n "$PGPASSWORD" ] && [ -n "$PGHOST" ] && [ -n "$PGDATABASE" ]; then
    export DATABASE_URL="postgresql://${PGUSER}:${PGPASSWORD}@${PGHOST}:${PGPORT:-5432}/${PGDATABASE}?sslmode=prefer"
    echo "✅ DATABASE_URL configurada"
fi

# Gerar SESSION_SECRET se não existir
if [ -z "$SESSION_SECRET" ]; then
    export SESSION_SECRET=$(openssl rand -base64 64 | tr -d '\n')
    echo "🔑 SESSION_SECRET gerada automaticamente"
fi

echo "✅ Inicialização completa!"
echo "📡 Servidor rodando em http://0.0.0.0:80"

# Executar comando original (apache2-foreground)
exec "$@"
