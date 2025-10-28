# Sistema de Controle de Acesso

## Overview
This project is an access control system for companies, developed with PHP 8+ and PostgreSQL. It manages employee and visitor access, including photo capture, user authentication, and real-time access logging. The system features a modern UX, is designed for easy deployment on Replit, and is prepared for future migration to production environments. Its business vision includes enhancing corporate security and streamlining access management processes.

## User Preferences
- **Communication style**: Simple, everyday language.
- **Work methodology**: Incremental testing approach - test one feature at a time, investigate deeply with detailed debugging, fix individual issues without creating long error lists. Focus on one problem until fully resolved before moving to the next.
- **Debugging approach**: Use console logs, database queries, and step-by-step verification to trace issues to their root cause before implementing fixes.

## System Architecture

### UI/UX Decisions
- **Template System**: Simple PHP template engine with includes for layout.
- **Modern UX**: Contemporary interface design with responsive layouts.
- **Photo Capture**: Browser-based photo capture for employees and visitors.
- **Form Standardization**: Standardized form components with robust validation, masks, and reusability. Visitor edit form includes multi-document fields (Tipo de Documento, Número, País) matching dashboard modal format.
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
- **PostgreSQL Boolean Handling**: Robust normalization of PostgreSQL boolean values ensuring consistent strict boolean comparisons in views.
- **Hygiene UX System**: Comprehensive resource management system preventing memory leaks and optimizing navigation. CleanupManager tracks and cancels pending AJAX requests via AbortController, manages timers, removes event listeners, and closes Bootstrap modals/tooltips. TabNavigationCleanup detects URL changes and triggers cleanup when switching between modules, ensuring isolated states per tab without resource leakage.

### Feature Specifications
- **User Roles**: Role-based access control (e.g., 'porteiro').
- **Password Security**: Hashed password storage.
- **Email-based Login**: Unique email addresses as user identifiers.
- **Data Retention**: Enterprise-grade security with SQL injection prevention and RBAC.
- **LGPD Compliance**: Comprehensive framework including consent, privacy notices, and cookie policy.
- **Biometric Infrastructure**: Pre-configured secure biometric storage with AES-256-GCM encryption (currently inactive).
- **Security Testing**: Automated CI/CD pipeline with runtime tests and static code scanning.
- **Multi-Document Support**: System accepts 8 types of documents for visitors (CPF, RG, CNH, Passaporte, RNE, DNI, CI, Outros) with country field and automatic masks. Document normalization is type-aware: Brazilian documents (CPF/RG/CNH) use digits-only, while international documents preserve alphanumeric format.
- **Validation Relaxation**: CPF/RG validation simplified to accept any 11-digit CPF (without verifying checksums) and 7-10 character RG.
- **Document Validation System**: Conditional validation based on document type with centralized `getEffectiveDocType()` function normalizing empty type as CPF (default). Backend validates "Número do documento é obrigatório" for all document types.
- **Pré-Cadastros System**: Reusable pre-registration system (v2.0.0) with 1-year validity for recurring visitors and service providers. Separates registration data (cadastros) from access events (registros) via 1:N relationship. Features validity status tracking (valid/expiring/expired), automatic renovation, soft delete protection, and RBAC permissions (Admin 5/5, Porteiro 4/5 without delete). Reduces entry time by 95% for recurring access (2 min → 5 sec).

### System Design Choices
- **Data Separation**: Refactored `profissionais_renner` table into two distinct tables (`profissionais_renner` for registration data and `registro_acesso` for access control data) to improve data integrity and auditability. Pré-Cadastros v2.0.0 extends this pattern with 4 tables: `visitantes_cadastro`, `visitantes_registros`, `prestadores_cadastro`, `prestadores_registros` (1:N relationship via `cadastro_id` foreign key with DELETE RESTRICT). **Important nomenclature note**: cadastro tables use "observacoes" (plural) while registros tables use "observacao_entrada/observacao_saida" (singular) - this is intentional and controllers respect these differences.
- **PostgreSQL Database**: Primary relational database for data storage.
- **Local File Storage**: For captured photos.
- **Environment Configuration**: Centralized configuration in `/config` directory.
- **Audit Log Database Schema**: Migration of `audit_log` table to `timestamptz` with added `severidade`, `modulo`, and `resultado` fields, along with performance-enhancing indices.
- **Fire Brigade Photo Storage**: Field `foto_url` in `profissionais_renner` table stores corporate photos (non-biometric) for panel display, protected by realpath() validation and .htaccess rules.
- **Pré-Cadastros Performance Optimization**: 18 specialized indexes (GIN full-text search on names, B-tree on doc_type/doc_number/valid_until/ativo/deleted_at for cadastros; B-tree on cadastro_id/entrada_at/saida_at for registros). 4 derived views (`vw_*_cadastro_status`) calculate validity status (valid/expiring/expired) with day counters for proactive management.

## External Dependencies

- **PostgreSQL**: Primary relational database.
- **PHP PDO PostgreSQL extension**: Database connectivity.
- **Replit Platform**: Cloud-based development environment.
- **PhpSpreadsheet**: For CSV/XLSX import functionality.
- **jQuery**: For JavaScript functionalities, including cookie consent and autocomplete.
- **jQuery UI**: For autocomplete feature.

## Recent Changes

- **Ramais Module (Internal Phone Directory)** (October 28, 2025): Complete CRUD system for managing internal phone directory with Excel/CSV import functionality. Database table `ramais` (id, area, nome, ramal_celular, tipo, observacoes, ativo, timestamps). Features: public read-only interface at `/ramais` (accessible without login), RBAC permissions (all profiles can view via ramais.read, only RH/Admin can edit via ramais.write), sectoral grouping UI with real-time search, drag-and-drop Excel import with 3-column format (Área|Nome|Ramal), automatic type detection (interno=4 digits, externo=10+ digits), soft delete, audit logging. Controller uses `ramal_celular as ramal` alias in all SELECTs for backward compatibility with views. Architect-approved production-ready after schema consistency fixes.
- **Photo Capture System - Architecture Refinement** (October 27, 2025): Reorganized photo capture workflow for clearer separation of concerns. Photo capture now happens exclusively in pré-cadastro pages (`/pre-cadastros/visitantes/form.php` and `/pre-cadastros/prestadores/form.php`) using PhotoCapture.js component. Dashboard modals display existing photos (read-only) when selecting pre-registered entries via autocomplete. Implementation: foto_url field included in autocomplete API responses (PreCadastrosVisitantesController and PreCadastrosPrestadoresController search() methods), JavaScript automatically shows/hides photo preview based on data availability, seamless integration with existing autocomplete workflow. Storage remains in /public/uploads/visitantes/ and /public/uploads/prestadores/ with security validations (2MB limit, MIME type check, CSRF protection).
- **Pré-Cadastros System v2.0.0** (October 20, 2025): Complete implementation of reusable pre-registration system for Visitantes and Prestadores de Serviço with 1-year validity. System features 4 new tables (cadastros + registros separation), 18 performance indexes, 4 derived views for validity status, multi-document support (8 types), LGPD-compliant masking, and RBAC permissions (Admin full access, Porteiro without delete). Dashboard ready for future autocomplete integration. Reduces entry time from 2 minutes to 5 seconds for recurring visitors/providers (-95% improvement).
- **Multi-Document System for Prestadores de Serviço** (October 17, 2025): Complete implementation of multi-document support for service providers matching Visitantes module. System accepts 8 document types (CPF, RG, CNH, Passaporte, RNE, DNI, CI, Outros) with conditional validation, type-aware normalization (Brazilian documents digits-only, international alphanumeric uppercase), and LGPD-compliant masking. Constraint chk_prestadores_doc_consistency enforced: doc_type and doc_number must be BOTH NULL (legacy CPF mode) or BOTH filled. Dashboard modal updated to send doc_type/doc_number/doc_country fields. Query fixed: saida_consolidada AS saida for report rendering. Duplicity validation corrected to only check CPF when doc_type is NULL or 'CPF', avoiding errors with non-CPF documents.
- **Retroactive Entry Detection for Professionals** (October 16, 2025): Implemented automatic detection of retroactive entries for Profissionais Renner. When user selects a past date/time in the entry field, system automatically displays an "Observação/Justificativa" field (required). Backend validates mandatory justification and logs it in audit trail with metadata (entrada_retroativa flag, justificativa text, diferenca_tempo).
- Resolved critical PHP reference bug causing duplicate entries in visitor reports by adding `unset($visitante)` after `foreach` loop with reference parameter.
- Implemented multi-document support in visitor edit form matching dashboard modal (type selector, number field, country field with dynamic visibility).
- Fixed database schema constraints: expanded `doc_country` from `character(2)` to `varchar(100)` in both `visitantes_novo` and `prestadores_servico` tables, handling view dependencies with transactional migration.
- Upgraded visitor report to show "Documento" column instead of "CPF" with intelligent display (badges for document types, LGPD-compliant masking showing only last 4 characters).
- Resolved professional import failures (174 errors): fixed constraint violation by explicitly inserting NULL for doc_type/doc_number fields to override default values during CSV import.