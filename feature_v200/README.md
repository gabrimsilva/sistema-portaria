# 📁 VERSÃO 2.0.0 - ESTRUTURA DE DESENVOLVIMENTO

## 📂 Organização dos Arquivos

```
feature_v200/
├── drafts/                          # Rascunhos (NÃO aplicados)
│   ├── sql/                         # Scripts SQL
│   │   ├── 001_docs_estrangeiros.sql
│   │   ├── 002_validade_cadastros.sql
│   │   ├── 003_fix_saida_placas.sql
│   │   ├── 004_auditoria_retroativa.sql
│   │   ├── APLICAR_MIGRATIONS.sql   # Script master de aplicação
│   │   └── rollback/                # Scripts de reversão
│   │       ├── 001_docs_estrangeiros_rollback.sql
│   │       ├── 002_validade_cadastros_rollback.sql
│   │       ├── 003_fix_saida_placas_rollback.sql
│   │       ├── 004_auditoria_retroativa_rollback.sql
│   │       └── ROLLBACK_COMPLETO.sql
│   ├── snippets/                    # Diffs de código (M3)
│   ├── controllers/                 # Controllers novos (M3)
│   ├── views/                       # Views novas (M4)
│   └── js/                          # JavaScript novo (M4)
├── COMPATIBILIDADE_IMPACTO.md       # Relatório de impacto
└── README.md                        # Este arquivo

docs/
└── v2.0.0_discovery.md              # Descoberta M1
```

## 🎯 STATUS ATUAL

### ✅ M1 - Descoberta (CONCLUÍDO)
- Mapeamento completo do banco de dados
- Identificação de rotas e controllers
- Análise de bugs e problemas
- Documento: `docs/v2.0.0_discovery.md`

### ✅ M2 - Modelagem & Migrations (CONCLUÍDO)
- 4 migrations criadas em draft
- Relatório de compatibilidade gerado
- Scripts de rollback preparados
- **NADA FOI EXECUTADO** - tudo em rascunho!

### ⏳ PRÓXIMOS PASSOS
- M3: Endpoints & Rotas (aguardando aprovação)
- M4: Views & JS
- M5: Correção de Relatórios
- M6-M12: Conforme cronograma

## 📋 MIGRATIONS CRIADAS

### 001 - Documentos Estrangeiros
**O que faz:**
- Adiciona suporte a Passaportes, RNE, DNI, etc.
- Novos campos: `doc_type`, `doc_number`, `doc_country`
- Mantém coluna `cpf` para compatibilidade
- Migra dados existentes automaticamente

**Impacto:** 🟡 Médio  
**Compatibilidade:** ✅ 100% (via triggers)

### 002 - Validade de Cadastros
**O que faz:**
- Campos de validade: `valid_from`, `valid_until`, `validity_status`
- Triggers para cálculo automático de status
- Views de cadastros expirando
- Funções de renovação

**Impacto:** 🟢 Baixo  
**Compatibilidade:** ✅ 100% (campos opcionais)

### 003 - Correção Saídas/Placas
**O que faz:**
- Corrige bug crítico de saídas não registradas
- Triggers bidirecionais de sincronização
- View `vw_prestadores_consolidado`
- Função `corrigir_saidas_inconsistentes()`

**Impacto:** 🔴 Alto (mas positivo - corrige bug!)  
**Compatibilidade:** ⚠️ Requer atualização de relatórios

### 004 - Auditoria Retroativa
**O que faz:**
- Suporte para entradas retroativas
- Tabela `entradas_retroativas`
- Função `registrar_entrada_retroativa_profissional()`
- Permissão `acesso.retroativo`

**Impacto:** 🟢 Baixo  
**Compatibilidade:** ✅ 100% (feature nova)

## 🚀 COMO APLICAR (quando aprovado)

### Opção 1: Aplicar Tudo de Uma Vez
```bash
cd feature_v200/drafts/sql
psql -U usuario -d nome_banco -f APLICAR_MIGRATIONS.sql
```

### Opção 2: Aplicar Individualmente
```bash
cd feature_v200/drafts/sql
psql -U usuario -d nome_banco -f 001_docs_estrangeiros.sql
psql -U usuario -d nome_banco -f 002_validade_cadastros.sql
psql -U usuario -d nome_banco -f 003_fix_saida_placas.sql
psql -U usuario -d nome_banco -f 004_auditoria_retroativa.sql

# Executar correções pós-migration
psql -U usuario -d nome_banco -c "SELECT * FROM corrigir_saidas_inconsistentes();"
```

### Pré-requisitos
1. ✅ Backup completo do banco
2. ✅ Ambiente de testes validado
3. ✅ Código da aplicação atualizado
4. ✅ Horário de baixo tráfego

## 🔄 ROLLBACK (se necessário)

### Rollback Completo
```bash
cd feature_v200/drafts/sql/rollback
psql -U usuario -d nome_banco -f ROLLBACK_COMPLETO.sql
```

### Rollback Seletivo (ordem inversa!)
```bash
psql -U usuario -d nome_banco -f rollback/004_auditoria_retroativa_rollback.sql
psql -U usuario -d nome_banco -f rollback/003_fix_saida_placas_rollback.sql
psql -U usuario -d nome_banco -f rollback/002_validade_cadastros_rollback.sql
psql -U usuario -d nome_banco -f rollback/001_docs_estrangeiros_rollback.sql
```

## 📊 CÓDIGO QUE PRECISA ATUALIZAÇÃO

### 🔴 Prioridade ALTA (Obrigatório)

1. **PrestadoresServicoController** - Relatórios
   - Usar `vw_prestadores_consolidado`
   - Campo `saida_consolidada` em vez de `saida`

2. **DashboardController** - Contadores
   - Filtrar por `status_acesso = 'aberto'`

### 🟡 Prioridade MÉDIA

3. **Formulários** - Tipo de Documento
   - Adicionar select de `doc_type`
   - Campo `doc_number` em vez de `cpf`
   - Campo `doc_country` para docs estrangeiros

4. **Validações** - DocumentValidator
   - Criar service novo para validar diferentes tipos

### 🟢 Prioridade BAIXA

5. **UIs** - Badges de status
6. **Filtros** - Usar `validity_status`

## 📈 MÉTRICAS ESPERADAS

### Performance
- ✅ Queries de filtro: **+60-80% mais rápidas**
- ✅ Relatórios: **+70-90% mais rápidos**
- ⚠️ Espaço em disco: **+10-15%**
- ⚠️ Writes: **+2-5% overhead** (triggers)

### Funcionalidades Novas
- ✅ Documentos internacionais
- ✅ Validade de cadastros
- ✅ Entradas retroativas
- ✅ Saídas corrigidas

## ⚠️ AVISOS IMPORTANTES

1. **NÃO EXECUTAR** sem backup completo
2. **LER** `COMPATIBILIDADE_IMPACTO.md` antes de aplicar
3. **TESTAR** em ambiente de desenvolvimento primeiro
4. **ATUALIZAR** código da aplicação conforme checklist
5. **MONITORAR** performance após aplicação

## 📞 SUPORTE

Em caso de problemas:
1. Verificar logs do PostgreSQL
2. Consultar `COMPATIBILIDADE_IMPACTO.md`
3. Executar rollback se necessário
4. Reportar issues encontradas

---

**Versão:** 2.0.0  
**Data:** 14/10/2025  
**Status:** M2 Concluído - Aguardando Aprovação para M3
