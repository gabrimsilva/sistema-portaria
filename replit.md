# Sistema de Controle de Acesso

## Overview
This project is an access control system for companies, developed with PHP 8+ and PostgreSQL. It manages employee and visitor access, including photo capture, user authentication, and real-time access logging. The system features a modern UX, is designed for easy deployment, and aims to enhance corporate security and streamline access management processes.

## User Preferences
- **Communication style**: Simple, everyday language.
- **Work methodology**: Incremental testing approach - test one feature at a time, investigate deeply with detailed debugging, fix individual issues without creating long error lists. Focus on one problem until fully resolved before moving to the next.
- **Debugging approach**: Use console logs, database queries, and step-by-step verification to trace issues to their root cause before implementing fixes.

## System Architecture

### UI/UX Decisions
- **Modern UX**: Contemporary interface design with responsive layouts, standardized form components, and browser-based photo capture.
- **LGPD Cookie Banner**: Responsive cookie banner with detailed preferences and granular control.
- **Autocomplete UI**: jQuery UI-based autocomplete for access registration.
- **Color-Coded Dashboard**: Dashboard cards use Bootstrap color classes for visual consistency, displaying "Ativos Agora" (all active entries) and "Registrados Hoje" (today's new entries).
- **Brigadista Visual Identification**: Active fire brigade members are visually identified in the dashboard and on a public panel with circular photos and specific badging.

### Technical Implementations
- **MVC Pattern**: Simple Model-View-Controller architecture with PHP 8+.
- **Session Management**: PHP session-based authentication and authorization.
- **File Upload Handling**: System for photo storage and management with security validations.
- **Error Handling**: Centralized JavaScript error handling.
- **CSV/XLSX Import System**: Robust import functionality with drag-and-drop UI and data validation.
- **Access Registration Autocomplete**: API endpoint for searching professionals and dynamic autocomplete.
- **Audit Log System**: Enhanced audit logging with automatic inference and advanced filtering.
- **CSV Export System**: Enterprise-grade CSV export for all access reports with filter preservation, formula injection protection, and LGPD-compliant masking.
- **PostgreSQL Boolean Handling**: Robust normalization of PostgreSQL boolean values.
- **Hygiene UX System**: Comprehensive resource management system preventing memory leaks by tracking and canceling AJAX requests, managing timers, and removing event listeners.

### Feature Specifications
- **User Roles**: Role-based access control.
- **Password Security**: Hashed password storage.
- **Email-based Login**: Unique email addresses as user identifiers.
- **Data Retention**: Enterprise-grade security with SQL injection prevention and RBAC.
- **LGPD Compliance**: Comprehensive framework including consent and privacy notices.
- **Biometric Infrastructure**: Pre-configured secure biometric storage (currently inactive).
- **Security Testing**: Automated CI/CD pipeline with runtime tests and static code scanning.
- **Multi-Document Support**: Accepts 8 types of documents for visitors/service providers with conditional validation, country field, and type-aware normalization.
- **Pré-Cadastros System**: Reusable pre-registration system (v2.0.0) with 1-year validity for recurring visitors and service providers, separating registration data from access events, featuring validity tracking, automatic renovation, and RBAC permissions.
- **Duplicate Entry Prevention**: Robust validation to prevent duplicate open entries for professionals.
- **Retroactive Entry Detection**: Automatic detection of retroactive entries for professionals, requiring justification.
- **Ramais Module**: Complete CRUD system for managing internal phone directory with Excel/CSV import, public read-only interface, and RBAC permissions.

### System Design Choices
- **Data Separation**: Refactored data into distinct tables for registration and access events (`profissionais_renner`, `registro_acesso`, `visitantes_cadastro`, `visitantes_registros`, `prestadores_cadastro`, `prestadores_registros`) to improve data integrity.
- **PostgreSQL Database**: Primary relational database.
- **Local File Storage**: For captured photos, protected by realpath() validation and .htaccess rules.
- **Environment Configuration**: Centralized configuration in `/config` directory.
- **Audit Log Database Schema**: Migration of `audit_log` table to `timestamptz` with added fields and performance-enhancing indices.
- **Pré-Cadastros Performance Optimization**: 18 specialized indexes and 4 derived views for validity status calculation.

## External Dependencies

- **PostgreSQL**: Primary relational database.
- **PHP PDO PostgreSQL extension**: Database connectivity.
- **Replit Platform**: Cloud-based development environment.
- **PhpSpreadsheet**: For CSV/XLSX import functionality.
- **jQuery**: For JavaScript functionalities.
- **jQuery UI**: For autocomplete feature.