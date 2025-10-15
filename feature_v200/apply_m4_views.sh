#!/bin/bash
# ================================================
# SCRIPT: Aplicar Views & JavaScript v2.0.0 (M4)
# Versão: 2.0.0
# Uso: bash feature_v200/apply_m4_views.sh
# ================================================

set -e

echo "========================================"
echo "🎨 APLICANDO VIEWS & JS v2.0.0 (M4)"
echo "========================================"
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verificar diretórios
if [ ! -d "feature_v200/drafts/views" ] || [ ! -d "feature_v200/drafts/js" ]; then
    echo -e "${RED}❌ Erro: Diretórios de views/js não encontrados!${NC}"
    exit 1
fi

echo "📋 Verificando arquivos..."

# Verificar views
VIEWS=(
    "feature_v200/drafts/views/ramais/index.php"
    "feature_v200/drafts/views/components/modal_entrada_retroativa.php"
    "feature_v200/drafts/views/components/widget_cadastros_expirando.php"
    "feature_v200/drafts/views/components/seletor_documento.php"
)

# Verificar JS
JS_FILES=(
    "feature_v200/drafts/js/ramais.js"
    "feature_v200/drafts/js/entrada-retroativa.js"
    "feature_v200/drafts/js/widget-cadastros-expirando.js"
    "feature_v200/drafts/js/document-validator.js"
    "feature_v200/drafts/js/gestao-validade.js"
)

for file in "${VIEWS[@]}" "${JS_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ Arquivo não encontrado: $file${NC}"
        exit 1
    fi
    echo -e "  ${GREEN}✓${NC} $(basename $file)"
done
echo ""

# Confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO:${NC}"
echo "Esta operação irá copiar:"
echo "  - 4 views PHP"
echo "  - 6 arquivos JavaScript"
echo ""
read -p "Deseja continuar? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${RED}❌ Operação cancelada.${NC}"
    exit 1
fi

# Criar backup
BACKUP_DIR="backups/m4_backup_$(date +%Y%m%d_%H%M%S)"
echo "📦 Criando backup em $BACKUP_DIR..."
mkdir -p "$BACKUP_DIR/views/ramais"
mkdir -p "$BACKUP_DIR/views/components"
mkdir -p "$BACKUP_DIR/js"

echo ""
echo "🚀 Copiando arquivos..."

# Copiar views
echo "📄 Views..."
mkdir -p "views/ramais"
mkdir -p "views/components"

for view in "${VIEWS[@]}"; do
    if [[ $view == *"ramais/index.php" ]]; then
        dest="views/ramais/"
    else
        dest="views/components/"
    fi
    
    filename=$(basename "$view")
    
    # Backup se existir
    if [ -f "$dest$filename" ]; then
        cp "$dest$filename" "$BACKUP_DIR/$dest"
        echo -e "  ${YELLOW}📦${NC} Backup: $filename"
    fi
    
    cp "$view" "$dest"
    echo -e "  ${GREEN}✅${NC} Copiado: $filename → $dest"
done

# Copiar JavaScript
echo ""
echo "📜 JavaScript..."
mkdir -p "public/assets/js"

for js in "${JS_FILES[@]}"; do
    dest="public/assets/js/"
    filename=$(basename "$js")
    
    if [ -f "$dest$filename" ]; then
        cp "$dest$filename" "$BACKUP_DIR/js/"
        echo -e "  ${YELLOW}📦${NC} Backup: $filename"
    fi
    
    cp "$js" "$dest"
    echo -e "  ${GREEN}✅${NC} Copiado: $filename → $dest"
done

echo ""
echo "================================================"
echo -e "${GREEN}✅ VIEWS E JAVASCRIPT APLICADOS!${NC}"
echo "================================================"
echo ""

# Instruções manuais
echo "📝 PRÓXIMOS PASSOS MANUAIS:"
echo ""
echo "1️⃣  Adicionar rota de ramais em src/services/NavigationService.php:"
echo "    ["
echo "        'label' => 'Ramais',"
echo "        'url' => '/ramais',"
echo "        'icon' => 'bi-telephone',"
echo "        'permission' => null"
echo "    ],"
echo ""
echo "2️⃣  Incluir widget no dashboard em views/dashboard/index.php:"
echo "    <?php require_once __DIR__ . '/../components/widget_cadastros_expirando.php'; ?>"
echo ""
echo "3️⃣  Incluir scripts no layout em views/layouts/main.php (antes de </body>):"
echo "    <script src=\"/assets/js/document-validator.js\"></script>"
echo "    <script src=\"/assets/js/gestao-validade.js\"></script>"
echo ""

read -p "Você aplicou as mudanças manuais acima? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Aplique as mudanças manuais antes de continuar!${NC}"
    exit 0
fi

echo ""
echo "================================================"
echo "📊 RESUMO M4"
echo "================================================"
echo "Views copiadas: 4"
echo "JavaScript copiados: 6"
echo "Mudanças manuais: 3"
echo "Backup salvo em: $BACKUP_DIR"
echo ""

echo "⏭️  PRÓXIMO PASSO: Aplicar diffs de relatórios (M5)"
echo "   Execute: bash feature_v200/apply_m5_reports.sh"
echo ""
