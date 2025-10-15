#!/bin/bash
# ================================================
# SCRIPT: Aplicar Diffs de Relatórios v2.0.0 (M5)
# Versão: 2.0.0
# Uso: bash feature_v200/apply_m5_reports.sh
# ================================================

set -e

echo "========================================"
echo "📊 APLICANDO DIFFS RELATÓRIOS v2.0.0 (M5)"
echo "========================================"
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verificar diffs
DIFFS=(
    "feature_v200/drafts/snippets/diff_prestadores_controller.md"
    "feature_v200/drafts/snippets/diff_visitantes_controller.md"
    "feature_v200/drafts/snippets/diff_profissionais_controller.md"
    "feature_v200/drafts/snippets/diff_dashboard_controller.md"
)

echo "📋 Verificando diffs..."
for diff in "${DIFFS[@]}"; do
    if [ ! -f "$diff" ]; then
        echo -e "${RED}❌ Diff não encontrado: $diff${NC}"
        exit 1
    fi
    echo -e "  ${GREEN}✓${NC} $(basename $diff)"
done
echo ""

# Criar backup
BACKUP_DIR="backups/m5_backup_$(date +%Y%m%d_%H%M%S)"
echo "📦 Criando backup dos controllers..."
mkdir -p "$BACKUP_DIR/controllers"

CONTROLLERS=(
    "src/controllers/PrestadoresServicoController.php"
    "src/controllers/VisitantesNovoController.php"
    "src/controllers/ProfissionaisRennerController.php"
    "src/controllers/DashboardController.php"
)

for controller in "${CONTROLLERS[@]}"; do
    if [ -f "$controller" ]; then
        cp "$controller" "$BACKUP_DIR/controllers/"
        echo -e "  ${GREEN}✓${NC} Backup: $(basename $controller)"
    else
        echo -e "  ${YELLOW}⚠${NC}  Controller não existe: $(basename $controller)"
    fi
done
echo ""

# Avisar sobre processo manual
echo "================================================"
echo -e "${YELLOW}⚠️  ATENÇÃO: APLICAÇÃO MANUAL NECESSÁRIA${NC}"
echo "================================================"
echo ""
echo "Os diffs de relatórios devem ser aplicados MANUALMENTE"
echo "devido à complexidade das mudanças."
echo ""
echo "📝 ORDEM DE APLICAÇÃO:"
echo ""
echo "1️⃣  🔴 CRÍTICO: diff_prestadores_controller.md"
echo "   → Corrige bug de saídas de prestadores"
echo "   → Arquivo: src/controllers/PrestadoresServicoController.php"
echo "   → Leia o diff e aplique as mudanças"
echo ""
echo "2️⃣  🔴 CRÍTICO: diff_dashboard_controller.md"
echo "   → Corrige contadores de prestadores ativos"
echo "   → Adiciona widget de cadastros expirando"
echo "   → Arquivo: src/controllers/DashboardController.php"
echo "   → Leia o diff e aplique as mudanças"
echo ""
echo "3️⃣  🟡 MÉDIA: diff_visitantes_controller.md"
echo "   → Adiciona suporte a documentos internacionais"
echo "   → Arquivo: src/controllers/VisitantesNovoController.php"
echo "   → Leia o diff e aplique as mudanças"
echo ""
echo "4️⃣  🟢 BAIXA: diff_profissionais_controller.md"
echo "   → Adiciona suporte a documentos estrangeiros"
echo "   → Arquivo: src/controllers/ProfissionaisRennerController.php"
echo "   → Leia o diff e aplique as mudanças"
echo ""
echo "================================================"
echo ""

echo "💡 DICAS PARA APLICAÇÃO:"
echo ""
echo "• Abra o diff ao lado do arquivo PHP"
echo "• Use busca (Ctrl+F) para localizar as linhas"
echo "• Copie/cole com cuidado, respeitando indentação"
echo "• Verifique sintaxe PHP após cada mudança"
echo "• Teste após aplicar cada controller"
echo ""

read -p "Abrir primeiro diff em cat? (s/N): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Ss]$ ]]; then
    echo ""
    echo "========================================"
    echo "DIFF: PrestadoresServicoController (CRÍTICO)"
    echo "========================================"
    cat "${DIFFS[0]}"
    echo ""
fi

echo ""
read -p "Você aplicou TODOS os 4 diffs? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Aplique todos os diffs antes de prosseguir!${NC}"
    echo ""
    echo "Backup dos controllers originais salvo em:"
    echo "  $BACKUP_DIR/controllers/"
    echo ""
    echo "Se precisar reverter:"
    echo "  cp $BACKUP_DIR/controllers/*.php src/controllers/"
    exit 0
fi

echo ""
echo "================================================"
echo -e "${GREEN}✅ DIFFS APLICADOS (confirmado pelo usuário)${NC}"
echo "================================================"
echo ""
echo "📊 RESUMO M5"
echo "================================================"
echo "Controllers modificados: 4"
echo "Diffs aplicados: 4 (manual)"
echo "Backup salvo em: $BACKUP_DIR"
echo ""

echo "⏭️  PRÓXIMO PASSO: Testes integrados"
echo "   Execute: bash feature_v200/run_integration_tests.sh"
echo ""
