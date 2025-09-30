# Sistema de Controle de Acesso

## Overview
This project is an access control system for companies, developed with PHP 8+ and PostgreSQL. It manages employee and visitor access, including photo capture, user authentication, and real-time access logging. The system features a modern UX, is designed for easy deployment on Replit, and is prepared for future migration to production environments. Its business vision includes enhancing corporate security and streamlining access management processes.

## User Preferences
Preferred communication style: Simple, everyday language.

## Project Progress (Updated: Sep 30, 2025)

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
- **ETAPA 5 - Auditoria**: ██████░░░░░░ 50% (básica implementada, filtros avançados pendentes)
- **ETAPA 6 - Higiene UX**: ░░░░░░░░░░░░ 0% (pendente)
- **ETAPA 7 - Segurança Extra**: ████░░░░░░░░ 30% (básica implementada)

**Total concluído: ~70% do plano original (4 de 7 etapas completas)**

## System Architecture

### UI/UX Decisions
- **Template System**: Simple PHP template engine with includes for layout.
- **Modern UX**: Contemporary interface design with responsive layouts.
- **Photo Capture**: Browser-based photo capture for employees and visitors.
- **Form Standardization**: Standardized form components with robust validation, masks, and reusability.
- **LGPD Cookie Banner**: Responsive cookie banner with detailed preferences and granular control.
- **Autocomplete UI**: jQuery UI-based autocomplete for access registration, enhancing data entry speed and accuracy.

### Technical Implementations
- **MVC Pattern**: Simple Model-View-Controller architecture.
- **PHP 8+**: Utilizes modern PHP features.
- **Session Management**: PHP session-based authentication and authorization.
- **File Upload Handling**: System for photo storage and management.
- **Error Handling**: Centralized JavaScript error handling with safe wrappers and global event listeners.
- **CSV/XLSX Import System**: Robust import functionality with drag-and-drop UI, data validation, and security measures for professional data.
- **Access Registration Autocomplete**: API endpoint for searching professionals and dynamic autocomplete for forms, preventing data duplication.
- **Audit Log System**: Enhanced audit logging with automatic inference of severity and module, and advanced filtering capabilities.

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

## External Dependencies

- **PostgreSQL**: Primary relational database.
- **PHP PDO PostgreSQL extension**: Database connectivity.
- **Replit Platform**: Cloud-based development environment.
- **PhpSpreadsheet**: For CSV/XLSX import functionality.
- **jQuery**: For JavaScript functionalities, including cookie consent and autocomplete.
- **jQuery UI**: For autocomplete feature.