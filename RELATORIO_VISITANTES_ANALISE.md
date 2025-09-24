# ANÁLISE - Reformulação Relatórios > Visitantes

**Data da Análise:** 24/09/2025  
**Objetivo:** Reformular página "Relatórios > Visitantes" para exibir entradas diárias com filtros específicos

## 📋 RESUMO DE ARQUITETURA

### Backend
- **Controller Principal:** `src/controllers/VisitantesNovoController.php` 
- **Tabela Ativa:** `visitantes_novo` (tabela principal em uso)
- **Tabela Legada:** `visitantes` (deprecated, usada por `_deprecated/VisitorController.php`)
- **Registros Unificados:** `registro_acesso` (sistema novo de controle de entrada/saída)
- **Serviços:** `DuplicityValidationService`, `AuthorizationService`, `AuditService`

### Frontend
- **View Existente:** `views/reports/visitantes/list.php` (já implementada)
- **Controller de Relatórios:** `src/controllers/ReportController.php::visitorsReport()`
- **Rota Atual:** Via `/reports?action=visitors` (usando ReportController)
- **Sistema de Templates:** AdminLTE 3.2 + Bootstrap 5.3

## 🔍 ANÁLISE DETALHADA

### 1. MODELO DE DADOS

#### Tabela Principal: `visitantes_novo`
```sql
Campos Disponíveis:
- id (PK)
- nome (NOT NULL)
- cpf
- empresa  
- funcionario_responsavel
- setor
- placa_veiculo
- hora_entrada (timestamp)
- hora_saida (timestamp) 
- created_at, updated_at
```

#### Tabela Unificada: `registro_acesso` 
```sql
Campos Relevantes:
- tipo (VISITANTE/PRESTADOR)
- nome, cpf, empresa, setor
- placa_veiculo, funcionario_responsavel
- entrada_at, saida_at
- observacao
```

### 2. ÍNDICES EXISTENTES

#### ✅ Índices EXISTENTES e ÚTEIS:
- `ux_visitantes_cpf_ativo` (visitantes_novo: cpf WHERE hora_saida IS NULL)
- `ux_visitantes_placa_ativa` (visitantes_novo: placa_veiculo WHERE hora_saida IS NULL)
- `idx_registro_acesso_entrada_at` (registro_acesso: entrada_at)
- `idx_registro_acesso_saida_null` (registro_acesso: saida_at WHERE saida_at IS NULL)

#### ❌ Índices em FALTA:
- Por setor: `visitantes_novo.setor`, `registro_acesso.setor`
- Por empresa: `visitantes_novo.empresa`, `registro_acesso.empresa`  
- Por responsável: `visitantes_novo.funcionario_responsavel`, `registro_acesso.funcionario_responsavel`
- Por tipo + entrada: `registro_acesso (tipo, entrada_at DESC)`

### 3. CONTROLADORES E ROTAS

#### ✅ Controller Existente:
- `VisitantesNovoController::index()` - Lista com filtros básicos (search, setor, status)
- Detecção de contexto: `/reports/` vs `/visitantes` via `getViewPath()`
- Filtros atuais: busca, setor, status (ativo/saiu)

#### ❌ Rotas em FALTA:
- Não existe rota específica `/reports/visitantes` 
- Falta filtros: data, empresa, funcionário responsável
- Falta paginação adequada
- Falta máscara de CPF

## 📊 STATUS DOS REQUISITOS

| Requisito | Status | Observações |
|-----------|--------|-------------|
| **Filtros** |
| - Data única/intervalo | ❌ FALTA | Não implementado |
| - Setor | ✅ OK | Implementado |
| - Status (aberto/finalizado) | ✅ OK | Implementado como ativo/saiu |
| - Empresa | ❌ FALTA | Não implementado |
| - Funcionário responsável | ❌ FALTA | Não implementado |
| **Colunas** |
| - Nome completo | ✅ OK | Campo `nome` disponível |
| - Setor | ✅ OK | Campo `setor` disponível |
| - Placa/"A pé" | 🔄 INCOMPLETO | Campo existe, falta lógica "A pé" |
| - Empresa | ✅ OK | Campo `empresa` disponível |
| - Funcionário responsável | ✅ OK | Campo `funcionario_responsavel` |
| - CPF mascarado | ❌ FALTA | Não implementado |
| - Data/Hora entrada | ✅ OK | Campo `hora_entrada` |
| **Paginação** | ❌ FALTA | Não implementado |
| **Botão Cadastro** | ✅ OK | Não encontrado na view atual |
| **Índices Performance** | 🔄 INCOMPLETO | Existem parciais, faltam específicos |

## 🚨 PROBLEMAS IDENTIFICADOS

1. **Duplicação de Sistemas:**
   - Tabela `visitantes` (legada) vs `visitantes_novo` (ativa)
   - `ReportController` vs `VisitantesNovoController` para relatórios

2. **Performance:**
   - Faltam índices para filtros por data, setor, empresa, responsável
   - Query sem LIMIT pode ser lenta com muitos registros

3. **Interface:**
   - View atual muito básica em `views/reports/visitantes/list.php`
   - Filtros limitados e sem data

4. **Segurança:**
   - CPF não mascarado
   - Falta verificação de permissões RBAC para visualização

## 💡 RECOMENDAÇÕES TÉCNICAS

### Backend:
1. **Usar tabela `visitantes_novo`** como fonte principal
2. **Implementar filtro por data** na query
3. **Adicionar índices** para performance
4. **Máscara de CPF** baseada em permissões do usuário
5. **Paginação** com LIMIT/OFFSET

### Frontend:
1. **Reformular** `views/reports/visitantes/list.php`
2. **Remover** qualquer botão de cadastro (não encontrado)
3. **Implementar filtros** avançados
4. **Responsividade** da tabela

### Performance:
1. **Meta:** < 300ms para consultas por data
2. **Índices estratégicos** por filtros mais usados
3. **Paginação** padrão 20 registros/página

## 🎯 CONCLUSÃO

O sistema possui base sólida com `visitantes_novo`, mas precisa de reformulação completa da interface de relatórios com foco em:
- Filtros por data (principal)
- Performance com índices adequados  
- Interface moderna com paginação
- Segurança com mascaramento de CPF