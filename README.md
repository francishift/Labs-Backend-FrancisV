# 📂 README del Proyecto (Estado Actual)

Este documento resume la arquitectura, componentes y herramientas que conforman la aplicación en su estado actual.

## 🏗️ Arquitectura General
La aplicación es un **SaaS de gestión (ERP/CRM)** construido con:
- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: Vue 3 + Inertia.js (Single Page Application)
- **Estilo**: Tailwind CSS (Vanilla CSS para componentes personalizados)
- **Visualización**: ECharts + Vue-ECharts para analítica avanzada.

## 📁 Estructura de Carpetas Clave

### Backend (`app/`)
- `Http/Controllers`: Lógica de negocio (Admin Dashboard, CRUDs).
- `Models/`: Definición de la base de datos y relaciones.
- `Helpers/`: Lógica de soporte (Cálculos de rentabilidad, normalización).

### Frontend (`resources/js/`)
- `Pages/Admin/`: Vistas principales de administración (CRUDs).
- `Components/`: Componentes Vue reutilizables (DataTable, Modals, Cards).
- `Layouts/`: Estructura base de navegación y diseño.
- `Utils/`: Utilidades compartidas (Normalización de fechas, divisas).

## 📊 Modelos de Datos Principales

| Modelo | Descripción |
| :--- | :--- |
| `User` | Gestión de usuarios y roles (Spatie Permission). |
| `Client` | Perfiles de clientes vinculados a proyectos y mantenimientos. |
| `Proyecto` | Gestión de proyectos de desarrollo con presupuestos y estados. |
| `Mantenimiento` | Servicios recurrentes (Mensual/Anual) con MRR. |
| `Extension` | Repositorio de herramientas/licencias usadas en sitios. |
| `Servicio` | Tareas específicas dentro de proyectos o mantenimientos. |
| `Configuracion` | Ajustes globales del sistema (Precios hora, descuentos). |

## 🏗️ Diagrama de Arquitectura y Entidades

Vista de pájaro de las relaciones entre los modelos de datos y los controladores que los gestionan.

```mermaid
erDiagram
    CLIENT ||--o{ PROYECTO : "tiene"
    CLIENT ||--o{ MANTENIMIENTO : "tiene"
    PROYECTO ||--o{ SERVICIO : "contiene"
    MANTENIMIENTO ||--o{ MANTENIMIENTO_SERVICIO : "contiene"
    PROYECTO }|--|{ EXTENSION : "usa"
    MANTENIMIENTO }|--|{ EXTENSION : "usa"

    CLIENT {
        string name
        string cif_nif
        string email
    }

    PROYECTO {
        string proyecto
        decimal presupuesto
        string estado
    }

    MANTENIMIENTO {
        string aplicacion
        decimal importe
        string tipo_pago
    }

    EXTENSION {
        string nombre
        decimal precio_base
    }

    subgraph "Controladores de Gestión"
        ClientController --> CLIENT
        ProyectoController --> PROYECTO
        MantenimientoController --> MANTENIMIENTO
        ExtensionController --> EXTENSION
        ServicioController --> SERVICIO
        MantenimientoServicioController --> MANTENIMIENTO_SERVICIO
    end
```

## 🛣️ Mapa de Rutas del Sistema

A continuación se detallan las rutas principales accesibles tras la autenticación, organizadas por módulo.

| Método | URI | Controlador @ Método | Descripción | Permisos |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/dashboard` | `DashboardController@index` | Vista principal con analítica y KPIs. | `auth` |
| **GET** | `/admin/proyectos` | `ProyectoController@index` | Listado y búsqueda de proyectos. | `admin, coordin` |
| **POST** | `/admin/proyectos` | `ProyectoController@store` | Crear un nuevo proyecto. | `admin, coordin` |
| **GET** | `/admin/proyectos/{id}` | `ProyectoController@show` | Detalle, edición y servicios del proyecto. | `admin, coordin` |
| **PATCH** | `/admin/proyectos/{id}` | `ProyectoController@update` | Actualizar datos del proyecto. | `admin, coordin` |
| **GET** | `/admin/mantenimientos` | `MantenimientoController@index` | Gestión de mantenimientos recurrentes. | `admin, coordin` |
| **GET** | `/admin/mantenimientos/{id}` | `MantenimientoController@show` | Detalle y facturación de mantenimiento. | `admin, coordin` |
| **GET** | `/admin/extensiones` | `ExtensionController@index` | Gestión del repositorio de extensiones. | `admin, coordin` |
| **GET** | `/admin/clientes` | `ClientController@index` | Listado maestro de clientes. | `admin` |
| **POST** | `/admin/clientes/import` | `ClientController@import` | Importación masiva desde Excel. | `admin` |
| **GET** | `/admin/usuarios` | `UserController@index` | Gestión de accesos y roles (RBAC). | `admin` |
| **GET** | `/admin/configuracion` | `SettingsController@index` | Ajustes globales de precios e IVA. | `admin` |
| **GET** | `/profile` | `ProfileController@edit` | Gestión de perfil de usuario actual. | `auth` |

## 🛠️ Dependencias Importantes

### Frontend
- **Inertia.js**: Puente entre Laravel y Vue.
- **ECharts / Vue-ECharts**: Motor de gráficos del Dashboard.
- **Heroicons**: Set de iconos oficial.
- **Lato (Fontsource)**: Tipografía base del sistema.

### Backend
- **Spatie Permission**: Sistema de roles y permisos (Admin, Coordinador).
- **Ziggy**: Inyección de rutas de Laravel en JavaScript.
- **Maatwebsite Excel**: Exportación de reportes y datos.

---
> [!NOTE]
> Este documento debe actualizarse al añadir nuevos módulos core o cambiar dependencias estructurales.
