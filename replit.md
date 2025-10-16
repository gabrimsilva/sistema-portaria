# Sistema de Controle de Acesso

## Overview
This project is an access control system for companies, developed with PHP 8+ and PostgreSQL. It manages employee and visitor access, including photo capture, user authentication, and real-time access logging. The system features a modern UX, is designed for easy deployment on Replit, and is prepared for future migration to production environments. Its business vision includes enhancing corporate security and streamlining access management processes.

## User Preferences
Preferred communication style: Simple, everyday language.

## Project Progress (Updated: Oct 15, 2025)

### Configuration Module Stages
- **ETAPA 1 - Organização**: ████████████ 100% ✅ CONCLUÍDA
  - Formulário completo com validações em tempo real
  - Upload de logo (2MB max, PNG/JPG) com preview
  - Validação CNPJ com formatação automática
  - CSRF protection ativada
- **ETAPA 2 - Sites/Locais**: ████████████ 100% ✅ CONCLUÍDA
  - CRUD completo de sites e setores
  - Horários de funcionamento (7 dias)
  - Gestão de feriados (globais/específicos)
- **ETAPA 3 - RBAC**: ████████████ 100% ✅ CONCLUÍDA
  - Matriz interativa de permissões (5 roles × 7 módulos)
  - Proteções de segurança (Admin mantém config.* e CPF não mascarado)
  - Modal de usuários por perfil
- **ETAPA 4 - Autenticação**: ████████████ 100% ✅ CONCLUÍDA
  - Formulário completo de políticas de autenticação
  - Validações em tempo real (senha mínima, expiração, timeout)
  - Toggles 2FA/SSO (placeholder UI preparado)
  - APIs GET/PUT funcionais com CSRF protection
- **ETAPA 5 - Auditoria**: ████████████ 100% ✅ CONCLUÍDA
  - Filtros avançados (usuário, entidade, ação, datas)
  - Paginação eficiente com prev/next e contador de registros
  - Carregamento dinâmico de usuários no filtro
  - Export CSV funcional com filtros aplicados
  - Modal de detalhes com visualização diff antes/depois
- **ETAPA 6 - Higiene UX**: ████████████ 100% ✅ CONCLUÍDA
  - CleanupManager com AbortController para cancelar requests pendentes
  - TabNavigationCleanup para detecção automática de mudança de módulo
  - Gerenciamento de timers (setTimeout/setInterval) com cleanup automático
  - Rastreamento e remoção de event listeners
  - Fechamento automático de modais ao navegar entre seções
  - Estados isolados por aba/módulo (sem vazamento de cache)
- **ETAPA 7 - Segurança Extra**: ████████████ 100% ✅ CONCLUÍDA

**Total concluído: 100% do plano original (7 de 7 etapas completas) 🎉**

### v2.0.0 Enhancements (Oct 15, 2025) ✅ COMPLETO
- **M4.1 - Widget Cadastros Expirando**: Dashboard com tabs Visitantes/Prestadores, badges coloridos (ativo/expirando/expirado), renovação rápida +30 dias via AJAX
- **M4.2 - Seletor de Documento Internacional**: 8 tipos de documentos (CPF, RG, CNH, PASSAPORTE, RNE, DNI, CI, OUTROS), validação JavaScript + PHP por tipo, campo país (ISO-3166)
- **M4.3 - Modal Entrada Retroativa**: Interface para registrar entradas passadas, validação de data, audit trail com campo `is_retroactive=true`
- **M4.4 - Gestão de Validade UI**: Modais dinâmicos (renovar, bloquear, desbloquear), ValidadeController com 6 endpoints, renovação visitante +45d / prestador +60d
- **M6 - Bug Crítico Saídas Prestadores**: Fix arquitetura híbrida - saída atualiza AMBAS tabelas (`prestadores_servico.saida` + `registro_acesso.saida_at`), view consolidada funcional
- **M7.1 - CRUD Ramais**: Tabela `ramais` com gestão completa (adicionar, editar, remover), export CSV, unique constraints (ramal, professional_id)
- **M7.2 - Painel Público Brigada**: Controller público `/painel/brigada` com fotos circulares, auto-refresh 60s, segurança LGPD (realpath validation)
- **M8 - Validação Final**: 3 testes completos (visitante estrangeiro Passaporte US, prestador validade híbrida, segurança + 47 índices de performance)

### Validation Relaxation (Oct 16, 2025) ✅ CONCLUÍDO
- **CPF/RG Validation Simplified**: Validação de dígitos verificadores desabilitada a pedido da portaria
  - **Problema**: CPFs inválidos impediam registro de visitantes/prestadores na portaria
  - **Solução**: Sistema agora aceita qualquer CPF com 11 dígitos (sem validar dígitos verificadores)
  - **Arquivos modificados**: 
    - `src/utils/CpfValidator.php` - validateAndNormalize() simplificado
    - `src/services/DocumentValidator.php` - nota sobre validação desabilitada
    - `public/assets/js/document-validator.js` - validateCPFLocal() simplificado
  - **RG**: Mantém validação simplificada (aceita 7-10 caracteres)
  - **Formatação**: Mantida para exibição consistente
  - **Segurança**: Sem problemas observados, validação de comprimento mantida

### Bug Fixes & UX Improvements (Oct 16, 2025) ✅ CONCLUÍDO
- **Edit Workflow Standardization**: Sistema de edição alinhado ao padrão UX de Profissionais Renner:
  - Botão amarelo redireciona para página separada de edição (form.php) com todos os campos editáveis
  - Campos de saída (hora_saida/saida) presentes nas páginas de edição
  - Actions 'edit' e 'update' já configuradas no router (index.php)
  - Mesmo padrão aplicado em Visitantes, Prestadores e Profissionais Renner
- **Navigation Standardization**: Todas as views de relatórios agora usam NavigationService::renderSidebar():
  - `/reports/visitantes/list.php` e `form.php`
  - `/reports/prestadores-servico/list.php` e `form.php`
  - `/reports/profissionais-renner/form.php`
  - Elimina duplicação de código e garante navegação consistente
- **Critical Bug Fix - Exit Registration**: Corrigido bug crítico que impedia salvar hora de saída:
  - **Root Cause**: Formulários enviavam dados para rotas antigas + validações excessivas bloqueavam submit
  - **Forms Fixed**: Actions corrigidos para `/reports/visitantes` e `/reports/prestadores-servico`
  - **Controllers Simplified**: Removidas validações de setor/CPF obrigatórios no update() (não eram required no form)
  - **Temporal Validation**: Entrada/saída tornadas opcionais durante edição, normalizadas apenas se fornecidas
  - **Duplicity Check**: Validação de duplicidade removida do update() para permitir edição livre de saídas
  - **Navigation Fixed**: Botões "Voltar" corrigidos para rotas de relatórios
- **UI/UX Cleanup**: Removida implementação modal incorreta, código duplicado eliminado

## System Architecture

### UI/UX Decisions
- **Template System**: Simple PHP template engine with includes for layout.
- **Modern UX**: Contemporary interface design with responsive layouts.
- **Photo Capture**: Browser-based photo capture for employees and visitors.
- **Form Standardization**: Standardized form components with robust validation, masks, and reusability.
- **LGPD Cookie Banner**: Responsive cookie banner with detailed preferences and granular control.
- **Autocomplete UI**: jQuery UI-based autocomplete for access registration, enhancing data entry speed and accuracy.
- **Color-Coded Dashboard**: Dashboard cards use Bootstrap color classes (bg-primary/blue for Profissional Renner, bg-success/green for Visitante, bg-warning/yellow for Prestador, bg-danger/red for Total) matching registration button colors for visual consistency.
- **Dual-Metric System**: Each dashboard card displays two metrics: "Ativos Agora" (counts all active entries including previous days without final exit) and "Registrados Hoje" (counts only today's new entries, resets daily with São Paulo timezone).
- **Brigadista Visual Identification**: Active fire brigade members (brigadistas) are visually identified in the dashboard with a red badge containing a fire extinguisher icon next to their name in the "Pessoas na Empresa" section.
- **Fire Brigade Panel Photos**: The public Fire Brigade Panel (`/painel/brigada`) displays circular photos of active brigade members with LGPD-compliant photo storage in `/public/uploads/profissionais/`, secured with realpath() canonical validation and .htaccess protection against path traversal attacks.

### Technical Implementations
- **MVC Pattern**: Simple Model-View-Controller architecture.
- **PHP 8+**: Utilizes modern PHP features.
- **Session Management**: PHP session-based authentication and authorization.
- **File Upload Handling**: System for photo storage and management.
- **Error Handling**: Centralized JavaScript error handling with safe wrappers and global event listeners.
- **CSV/XLSX Import System**: Robust import functionality with drag-and-drop UI, data validation, and security measures for professional data.
- **Access Registration Autocomplete**: API endpoint for searching professionals and dynamic autocomplete for forms, preventing data duplication.
- **Audit Log System**: Enhanced audit logging with automatic inference of severity and module, and advanced filtering capabilities.
- **CSV Export System**: Enterprise-grade CSV export for all access reports (Visitantes, Prestadores de Serviço, Profissionais Renner) with full filter preservation, CSV formula injection protection (sanitizeForCsv), LGPD-compliant CPF masking, UTF-8 BOM for Excel compatibility, and semicolon delimiter.
- **PostgreSQL Boolean Handling**: Robust normalization of PostgreSQL boolean values which can be returned as 't'/'f' strings, true/false booleans, or 1/0 integers depending on PDO driver, ensuring consistent strict boolean comparisons in views.
- **Hygiene UX System (ETAPA 6)**: Comprehensive resource management system preventing memory leaks and optimizing navigation. CleanupManager tracks and cancels pending AJAX requests via AbortController, manages timers (setTimeout/setInterval) with automatic cleanup, removes event listeners on module changes, and closes Bootstrap modals/tooltips. TabNavigationCleanup detects URL changes (popstate, pushState) and triggers cleanup when switching between modules, ensuring isolated states per tab without resource leakage. All JavaScript modules (ramais.js, widget-cadastros-expirando.js, gestao-validade.js) integrate with graceful fallback when CleanupManager is unavailable.

### Feature Specifications
- **User Roles**: Role-based access control (e.g., 'porteiro').
- **Password Security**: Hashed password storage.
- **Email-based Login**: Unique email addresses as user identifiers.
- **Data Retention**: Enterprise-grade security with SQL injection prevention and RBAC.
- **LGPD Compliance**: Comprehensive framework including consent, privacy notices, and cookie policy.
- **Biometric Infrastructure**: Pre-configured secure biometric storage with AES-256-GCM encryption (currently inactive).
- **Security Testing**: Automated CI/CD pipeline with runtime tests and static code scanning.

### System Design Choices
- **Data Separation**: Refactored `profissionais_renner` table into two distinct tables (`profissionais_renner` for registration data and `registro_acesso` for access control data) to improve data integrity and auditability.
- **PostgreSQL Database**: Primary relational database for data storage.
- **Local File Storage**: For captured photos.
- **Environment Configuration**: Centralized configuration in `/config` directory.
- **Audit Log Database Schema**: Migration of `audit_log` table to `timestamptz` with added `severidade`, `modulo`, and `resultado` fields, along with performance-enhancing indices.
- **Fire Brigade Photo Storage**: Field `foto_url` in `profissionais_renner` table stores corporate photos (non-biometric) for panel display, protected by realpath() validation and .htaccess rules preventing literal and percent-encoded path traversal.

## External Dependencies

- **PostgreSQL**: Primary relational database.
- **PHP PDO PostgreSQL extension**: Database connectivity.
- **Replit Platform**: Cloud-based development environment.
- **PhpSpreadsheet**: For CSV/XLSX import functionality.
- **jQuery**: For JavaScript functionalities, including cookie consent and autocomplete.
- **jQuery UI**: For autocomplete feature.