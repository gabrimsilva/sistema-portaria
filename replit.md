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

### Potential Future Integrations  
- **Production Hosting**: Migration path to dedicated servers or cloud platforms
- **Backup Services**: Database backup and recovery solutions
- **Security Services**: Enhanced authentication services (2FA, SSO)
- **Monitoring Tools**: Application performance and error monitoring