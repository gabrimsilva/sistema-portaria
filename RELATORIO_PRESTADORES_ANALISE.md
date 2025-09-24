# Relatório de Análise - Prestadores de Serviços

## Data da Análise
**Data:** 24/09/2025  
**Fase:** A - ANÁLISE (somente leitura)  
**Objetivo:** Mapear infraestrutura atual para reformulação da tela Relatórios → Prestadores de Serviços

---

## 1. Resumo da Arquitetura

### 1.1 Backend (PHP)
- **Controller Principal:** `src/controllers/PrestadoresServicoController.php`
- **Detecção de Contexto:** Implementada via `getViewPath()` e `getBaseRoute()` 
  - Regular: `/prestadores-servico` → `views/prestadores_servico/`
  - Relatórios: `/reports/prestadores-servico` → `views/reports/prestadores_servico/`
- **Banco de Dados:** PostgreSQL com classe `Database`
- **Serviços Auxiliares:** 
  - `DuplicityValidationService` para validações
  - `DateTimeValidator` para timestamps
  - `CpfValidator` para CPF

### 1.2 Frontend (PHP + JavaScript)
- **Template Engine:** PHP includes com AdminLTE
- **Views Atuais:**
  - `views/prestadores_servico/list.php` (principal)
  - `views/reports/prestadores_servico/list.php` (relatórios)
- **JavaScript:** jQuery para modais, AJAX e manipulação de DOM

---

## 2. Estrutura do Banco de Dados

### 2.1 Tabela: `prestadores_servico`
```sql
Campos principais:
- id (integer, PK)
- entrada (timestamp, nullable) 
- saida (timestamp, nullable)
- nome (varchar 255, NOT NULL)
- cpf (varchar 14, nullable)
- empresa (varchar 255, nullable) 
- setor (varchar 255, nullable)
- funcionario_responsavel (varchar 255, nullable, default '')
- placa_veiculo (varchar 20, nullable)
- observacao (text, nullable)
- created_at (timestamp, default CURRENT_TIMESTAMP)
- updated_at (timestamp, default CURRENT_TIMESTAMP)
```

### 2.2 Índices Existentes
```sql
1. prestadores_servico_pkey (id) - PRIMARY KEY
2. ux_prestadores_cpf_ativo (cpf WHERE saida IS NULL) - UNIQUE  
3. ux_prestadores_placa_ativa (placa_veiculo WHERE saida IS NULL AND placa != 'APE') - UNIQUE
```

### 2.3 Status Business Logic
- **Aguardando:** `entrada IS NULL AND saida IS NULL`
- **Trabalhando:** `entrada IS NOT NULL AND saida IS NULL` 
- **Finalizado:** `saida IS NOT NULL`

---

## 3. Análise de Implementação

| Funcionalidade | Status | Observações |
|---|---|---|
| **FILTROS** |
| - Data única | ❌ FALTA | Não implementado filtro por data |
| - Intervalo de datas | ❌ FALTA | Não implementado |
| - Setor | ✅ OK | Campo `setor` disponível, dropdown implementado |
| - Status (Em aberto/Finalizado) | ❌ FALTA | Lógica existe, falta filtro na interface |
| - Empresa | ✅ OK | Campo `empresa` disponível, dropdown implementado |
| - Funcionário responsável | ✅ OK | Campo `funcionario_responsavel` disponível |
| **COLUNAS** |
| - Nome completo | ✅ OK | Campo `nome` |
| - Setor | ✅ OK | Campo `setor` |
| - Placa/"A pé" | 🔄 INCOMPLETO | Campo existe, falta lógica "A pé" |
| - Empresa | ✅ OK | Campo `empresa` |
| - Funcionário responsável | ✅ OK | Campo `funcionario_responsavel` |
| - CPF mascarado | ❌ FALTA | Não implementado mascaramento |
| - Data/Hora entrada | ✅ OK | Campo `entrada` |
| **INTERFACE** |
| - Paginação | ❌ FALTA | Não implementada |
| - Ordenação por entrada_at DESC | 🔄 INCOMPLETO | Ordena por `created_at DESC` |
| - Remoção botão cadastro | 🔄 IDENTIFICADO | Botão "Novo Prestador" presente na linha 132-135 |
| **ÍNDICES NECESSÁRIOS** |
| - (entrada DESC) | ❌ FALTA | Para ordenação otimizada |
| - (setor) | ❌ FALTA | Para filtro por setor |
| - (empresa) | ❌ FALTA | Para filtro por empresa |
| - (funcionario_responsavel) | ❌ FALTA | Para filtro por responsável |

---

## 4. Auditoria de "Sujeiras" Frontend

### 4.1 Riscos Identificados por Tela

#### `views/prestadores_servico/list.php` (Principal)
- **Modal de Edição:** JavaScript listeners em `.btn-editar` (linha 379)
- **Estados Loading:** Classes `.loading` podem persistir (linha 387)
- **AJAX Calls:** Requests para `/prestadores-servico?action=get_data` podem vazar
- **Checkbox APE:** Estado do checkbox pode persistir entre navegações
- **Timers:** Possível debounce não limpo em formulários

#### `views/reports/prestadores_servico/list.php` (Relatórios)
- **Filtros Persistentes:** QueryParams podem permanecer ao trocar de tela
- **Event Listeners:** Listeners globais podem acumular
- **Cache de Filtros:** Dados de setores/empresas podem ser reutilizados incorretamente

### 4.2 Pontos Críticos de Cleanup

| Componente | Risco | Localização |
|---|---|---|
| **Modal Edit** | Event listeners `.btn-editar` | Linha 379 `list.php` |
| **AJAX Loading** | Classes `.loading` não removidas | Linha 387 `list.php` |
| **Checkbox APE** | Estado `checked` persistente | Linha 254 `form.php` |
| **Filtros GET** | QueryParams vazando entre telas | Formulário filtros |
| **Dropdowns** | Cache indevido de setores/empresas | Controller `index()` |

### 4.3 Navegação Cruzada (Cross-Navigation)
- **Problemas Detectados:** 
  - Filtros de uma tela podem aparecer em outra
  - Estados de modais podem vazar
  - Loading states podem não ser limpos
  - Requests AJAX pendentes podem retornar fora de contexto

---

## 5. Rotas e Endpoints Atuais

### 5.1 Endpoints Identificados
```
GET  /prestadores-servico              → index()
GET  /prestadores-servico?action=new   → create() 
POST /prestadores-servico?action=save  → save()
POST /prestadores-servico?action=save_ajax → saveAjax()
GET  /prestadores-servico?action=get_data&id={id} → getData()
POST /prestadores-servico?action=update_ajax → updateAjax()
```

### 5.2 Botão para Remoção
- **Localização:** `views/prestadores_servico/list.php`, linha 132-135
- **Código:** 
```php
<a href="/prestadores-servico?action=new" class="btn btn-primary">
    <i class="fas fa-plus"></i> Novo Prestador
</a>
```

---

## 6. Recomendações de Índices

### 6.1 Performance Crítica (< 300ms)
```sql
-- Para ordenação por entrada (DESC)
CREATE INDEX idx_prestadores_entrada_desc ON prestadores_servico (entrada DESC NULLS LAST);

-- Para filtros por setor
CREATE INDEX idx_prestadores_setor ON prestadores_servico (setor);

-- Para filtros por empresa  
CREATE INDEX idx_prestadores_empresa ON prestadores_servico (empresa);

-- Para filtros por funcionário responsável
CREATE INDEX idx_prestadores_responsavel ON prestadores_servico (funcionario_responsavel);

-- Índice combinado para data + status
CREATE INDEX idx_prestadores_data_status ON prestadores_servico (DATE(entrada), saida);
```

---

## 7. Conclusões

### 7.1 Estado Atual
- **Infraestrutura:** ✅ Sólida, controller com detecção de contexto
- **Banco de Dados:** ✅ Estrutura adequada, precisa de índices
- **Frontend:** 🔄 Funcional, mas com riscos de "sujeira"

### 7.2 Próximos Passos
1. **FASE B:** Criar plano detalhado de implementação
2. **Focar em:** Filtros por data, status, paginação e limpeza de estado
3. **Prioridade:** Performance com índices e higiene entre telas

---

**Status:** ✅ ANÁLISE COMPLETA  
**Próxima Fase:** B - PROPOSTA (plano + diffs)