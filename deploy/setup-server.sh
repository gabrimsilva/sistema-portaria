#!/bin/bash
set -e

echo "🚀 Configurando servidor Ubuntu para deploy via GitHub Actions"
echo "=============================================================="

# 1. Atualizar sistema
echo ""
echo "1️⃣ Atualizando sistema..."
sudo apt update && sudo apt upgrade -y

# 2. Instalar Docker
echo ""
echo "2️⃣ Instalando Docker..."
if ! command -v docker &> /dev/null; then
    sudo apt install -y docker.io docker-compose
    sudo systemctl enable docker
    sudo systemctl start docker
    echo "✅ Docker instalado"
else
    echo "✅ Docker já está instalado"
fi

# 3. Criar usuário de deploy
echo ""
echo "3️⃣ Configurando usuário de deploy..."
if ! id "deployuser" &>/dev/null; then
    sudo adduser --disabled-password --gecos "" deployuser
    sudo usermod -aG docker deployuser
    echo "✅ Usuário deployuser criado"
else
    echo "✅ Usuário deployuser já existe"
fi

# 4. Configurar SSH para GitHub Actions
echo ""
echo "4️⃣ Gerando chave SSH para GitHub Actions..."
sudo su - deployuser << 'EOSU'
if [ ! -f ~/.ssh/id_ed25519 ]; then
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/id_ed25519 -N ""
    cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
    echo "✅ Chave SSH gerada"
else
    echo "✅ Chave SSH já existe"
fi
EOSU

# 5. Criar estrutura de diretórios
echo ""
echo "5️⃣ Criando estrutura de diretórios..."
sudo su - deployuser << 'EOSU'
mkdir -p ~/controle-portaria/{storage,uploads,logs}
chmod 755 ~/controle-portaria
EOSU

# 6. Mostrar chave privada
echo ""
echo "=============================================================="
echo "✅ CONFIGURAÇÃO CONCLUÍDA!"
echo "=============================================================="
echo ""
echo "📋 PRÓXIMOS PASSOS:"
echo ""
echo "1. Copie a CHAVE PRIVADA abaixo e adicione no GitHub:"
echo "   GitHub → Repositório → Settings → Secrets and variables → Actions"
echo "   Nome do secret: SSH_PRIVATE_KEY"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
sudo cat /home/deployuser/.ssh/id_ed25519
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "2. Adicione também estes secrets no GitHub:"
echo "   - DOCKER_USERNAME: seu usuário do Docker Hub"
echo "   - DOCKER_PASSWORD: seu token do Docker Hub"
echo "   - SERVER_HOST: $(curl -s ifconfig.me)"
echo "   - SERVER_USER: deployuser"
echo ""
echo "3. Faça o primeiro deploy manual:"
echo "   sudo su - deployuser"
echo "   cd ~/controle-portaria"
echo "   # Copie os arquivos docker-compose.yml e .env para este diretório"
echo ""
