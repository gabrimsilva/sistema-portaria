# Sistema de Controle de Acesso

## Overview
This project is an access control system for companies, developed with PHP 8+ and PostgreSQL. It manages employee and visitor access, including photo capture, user authentication, and real-time access logging. The system features a modern UX, is designed for easy deployment on Replit, and is prepared for future migration to production environments. Its business vision includes enhancing corporate security and streamlining access management processes.

## User Preferences
Preferred communication style: Simple, everyday language.

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
- **Multi-Document Support**: System accepts 8 types of documents for visitors (CPF, RG, CNH, Passaporte, RNE, DNI, CI, Outros) with country field and automatic masks.
- **Validation Relaxation**: CPF/RG validation simplified to accept any 11-digit CPF (without verifying checksums) and 7-10 character RG.

### System Design Choices
- **Data Separation**: Refactored `profissionais_renner` table into two distinct tables (`profissionais_renner` for registration data and `registro_acesso` for access control data) to improve data integrity and auditability.
- **PostgreSQL Database**: Primary relational database for data storage.
- **Local File Storage**: For captured photos.
- **Environment Configuration**: Centralized configuration in `/config` directory.
- **Audit Log Database Schema**: Migration of `audit_log` table to `timestamptz` with added `severidade`, `modulo`, and `resultado` fields, along with performance-enhancing indices.
- **Fire Brigade Photo Storage**: Field `foto_url` in `profissionais_renner` table stores corporate photos (non-biometric) for panel display, protected by realpath() validation and .htaccess rules.

## External Dependencies

- **PostgreSQL**: Primary relational database.
- **PHP PDO PostgreSQL extension**: Database connectivity.
- **Replit Platform**: Cloud-based development environment.
- **PhpSpreadsheet**: For CSV/XLSX import functionality.
- **jQuery**: For JavaScript functionalities, including cookie consent and autocomplete.
- **jQuery UI**: For autocomplete feature.