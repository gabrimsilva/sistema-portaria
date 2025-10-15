#!/bin/bash
# ================================================
# SCRIPT: Aplicar RBAC Permissions v2.0.0 (M7)
# Versão: 2.0.0
# Uso: bash feature_v200/apply_m7_rbac.sh
# ================================================

set -e

echo "========================================"
echo "🔐 APLICANDO RBAC v2.0.0 (M7)"
echo "========================================"
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Verificar DATABASE_URL
if [ -z "$DATABASE_URL" ]; then
    echo -e "${RED}❌ Erro: DATABASE_URL não encontrada!${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} DATABASE_URL detectada"
echo ""

# Confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO:${NC}"
echo "Esta operação irá:"
echo "  - Criar 5 novas permissões no banco"
echo "  - Associar permissões aos roles (13 associações)"
echo "  - Corrigir permissões nos controllers (10 correções)"
echo ""
read -p "Você fez backup do banco? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${RED}❌ Operação cancelada.${NC}"
    echo "Faça backup antes de prosseguir!"
    exit 1
fi

# ================================================
# PARTE 1: APLICAR SQL DE PERMISSÕES
# ================================================

echo "================================================"
echo "📦 PARTE 1: Aplicando SQL de Permissões"
echo "================================================"
echo ""

SQL_FILE="feature_v200/drafts/sql/005_rbac_permissions_v2.sql"

if [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}❌ Arquivo não encontrado: $SQL_FILE${NC}"
    exit 1
fi

echo "Executando: $SQL_FILE"

if psql "$DATABASE_URL" -f "$SQL_FILE"; then
    echo -e "${GREEN}✅ SQL executado com sucesso!${NC}"
else
    echo -e "${RED}❌ Erro ao executar SQL${NC}"
    echo ""
    echo "⚠️  ROLLBACK NECESSÁRIO!"
    echo "Execute: psql \"\$DATABASE_URL\" -f feature_v200/drafts/sql/005_rbac_permissions_v2_rollback.sql"
    exit 1
fi

echo ""

# Verificar permissões criadas
echo "🔍 Verificando permissões criadas..."
PERM_COUNT=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM permissions WHERE key IN ('documentos.manage', 'entrada.retroativa', 'validade.manage', 'ramais.manage', 'reports.advanced_filters');" 2>/dev/null || echo "0")

if [ "$PERM_COUNT" -eq "5" ]; then
    echo -e "${GREEN}✅ 5 permissões criadas${NC}"
else
    echo -e "${RED}❌ Erro: Esperado 5 permissões, encontrado $PERM_COUNT${NC}"
    exit 1
fi

# Verificar associações criadas
echo "🔍 Verificando associações criadas..."
ASSOC_COUNT=$(psql "$DATABASE_URL" -t -c "SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE p.key IN ('documentos.manage', 'entrada.retroativa', 'validade.manage', 'ramais.manage', 'reports.advanced_filters');" 2>/dev/null || echo "0")

if [ "$ASSOC_COUNT" -eq "13" ]; then
    echo -e "${GREEN}✅ 13 associações criadas${NC}"
else
    echo -e "${RED}❌ Erro: Esperado 13 associações, encontrado $ASSOC_COUNT${NC}"
    exit 1
fi

echo ""

# ================================================
# PARTE 2: CORRIGIR PERMISSÕES NOS CONTROLLERS
# ================================================

echo "================================================"
echo "🔧 PARTE 2: Corrigindo Permissões nos Controllers"
echo "================================================"
echo ""

echo -e "${YELLOW}⚠️  ATENÇÃO: Correções Manuais Necessárias${NC}"
echo ""
echo "Abra o arquivo: feature_v200/drafts/rbac/diff_permissions_fix.md"
echo ""
echo "Aplique as 10 correções nos controllers:"
echo "  1. DocumentoController.php (1 correção)"
echo "  2. EntradaRetroativaController.php (2 correções)"
echo "  3. ValidadeController.php (3 correções)"
echo "  4. RamalController.php (4 correções)"
echo ""

read -p "Você aplicou TODAS as correções? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Aplique as correções antes de testar!${NC}"
    echo ""
    echo "SQL de permissões JÁ FOI APLICADO."
    echo "Termine as correções nos controllers."
    exit 0
fi

# ================================================
# PARTE 3: TESTES DE VALIDAÇÃO
# ================================================

echo ""
echo "================================================"
echo "🧪 PARTE 3: Testes de Validação"
echo "================================================"
echo ""

echo "📝 Execute estes testes manualmente:"
echo ""
echo "1️⃣  Teste documentos.manage"
echo "   - Login como Recepção (deve funcionar)"
echo "   - POST /api/documentos/validar"
echo ""
echo "2️⃣  Teste entrada.retroativa"
echo "   - Login como Segurança (deve funcionar)"
echo "   - POST /api/profissionais/entrada-retroativa"
echo ""
echo "3️⃣  Teste validade.manage"
echo "   - Login como RH (deve funcionar)"
echo "   - POST /api/cadastros/validade/renovar"
echo ""
echo "4️⃣  Teste ramais.manage"
echo "   - Login como Admin (deve funcionar)"
echo "   - POST /api/ramais/adicionar"
echo ""
echo "5️⃣  Teste permissão negada"
echo "   - Login como Porteiro"
echo "   - Qualquer endpoint acima (deve retornar 403)"
echo ""

read -p "Todos os testes passaram? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}⚠️  Revise os testes que falharam${NC}"
    echo ""
    echo "Se necessário, consulte:"
    echo "  - feature_v200/drafts/rbac/matriz_rbac_v2.md"
    echo "  - feature_v200/drafts/rbac/permissoes_v2.md"
    exit 0
fi

# ================================================
# RESUMO FINAL
# ================================================

echo ""
echo "================================================"
echo -e "${GREEN}✅ RBAC v2.0.0 APLICADO COM SUCESSO!${NC}"
echo "================================================"
echo ""
echo "📊 RESUMO:"
echo "  ✅ 5 permissões criadas"
echo "  ✅ 13 associações role-permission"
echo "  ✅ 10 correções em controllers"
echo "  ✅ Testes validados"
echo ""
echo "📋 MATRIZ DE PERMISSÕES:"
echo "  - Administrador: 5/5 novas permissões"
echo "  - Segurança: 2/5 (entrada.retroativa, reports.advanced_filters)"
echo "  - Recepção: 2/5 (documentos.manage, validade.manage)"
echo "  - RH: 4/5 (documentos, validade, ramais, reports)"
echo "  - Porteiro: 0/5"
echo ""
echo "📁 Documentação:"
echo "  - feature_v200/drafts/rbac/permissoes_v2.md"
echo "  - feature_v200/drafts/rbac/matriz_rbac_v2.md"
echo "  - feature_v200/drafts/rbac/diff_permissions_fix.md"
echo ""
echo "⏭️  PRÓXIMO PASSO: M8 - Testes de Segurança"
echo ""
