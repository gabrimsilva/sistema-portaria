#!/bin/bash
# ================================================
# SCRIPT: Aplicar Migrations v2.0.0 (M2)
# Versão: 2.0.0
# Uso: bash feature_v200/apply_m2_migrations.sh
# ================================================

set -e  # Parar em caso de erro

echo "========================================"
echo "🗄️  APLICANDO MIGRATIONS v2.0.0 (M2)"
echo "========================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se estamos no diretório correto
if [ ! -d "feature_v200/drafts/sql" ]; then
    echo -e "${RED}❌ Erro: Diretório feature_v200/drafts/sql não encontrado!${NC}"
    echo "Execute este script da raiz do projeto."
    exit 1
fi

# Verificar se migrations existem
MIGRATIONS=(
    "feature_v200/drafts/sql/001_documentos_estrangeiros.sql"
    "feature_v200/drafts/sql/002_validade_cadastros.sql"
    "feature_v200/drafts/sql/003_fix_saida_placas.sql"
    "feature_v200/drafts/sql/004_ramais_corporativos.sql"
)

echo "📋 Verificando migrations..."
for migration in "${MIGRATIONS[@]}"; do
    if [ ! -f "$migration" ]; then
        echo -e "${RED}❌ Migration não encontrada: $migration${NC}"
        exit 1
    fi
    echo -e "  ${GREEN}✓${NC} $(basename $migration)"
done
echo ""

# Perguntar confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO:${NC}"
echo "Esta operação irá modificar o banco de dados."
echo "Certifique-se de ter um backup completo antes de continuar."
echo ""
read -p "Você fez backup do banco de dados? (s/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${RED}❌ Operação cancelada pelo usuário.${NC}"
    echo "Faça backup antes de prosseguir!"
    exit 1
fi

# Detectar DATABASE_URL
if [ -z "$DATABASE_URL" ]; then
    echo -e "${RED}❌ Variável DATABASE_URL não encontrada!${NC}"
    echo "Defina DATABASE_URL antes de executar este script."
    exit 1
fi

echo -e "${GREEN}✓${NC} DATABASE_URL detectada"
echo ""

# Função para executar SQL
execute_sql() {
    local file=$1
    local name=$(basename "$file" .sql)
    
    echo "================================================"
    echo "📦 Aplicando: $name"
    echo "================================================"
    
    # Executar SQL via psql
    if psql "$DATABASE_URL" -f "$file"; then
        echo -e "${GREEN}✅ $name aplicada com sucesso!${NC}"
        return 0
    else
        echo -e "${RED}❌ Erro ao aplicar $name${NC}"
        return 1
    fi
}

# Criar log de execução
LOGFILE="feature_v200/migration_log_$(date +%Y%m%d_%H%M%S).txt"
echo "📝 Log de execução: $LOGFILE"
echo "" > "$LOGFILE"

# Aplicar migrations em ordem
echo "" | tee -a "$LOGFILE"
echo "🚀 Iniciando aplicação das migrations..." | tee -a "$LOGFILE"
echo "Hora de início: $(date)" | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"

MIGRATION_COUNTER=0
MIGRATION_TOTAL=${#MIGRATIONS[@]}

for migration in "${MIGRATIONS[@]}"; do
    MIGRATION_COUNTER=$((MIGRATION_COUNTER + 1))
    echo "" | tee -a "$LOGFILE"
    echo "[$MIGRATION_COUNTER/$MIGRATION_TOTAL] $(basename $migration)" | tee -a "$LOGFILE"
    
    if execute_sql "$migration" 2>&1 | tee -a "$LOGFILE"; then
        echo -e "${GREEN}✅ Sucesso${NC}" | tee -a "$LOGFILE"
    else
        echo -e "${RED}❌ FALHA na migration $MIGRATION_COUNTER de $MIGRATION_TOTAL${NC}" | tee -a "$LOGFILE"
        echo "" | tee -a "$LOGFILE"
        echo "⚠️  ROLLBACK NECESSÁRIO!" | tee -a "$LOGFILE"
        echo "Execute os scripts de rollback em ordem reversa:" | tee -a "$LOGFILE"
        echo "  1. feature_v200/drafts/sql/004_ramais_corporativos_rollback.sql" | tee -a "$LOGFILE"
        echo "  2. feature_v200/drafts/sql/003_fix_saida_placas_rollback.sql" | tee -a "$LOGFILE"
        echo "  3. feature_v200/drafts/sql/002_validade_cadastros_rollback.sql" | tee -a "$LOGFILE"
        echo "  4. feature_v200/drafts/sql/001_documentos_estrangeiros_rollback.sql" | tee -a "$LOGFILE"
        exit 1
    fi
    
    # Pausa de 2 segundos entre migrations
    sleep 2
done

echo "" | tee -a "$LOGFILE"
echo "================================================" | tee -a "$LOGFILE"
echo -e "${GREEN}✅ TODAS AS MIGRATIONS APLICADAS COM SUCESSO!${NC}" | tee -a "$LOGFILE"
echo "================================================" | tee -a "$LOGFILE"
echo "Hora de término: $(date)" | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"

# Validações pós-migration
echo "🔍 Executando validações..." | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"

# Verificar views criadas
echo "Verificando views criadas:" | tee -a "$LOGFILE"
psql "$DATABASE_URL" -c "
    SELECT table_name 
    FROM information_schema.views 
    WHERE table_schema = 'public' 
      AND table_name LIKE 'vw_%'
    ORDER BY table_name;
" | tee -a "$LOGFILE"

echo "" | tee -a "$LOGFILE"

# Verificar colunas novas
echo "Verificando colunas novas em visitantes_novo:" | tee -a "$LOGFILE"
psql "$DATABASE_URL" -c "
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name = 'visitantes_novo' 
      AND column_name IN ('doc_type', 'doc_number', 'doc_country', 'data_validade', 'validity_status')
    ORDER BY column_name;
" | tee -a "$LOGFILE"

echo "" | tee -a "$LOGFILE"

# Verificar função de entrada retroativa
echo "Verificando função registrar_entrada_retroativa_profissional:" | tee -a "$LOGFILE"
psql "$DATABASE_URL" -c "
    SELECT routine_name 
    FROM information_schema.routines 
    WHERE routine_schema = 'public' 
      AND routine_name = 'registrar_entrada_retroativa_profissional';
" | tee -a "$LOGFILE"

echo "" | tee -a "$LOGFILE"
echo -e "${GREEN}✅ Validações concluídas!${NC}" | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"

echo "================================================" | tee -a "$LOGFILE"
echo "📊 RESUMO DA APLICAÇÃO" | tee -a "$LOGFILE"
echo "================================================" | tee -a "$LOGFILE"
echo "Migrations aplicadas: $MIGRATION_TOTAL" | tee -a "$LOGFILE"
echo "Status: SUCESSO" | tee -a "$LOGFILE"
echo "Log completo: $LOGFILE" | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"

echo "⏭️  PRÓXIMO PASSO: Aplicar endpoints (M3)" | tee -a "$LOGFILE"
echo "   Execute: bash feature_v200/apply_m3_endpoints.sh" | tee -a "$LOGFILE"
echo "" | tee -a "$LOGFILE"
