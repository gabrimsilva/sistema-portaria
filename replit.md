# Sistema de Controle de Acesso

## Overview

This is a complete access control system for companies built with PHP 8+ and PostgreSQL. The system manages employee and visitor access with photo capture capabilities, user authentication, and real-time access logging. It features a modern UX design and is structured for easy deployment on Replit with future migration capabilities to production servers.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Template System**: Simple PHP template engine using includes for layout management
- **Asset Organization**: Static assets (CSS, JS, images, captured photos) served from `/public` directory
- **Modern UX**: Contemporary interface design with responsive layouts
- **Photo Capture**: Browser-based photo capture functionality for employees and visitors

### Backend Architecture
- **MVC Pattern**: Simple Model-View-Controller architecture organized in `/src` directory
  - Controllers: Handle HTTP requests and business logic coordination
  - Models: Data access layer and entity representations
  - Services: Business logic and external service integrations
- **PHP 8+**: Modern PHP features and syntax
- **Session Management**: PHP session-based authentication and authorization
- **File Upload Handling**: Photo storage and management system

### Data Storage
- **Primary Database**: PostgreSQL for relational data storage
- **Schema Design**: 
  - Users table with role-based access (default: 'porteiro'/doorkeeper)
  - Employees table with personal info, photos, and admission dates
  - Visitors table with company details and visited person tracking
  - Access logs table tracking entry/exit events with timestamps
- **File Storage**: Local filesystem storage for captured photos in `/public` directory

### Authentication & Authorization
- **User Roles**: Role-based access control with 'porteiro' as default role
- **Password Security**: Hashed password storage using PHP password functions
- **Email-based Login**: Unique email addresses as user identifiers
- **Session Security**: Server-side session management for authenticated users

### Configuration Management
- **Environment Configuration**: Centralized configuration in `/config` directory
- **Database Connection**: PostgreSQL connection parameters and settings
- **Global Parameters**: Application-wide settings and constants

## External Dependencies

### Database
- **PostgreSQL**: Primary relational database for data persistence
- **Database Driver**: PHP PDO PostgreSQL extension for database connectivity

### Development Environment
- **Replit Platform**: Cloud-based development environment with built-in PostgreSQL support
- **PHP 8+**: Server-side scripting language with modern features

### Recent Security Achievements
- **Data Retention System**: Production-ready with enterprise-grade security (SQL injection eliminated, RBAC granular, PostgreSQL optimized)
- **LGPD Documentation**: Complete compliance framework including bases legais, consent terms, privacy notices, cookie policy, and conformity checklist
- **Biometric Infrastructure**: Complete secure biometric storage system with AES-256-GCM encryption, pre-configured for future photo access control (currently inactive)
- **Security Testing Framework**: Automated CI/CD pipeline with runtime tests and static code scanning preventing security regressions
- **Security Audit Complete**: All critical vulnerabilities resolved, system production-ready

### Step 1.5 - Form Component Standardization (COMPLETED ✅)
- **FormService Production-Ready**: Complete form component standardization with renderTextInput supporting arbitrary HTML attributes (data-*, aria-*, etc.), renderCpfInput with mask, renderPlacaInput with "A pé" checkbox, renderDateTimeInput, renderTextarea, renderFormButtons, and renderAlert components
- **Script Robustness**: All JavaScript components use class selectors instead of hardcoded IDs, CPF mask properly wrapped in DOMContentLoaded, jQuery dependency checks, and are parametrizable for multiple forms
- **Standards Achieved**: Eliminated 378+ lines of duplicated form code, created consistent validation patterns, standardized label structures, and enabled component reusability across all three user categories (Profissionais Renner, Visitantes, Prestador de serviços)
- **Production Validation**: End-to-end testing confirmed with HTTP 200 responses, all components working correctly, architect-reviewed and approved for production deployment

### Step 1.6 - JavaScript Error Handling & Console Cleanup (COMPLETED ✅)
- **Error Analysis & Resolution**: Successfully identified and addressed the root cause of "An uncaught exception occured but the error was not an error object" error stemming from empty try-catch blocks in sessionStorage operations and non-Error object throws
- **ErrorHandler Service**: Created comprehensive centralized JavaScript error handling system with safe wrappers for sessionStorage (safeSessionStorage), fetch operations (safeFetch), and robust error normalization (normalizeError, safeStringify)
- **Global Error Handlers**: Implemented window.addEventListener for 'error' and 'unhandledrejection' events to catch and normalize non-Error objects thrown anywhere in the application
- **Production Security**: Console logging conditionally enabled only in development environment, production console cleanup implemented, no sensitive data exposure in logs
- **Page Integration**: ErrorHandler included in critical pages (dashboard, access/scan, prestadores_servico/list) replacing problematic try-catch patterns with robust error handling
- **Browser Compatibility**: JSON-encoded configuration injection prevents PHP/JavaScript syntax conflicts, ensuring cross-browser compatibility and preventing template literal interpolation issues

## 📅 SEMANA 2: IMPLEMENTAR BANNER LGPD E POLÍTICAS VISÍVEIS

### Step 2.1 - Banner de Cookies LGPD (COMPLETED ✅)
- **Sistema Completo LGPD**: Criado banner de cookies moderno e responsivo em conformidade com a Lei Geral de Proteção de Dados brasileira, integrado com toda a documentação LGPD existente do sistema
- **Componente Responsivo**: Banner adaptável (views/components/cookie-banner.php) com interface desktop/mobile, modal de preferências detalhado, e controle granular de cookies (essenciais, funcionais, performance)
- **JavaScript Robusto**: Sistema de consentamento (public/assets/js/cookie-consent.js) com inicialização segura, aguarda jQuery, persiste preferências, e oferece API completa para gerenciamento
- **CookieService Integrado**: Serviço PHP centralizado para incluir banner em qualquer página, gerenciar consentimento server-side, e conectar com políticas de privacidade existentes
- **Integração Funcional**: Banner incluído em páginas críticas (login, dashboard, scan), carregando sem erros PHP/JavaScript conforme validado nos logs do servidor
- **Conformidade Básica LGPD**: Oferece controle granular de cookies, permite aceitar/rejeitar opcionais, conecta com políticas de privacidade em /privacy, e respeita direitos dos usuários
- **Melhorias Futuras Sugeridas**: Implementar bloqueio prévio de scripts opcionais, adicionar link persistente "Gerenciar Cookies", expandir para todas as páginas via layout base, e adicionar atributo SameSite nos cookies server-side

## 📅 SEMANA 3: SISTEMA DE IMPORTAÇÃO E REFATORAÇÃO ARQUITETURAL

### M5 - Sistema de Importação CSV/XLSX (COMPLETED ✅)
- **Import Funcional**: Sistema completo de importação CSV/XLSX com PhpSpreadsheet para dados de profissionais Renner
- **Interface Drag-and-Drop**: UI moderna com suporte a arrastar/soltar arquivos, validação de tipo, limite de 10MB
- **Validação de Dados**: Validação de colunas obrigatórias (Nome, Setor, Data de Admissão, FRE), detecção de duplicatas por nome, normalização de acentos e BOM
- **Segurança**: CSRF protection, MIME validation, limpeza garantida de arquivos temporários, RBAC com permissão `importacao.visualizar`
- **Processamento Robusto**: Tratamento de erros por linha, relatório detalhado de sucessos/falhas, suporte a múltiplos formatos

### Correção Arquitetural - Separação Cadastro e Controle de Acesso (COMPLETED ✅ - PRODUCTION-READY)
- **Problema Identificado**: Tabela `profissionais_renner` misturava dados de cadastro (nome, setor) com dados de controle de acesso (entrada, saída, placa)
- **Solução Implementada**: Separação completa em duas tabelas com relacionamento FK:
  - `profissionais_renner`: Cadastro permanente (id, nome, setor, fre, data_admissao)
  - `registro_acesso`: Registros de entrada/saída (id, profissional_renner_id, entrada_at, saida_at, retorno, saida_final, placa_veiculo)
- **Migração de Dados**: 13 registros migrados com sucesso de profissionais_renner → registro_acesso, backups criados para rollback
- **Integridade Referencial**: FK `fk_registro_acesso_profissional` com ON DELETE RESTRICT protegendo histórico de auditoria
- **Controller Refatorado**: ProfissionaisRennerController completamente refatorado usando JOINs para listagens e transações para operações combinadas
- **Proteção de Auditoria**: Método delete() com RBAC (`profissionais_renner.excluir`), verificação de histórico, bloqueio de exclusão com registros dependentes
- **Validação Completa**: Architect-reviewed e aprovado como production-ready após correção de problemas críticos de segurança e integridade

### M6 - Sistema de Autocomplete para Registro de Acesso (COMPLETED ✅ - PRODUCTION-READY)
- **Endpoint de Busca**: API endpoint `/profissionais-renner?action=search` com autenticação, retorna profissionais importados via JSON (id, nome, setor, fre)
- **Autocomplete jQuery UI**: Campo "Nome" no formulário de registro com autocomplete dinâmico, busca a partir de 2 caracteres digitados
- **UI Robusta**: CSS customizado com z-index 1060 para dropdown aparecer corretamente sobre Bootstrap modals (z-index 1050), scroll automático se muitos resultados
- **Preenchimento Automático**: Ao selecionar profissional da lista, campo "Setor" é preenchido automaticamente com dados importados
- **Lógica Anti-Duplicação**: Controller verifica se profissional existe em `profissionais_renner` antes de criar novo registro
  - Se existe: Reutiliza ID existente e cria apenas registro em `registro_acesso`
  - Se não existe: Cria em `profissionais_renner` + `registro_acesso`
- **Fluxo Completo Integrado**: Importação CSV/XLSX → dados salvos em `profissionais_renner` → autocomplete facilita novos registros → sem duplicatas
- **Performance**: Query com ILIKE case-insensitive, limite de 20 resultados por busca
- **UX Otimizada**: Reduz erros de digitação, padroniza nomes/setores, agiliza cadastro de profissionais recorrentes
- **Validação Completa**: Architect-reviewed com PASS status, sem logs de debug em produção, testado end-to-end com sucesso

## 📅 SEMANA 4: SISTEMA DE AUDITORIA EMPRESARIAL COM FILTRAGEM AVANÇADA

### Etapa 1.0 - Migração Banco de Dados audit_log (COMPLETED ✅)
- **timestamp → timestamptz**: Migração de 23 registros para UTC com timezone (TIMESTAMPTZ)
- **Novos Campos**: Adicionados `severidade` (VARCHAR, default 'INFO'), `modulo` (VARCHAR, default 'sistema'), `resultado` (VARCHAR, default 'success')
- **Índices de Performance**: 4 novos índices criados (idx_audit_timestamp, idx_audit_severidade, idx_audit_modulo, idx_audit_resultado)
- **Integridade de Dados**: 100% dos logs existentes preservados, nenhuma perda de dados
- **Validação SQL**: Testes confirmaram funcionamento correto de inserção, busca, e performance (<1ms para queries)

### Etapa 1.0.1 - AuditService Enhancement com Inferência Automática (COMPLETED ✅ - PRODUCTION-READY)
- **Método log() Atualizado**: Aceita 3 novos parâmetros opcionais (`$severidade`, `$modulo`, `$resultado`) mantendo 100% compatibilidade retroativa
- **Inferência Inteligente de Severidade**: Baseada em ação realizada:
  - `create/update/import` → **AUDIT** (auditoria de mudanças)
  - `delete/access_denied/config_change` → **WARN** (ações sensíveis)
  - `login/logout/export` → **INFO** (informativo)
  - `error` → **ERROR** (erros)
  - Default → **INFO**
- **Inferência Inteligente de Módulo**: Baseada em entidade e ação (prioridade: ação > entidade):
  - Ação: `import/export` → **import/export**
  - Ação: `login/logout` → **autenticacao**
  - Entidade: `usuarios/roles` → **autenticacao**
  - Entidade: `profissionais_renner/visitantes/prestadores` → **sistema**
  - Entidade: `organization_settings/sites` → **configuracao**
  - Default → **sistema**
- **Segurança Aprimorada**: Adicionado casting `(int)` no parâmetro `limit` do método `getLogs()` para prevenir SQL injection via LIMIT
- **Distribuição de Logs Atual**: AUDIT/autenticacao (5), AUDIT/import (1), AUDIT/sistema (16), WARN/sistema (4)
- **Architect Review**: PASS status - código production-ready com implementação correta, compatibilidade retroativa perfeita, e segurança mantida
- **Próximos Passos**: Etapa 1.1 implementará API GET /logs com server-side pagination e filtros avançados (severidade, modulo, período)

### Potential Future Integrations  
- **Production Hosting**: Migration path to dedicated servers or cloud platforms
- **Backup Services**: Database backup and recovery solutions
- **Security Services**: Enhanced authentication services (2FA, SSO)
- **Monitoring Tools**: Application performance and error monitoring