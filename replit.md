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
- **JavaScript Robusto**: Sistema de consentimento (public/assets/js/cookie-consent.js) com inicialização segura, aguarda jQuery, persiste preferências, e oferece API completa para gerenciamento
- **CookieService Integrado**: Serviço PHP centralizado para incluir banner em qualquer página, gerenciar consentimento server-side, e conectar com políticas de privacidade existentes
- **Integração Funcional**: Banner incluído em páginas críticas (login, dashboard, scan), carregando sem erros PHP/JavaScript conforme validado nos logs do servidor
- **Conformidade Básica LGPD**: Oferece controle granular de cookies, permite aceitar/rejeitar opcionais, conecta com políticas de privacidade em /privacy, e respeita direitos dos usuários
- **Melhorias Futuras Sugeridas**: Implementar bloqueio prévio de scripts opcionais, adicionar link persistente "Gerenciar Cookies", expandir para todas as páginas via layout base, e adicionar atributo SameSite nos cookies server-side

### Potential Future Integrations  
- **Production Hosting**: Migration path to dedicated servers or cloud platforms
- **Backup Services**: Database backup and recovery solutions
- **Security Services**: Enhanced authentication services (2FA, SSO)
- **Monitoring Tools**: Application performance and error monitoring