#!/bin/bash
# ================================================
# SCRIPT: Testes Integrados v2.0.0
# Versão: 2.0.0
# Uso: bash feature_v200/run_integration_tests.sh
# ================================================

echo "========================================"
echo "🧪 TESTES INTEGRADOS v2.0.0"
echo "========================================"
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS_COUNT=0
FAIL_COUNT=0
TOTAL_COUNT=0

# Função para marcar teste
pass_test() {
    PASS_COUNT=$((PASS_COUNT + 1))
    TOTAL_COUNT=$((TOTAL_COUNT + 1))
    echo -e "  ${GREEN}✅ PASSOU${NC}"
}

fail_test() {
    FAIL_COUNT=$((FAIL_COUNT + 1))
    TOTAL_COUNT=$((TOTAL_COUNT + 1))
    echo -e "  ${RED}❌ FALHOU${NC}"
}

skip_test() {
    TOTAL_COUNT=$((TOTAL_COUNT + 1))
    echo -e "  ${YELLOW}⊘ PULADO${NC}"
}

manual_test() {
    local question=$1
    read -p "$question (s/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        pass_test
        return 0
    else
        fail_test
        return 1
    fi
}

echo "================================================"
echo "CATEGORIA 1: BANCO DE DADOS"
echo "================================================"
echo ""

echo "Teste 1.1: Views consolidadas existem"
if [ -z "$DATABASE_URL" ]; then
    echo -e "  ${YELLOW}⊘ DATABASE_URL não definida, pulando${NC}"
    skip_test
else
    VIEW_COUNT=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM information_schema.views WHERE table_schema='public' AND table_name LIKE 'vw_%';" 2>/dev/null || echo "0")
    if [ "$VIEW_COUNT" -ge "3" ]; then
        echo "  Encontradas $VIEW_COUNT views"
        pass_test
    else
        echo "  Esperado: ≥3 views, Encontrado: $VIEW_COUNT"
        fail_test
    fi
fi

echo ""
echo "Teste 1.2: Colunas de documentos existem"
if [ -z "$DATABASE_URL" ]; then
    skip_test
else
    DOC_COLS=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM information_schema.columns WHERE table_name='visitantes_novo' AND column_name IN ('doc_type','doc_number','doc_country');" 2>/dev/null || echo "0")
    if [ "$DOC_COLS" -eq "3" ]; then
        pass_test
    else
        echo "  Esperado: 3 colunas, Encontrado: $DOC_COLS"
        fail_test
    fi
fi

echo ""
echo "Teste 1.3: Função de entrada retroativa existe"
if [ -z "$DATABASE_URL" ]; then
    skip_test
else
    FUNC_EXISTS=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM information_schema.routines WHERE routine_name='registrar_entrada_retroativa_profissional';" 2>/dev/null || echo "0")
    if [ "$FUNC_EXISTS" -eq "1" ]; then
        pass_test
    else
        fail_test
    fi
fi

echo ""
echo "================================================"
echo "CATEGORIA 2: ARQUIVOS PHP"
echo "================================================"
echo ""

echo "Teste 2.1: Controllers novos existem"
NEW_CONTROLLERS=(
    "src/controllers/DocumentoController.php"
    "src/controllers/EntradaRetroativaController.php"
    "src/controllers/RamalController.php"
    "src/controllers/ValidadeController.php"
)
MISSING=0
for ctrl in "${NEW_CONTROLLERS[@]}"; do
    if [ ! -f "$ctrl" ]; then
        echo "  ❌ Faltando: $(basename $ctrl)"
        MISSING=$((MISSING + 1))
    fi
done
if [ $MISSING -eq 0 ]; then
    echo "  Todos os 4 controllers encontrados"
    pass_test
else
    echo "  Faltando: $MISSING controllers"
    fail_test
fi

echo ""
echo "Teste 2.2: Service de validação existe"
if [ -f "src/services/DocumentValidator.php" ]; then
    pass_test
else
    fail_test
fi

echo ""
echo "Teste 2.3: Views novas existem"
NEW_VIEWS=(
    "views/ramais/index.php"
    "views/components/modal_entrada_retroativa.php"
    "views/components/widget_cadastros_expirando.php"
    "views/components/seletor_documento.php"
)
MISSING=0
for view in "${NEW_VIEWS[@]}"; do
    if [ ! -f "$view" ]; then
        echo "  ❌ Faltando: $(basename $view)"
        MISSING=$((MISSING + 1))
    fi
done
if [ $MISSING -eq 0 ]; then
    echo "  Todas as 4 views encontradas"
    pass_test
else
    echo "  Faltando: $MISSING views"
    fail_test
fi

echo ""
echo "Teste 2.4: JavaScript novo existe"
NEW_JS=(
    "public/assets/js/ramais.js"
    "public/assets/js/entrada-retroativa.js"
    "public/assets/js/widget-cadastros-expirando.js"
    "public/assets/js/document-validator.js"
    "public/assets/js/gestao-validade.js"
)
MISSING=0
for js in "${NEW_JS[@]}"; do
    if [ ! -f "$js" ]; then
        echo "  ❌ Faltando: $(basename $js)"
        MISSING=$((MISSING + 1))
    fi
done
if [ $MISSING -eq 0 ]; then
    echo "  Todos os 6 arquivos JS encontrados"
    pass_test
else
    echo "  Faltando: $MISSING arquivos JS"
    fail_test
fi

echo ""
echo "================================================"
echo "CATEGORIA 3: FUNCIONALIDADES (MANUAL)"
echo "================================================"
echo ""

echo "Teste 3.1: Login funciona"
manual_test "  Você consegue fazer login?"

echo ""
echo "Teste 3.2: Dashboard carrega"
manual_test "  O dashboard carrega sem erros?"

echo ""
echo "Teste 3.3: Widget de cadastros expirando aparece"
manual_test "  O widget de cadastros expirando está visível?"

echo ""
echo "Teste 3.4: Página de ramais funciona (/ramais)"
manual_test "  A página /ramais carrega corretamente?"

echo ""
echo "Teste 3.5: Cadastrar visitante com CPF"
manual_test "  Você conseguiu cadastrar um visitante com CPF?"

echo ""
echo "Teste 3.6: Cadastrar visitante com Passaporte"
manual_test "  Você conseguiu cadastrar um visitante com Passaporte?"

echo ""
echo "Teste 3.7: Registrar entrada"
manual_test "  Você conseguiu registrar uma entrada?"

echo ""
echo "Teste 3.8: Registrar saída"
manual_test "  Você conseguiu registrar uma saída?"

echo ""
echo "Teste 3.9: Relatórios carregam"
manual_test "  Os relatórios (visitantes/prestadores) carregam?"

echo ""
echo "Teste 3.10: Export CSV funciona"
manual_test "  A exportação CSV funciona?"

echo ""
echo "================================================"
echo "CATEGORIA 4: BUG FIXES (CRÍTICO)"
echo "================================================"
echo ""

echo "Teste 4.1: Saídas de prestadores aparecem em relatórios"
manual_test "  Prestadores com saída via placa aparecem no relatório?"

echo ""
echo "Teste 4.2: Contadores de dashboard corretos"
manual_test "  Os contadores de 'Ativos Agora' estão corretos?"

echo ""
echo "================================================"
echo "📊 RESULTADOS DOS TESTES"
echo "================================================"
echo ""
echo -e "Total de testes: $TOTAL_COUNT"
echo -e "${GREEN}Passaram: $PASS_COUNT${NC}"
echo -e "${RED}Falharam: $FAIL_COUNT${NC}"
echo ""

if [ $FAIL_COUNT -eq 0 ]; then
    echo -e "${GREEN}✅ TODOS OS TESTES PASSARAM!${NC}"
    echo ""
    echo "🎉 Integração v2.0.0 concluída com sucesso!"
    echo ""
    echo "📝 PRÓXIMOS PASSOS:"
    echo "  1. Monitorar logs por 24-48h"
    echo "  2. Coletar feedback dos usuários"
    echo "  3. Documentar issues encontrados"
    echo "  4. Considerar deploy em produção"
    exit 0
else
    echo -e "${RED}❌ ALGUNS TESTES FALHARAM${NC}"
    echo ""
    echo "⚠️  AÇÕES RECOMENDADAS:"
    echo "  1. Revise os testes que falharam"
    echo "  2. Verifique logs de erro (PHP e PostgreSQL)"
    echo "  3. Considere executar rollback se crítico"
    echo "  4. Consulte feature_v200/ROLLBACK.md"
    exit 1
fi
