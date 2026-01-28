# 🚀 Labs Backend - Sistema de Gestión Empresarial (SaaS)

[English Version Below](#-labs-backend---enterprise-management-system-saas)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-1.x-9553E9?style=for-the-badge&logo=inertia)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css)](https://tailwindcss.com)

Una solución **ERP y CRM completa y premium**, diseñada para agencias digitales y freelancers. Este sistema proporciona un panel centralizado para gestionar proyectos, servicios de mantenimiento recurrentes, relaciones con clientes y rendimiento del equipo con analítica avanzada.

---

## ✨ Funcionalidades Clave

- **📊 Dashboard de Analítica Avanzada**: KPIs en tiempo real que rastrean la rentabilidad de proyectos, Ingresos Recurrentes Mensuales (MRR) y asignación de recursos usando ECharts.
- **💼 CRM Integral**: Perfiles de clientes multifacéticos con historiales detallados de proyectos, contratos de mantenimiento y registros de comunicación.
- **🏗️ Gestión del Ciclo de Vida de Proyectos**: Control granular sobre las etapas del proyecto, desde la estimación y presupuesto hasta la entrega final y seguimiento de tareas.
- **🔄 Automatización de Servicios Recurrentes**: Gestión automatizada de mantenimientos mensuales y anuales, incluyendo cálculos automáticos de rentabilidad.
- **🔌 Ecosistema de Extensiones**: Repositorio centralizado de herramientas y licencias utilizadas en diferentes proyectos para optimizar la gestión de costes.
- **🔐 RBAC Seguro**: Control de acceso detallado mediante el sistema de permisos de Spatie (roles de Admin, Coordinador y Visor).
- **📥 Importación Inteligente de Datos**: Sistema de importación masiva para datos de clientes heredados con normalización automática.
- **🌓 Interfaz Adaptativa**: Interfaz totalmente responsiva con modo oscuro integrado y navegación de alto rendimiento impulsada por Inertia.js.
- **⚡ Optimización de Alto Rendimiento**: Indexación estratégica de base de datos (`estado`), almacenamiento en caché del Dashboard con invalidación automática basada en eventos (Cache Busting), carga selectiva de columnas y optimización de bundles de Vite.
- **🔔 Notificaciones Globales**: Sistema de feedback centralizado mediante "Toast Messages" para una experiencia de usuario fluida y consistente.
- **🛡️ Inmutabilidad de Datos**: Sistema de snapshots de precios que garantiza la integridad de los informes históricos ante cambios en las tarifas globales.
- **📄 Visor de PDF Profesional**: Generación de informes financieros detallados para **Clientes, Proyectos y Mantenimientos**, con visor embebido optimizado para móviles y Safari.
- **📦 Integración con Holded (CRM/ERP)**: Sincronización bidireccional de presupuestos y contactos mediante API oficial, con persistencia en base de datos local para máximo rendimiento.
- **🎨 Refinamiento UI**: Interfaz coherente sistema de gestión de extensiones alineado con mantenimientos y mejoras en la legibilidad de tablas.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | Laravel 12, PHP 8.3, Eloquent ORM |
| **Frontend** | Vue 3 (Composition API), Inertia.js |
| **Estilo** | Tailwind CSS, Headless UI |
| **Analítica** | Apache ECharts, Vue-ECharts |
| **Auth/Seguridad** | Laravel Breeze, Spatie Permissions |
| **Automatización** | Composables Personalizados, Búsqueda Debounced, Filtrado Avanzado |

---

## 🏗️ Arquitectura del Sistema

La aplicación sigue un enfoque de arquitectura limpia, separando la lógica de negocio de la representación a través de Helpers personalizados y Vue Composables para maximizar la reutilización.

```mermaid
graph TD
    A[Web Pública] --> B[Inertia.js SPA]
    B --> C[Controladores Laravel]
    C --> D[Lógica de Negocio & Helpers]
    D --> E[Modelos Eloquent]
    E --> F[(PostgreSQL/MySQL)]
    C --> G[Permisos Spatie]
```

---

## 🚀 Instalación

### Requisitos

- PHP 8.3+
- Node.js 20+
- Composer
- MySQL/MariaDB

### Pasos

1. **Clonar el repositorio**
   ```bash
   git clone git@github.com:tu-usuario-git/Labs-Backend-FrancisV.git
   cd Labs-Backend-FrancisV
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Configurar Entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Ejecutar Migraciones y Seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Iniciar Servidor de Desarrollo**
   ```bash
   npm run dev
   # En otra terminal:
   php artisan serve
   ```

---

## 🛠️ Uso como Plantilla (Nuevos Proyectos)

Si deseas usar este proyecto como base para una nueva aplicación manteniendo el diseño y componentes:

### 1. Limpieza de Lógica Antigua
Para eliminar los datos del proyecto anterior y empezar de cero:

```bash
# Eliminar migraciones específicas (Mantener users y sessions)
rm database/migrations/202*_*_create_proyectos_table.php
rm database/migrations/202*_*_create_mantenimientos_table.php
# ... etc

# Limpiar Controladores y Modelos
rm app/Http/Controllers/Admin/*
rm app/Models/Proyecto.php
rm app/Models/Mantenimiento.php
# ... etc
```

### 2. Reinicio de Base de Datos
```bash
php artisan migrate:fresh --seed
```

### 3. Personalización Core
Modifica el `APP_NAME` en el `.env` y el logo en `resources/js/Components/ApplicationLogo.vue`.

---

## 👨‍💻 Autor

**Francis Valenzuela**
- GitHub: [@tu-usuario-git](https://github.com/tu-usuario-git)
- Web: [www.TU_DOMINIO](https://www.TU_DOMINIO)

---

# 🚀 Labs Backend - Enterprise Management System (SaaS)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-1.x-9553E9?style=for-the-badge&logo=inertia)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css)](https://tailwindcss.com)

A premium, full-featured **Enterprise Resource Planning (ERP) and CRM solution** designed for digital agencies and freelancers. This system provides a centralized dashboard for managing projects, recurring maintenance services, client relationships, and team performance with advanced analytics.

---

## ✨ Key Features

- **📊 Advanced Analytics Dashboard**: Real-time KPIs tracking project profitability, Monthly Recurring Revenue (MRR), and resource allocation using ECharts.
- **💼 Comprehensive CRM**: Multi-faceted client profiles with detailed histories of projects, maintenance contracts, and communication logs.
- **🏗️ Project Lifecycle Management**: Granular control over project stages, from estimation and budgeting to final delivery and service tracking.
- **🔄 Recurring Service Automation**: Automated management of monthly and annual maintenance services, including automated profitability calculations.
- **🔌 Extension Ecosystem**: Centralized repository of tools and licenses used across different projects for optimized cost management.
- **🔐 Secure RBAC**: Fine-grained access control using Spatie's permission system (Admin, Coordinator, Viewer roles).
- **📥 Smart Data Import**: Bulk import system for legacy client data with automatic normalization.
- **🌓 Adaptive UI**: Fully responsive interface with built-in dark mode and high-performance Inertia.js-driven navigation.
- **⚡ High-Performance Optimization**: Strategic database indexing (`status`), Dashboard caching with event-driven invalidation (Cache Busting), selective column loading, and Vite bundle optimization.
- **🔔 Global Notifications**: Centralized toast message system for a fluid and consistent user feedback experience.
- **🛡️ Data Immutability**: Price snapshot system ensuring the integrity of historical reports against changes in global rates.
- **📄 Proactive Export (PDF)**: Detailed financial report generation for **Clients, Projects, and Maintenance**, with an embedded viewer optimized for mobile and Safari compatibility.
- **📦 Holded Integration (CRM/ERP)**: Real-time synchronization of estimates and contacts via official API, with local database persistence for maximum performance and integrated document viewing.
- **🎨 UI Refinement**: Consistent interface for extension management aligned with maintenance modules and improved table readability.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend** | Laravel 12, PHP 8.3, Eloquent ORM |
| **Frontend** | Vue 3 (Composition API), Inertia.js |
| **Styling** | Tailwind CSS, Headless UI |
| **Analytics** | Apache ECharts, Vue-ECharts |
| **Auth/Security** | Laravel Breeze, Spatie Permissions |
| **Automation** | Custom Composables, Debounced Search, Advanced Filtering |

---

## 🏗️ System Architecture

The application follows a clean-architecture approach, separating business logic from representation through custom Helpers and Vue Composables for maximum reusability.

```mermaid
graph TD
    A[Public Web] --> B[Inertia.js SPA]
    B --> C[Laravel Controllers]
    C --> D[Business Logic & Helpers]
    D --> E[Eloquent Models]
    E --> F[(PostgreSQL/MySQL)]
    C --> G[Spatie Permissions]
```

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.3+
- Node.js 20+
- Composer
- MySQL/MariaDB

### Installation

1. **Clone the repository**
   ```bash
   git clone git@github.com:tu-usuario-git/Labs-Backend-FrancisV.git
   cd Labs-Backend-FrancisV
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Start Development Server**
   ```bash
   npm run dev
   # In another terminal:
   php artisan serve
   ```

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Francis Valenzuela**
- GitHub: [@tu-usuario-git](https://github.com/tu-usuario-git)
- Web: [www.TU_DOMINIO](https://www.TU_DOMINIO)

---
> *This repository is part of my professional portfolio. Feel free to explore the codebase and reach out for collaborations!*
