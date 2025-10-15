#!/bin/bash
# ================================================
# SCRIPT: Aplicar Endpoints v2.0.0 (M3)
# Versão: 2.0.0
# Uso: bash feature_v200/apply_m3_endpoints.sh
# ================================================

set -e  # Parar em caso de erro

echo "========================================"
echo "🔌 APLICANDO ENDPOINTS v2.0.0 (M3)"
echo "========================================"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verificar diretórios
if [ ! -d "feature_v200/drafts/controllers" ]; then
    echo -e "${RED}❌ Erro: Diretório feature_v200/drafts/controllers não encontrado!${NC}"
    exit 1
fi

echo "📋 Verificando arquivos a copiar..."

# Lista de arquivos
CONTROLLERS=(
    "feature_v200/drafts/controllers/DocumentoController.php:src/controllers/"
    "feature_v200/drafts/controllers/EntradaRetroativaController.php:src/controllers/"
    "feature_v200/drafts/controllers/RamalController.php:src/controllers/"
    "feature_v200/drafts/controllers/ValidadeController.php:src/controllers/"
)

SERVICES=(
    "feature_v200/drafts/services/DocumentValidator.php:src/services/"
)

# Verificar existência
for file_dest in "${CONTROLLERS[@]}" "${SERVICES[@]}"; do
    file="${file_dest%%:*}"
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Arquivo não encontrado: $file${NC}"
        exit 1
    fi
    echo -e "  ${GREEN}✓${NC} $(basename $file)"
done
echo ""

# Confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO:${NC}"
echo "Esta operação irá copiar 5 arquivos novos para src/"
echo ""
read -p "Deseja continuar? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${RED}❌ Operação cancelada.${NC}"
    exit 1
fi

# Criar backup
BACKUP_DIR="backups/m3_backup_$(date +%Y%m%d_%H%M%S)"
echo "📦 Criando backup em $BACKUP_DIR..."
mkdir -p "$BACKUP_DIR/controllers"
mkdir -p "$BACKUP_DIR/services"

# Copiar arquivos com backup
echo ""
echo "🚀 Copiando arquivos..."

for file_dest in "${CONTROLLERS[@]}"; do
    file="${file_dest%%:*}"
    dest="${file_dest##*:}"
    filename=$(basename "$file")
    
    # Backup se existir
    if [ -f "$dest$filename" ]; then
        cp "$dest$filename" "$BACKUP_DIR/controllers/"
        echo -e "  ${YELLOW}📦${NC} Backup: $filename"
    fi
    
    # Copiar
    cp "$file" "$dest"
    echo -e "  ${GREEN}✅${NC} Copiado: $filename → $dest"
done

for file_dest in "${SERVICES[@]}"; do
    file="${file_dest%%:*}"
    dest="${file_dest##*:}"
    filename=$(basename "$file")
    
    if [ -f "$dest$filename" ]; then
        cp "$dest$filename" "$BACKUP_DIR/services/"
        echo -e "  ${YELLOW}📦${NC} Backup: $filename"
    fi
    
    cp "$file" "$dest"
    echo -e "  ${GREEN}✅${NC} Copiado: $filename → $dest"
done

echo ""
echo "================================================"
echo -e "${GREEN}✅ CONTROLLERS E SERVICES APLICADOS!${NC}"
echo "================================================"
echo ""

# Mostrar diff de rotas
echo "📝 PRÓXIMO PASSO MANUAL: Atualizar public/index.php"
echo ""
echo "Abra o arquivo: feature_v200/drafts/snippets/rotas_v2_diff.md"
echo "E aplique as mudanças em: public/index.php"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC} As rotas são críticas para funcionamento!"
echo "Verifique cada linha do diff antes de aplicar."
echo ""

read -p "Você aplicou as rotas em public/index.php? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Aplique as rotas antes de continuar para M4!${NC}"
    exit 0
fi

echo ""
echo "================================================"
echo "📊 RESUMO M3"
echo "================================================"
echo "Controllers copiados: 4"
echo "Services copiados: 1"
echo "Rotas adicionadas: 22 (manual)"
echo "Backup salvo em: $BACKUP_DIR"
echo ""

echo "⏭️  PRÓXIMO PASSO: Aplicar views/JS (M4)"
echo "   Execute: bash feature_v200/apply_m4_views.sh"
echo ""
