# Documentación de Lógica de Negocio Completa

Este documento detalla todas las reglas de negocio, cálculos financieros, estándares de código y arquitecturas implementadas en el sistema de gestión de Labs.

> [!TIP]
> **Para nuevas aplicaciones:** Las secciones 1, 6 y 7 constituyen el "Core" reutilizable del sistema. El resto es lógica específica de este proyecto.

---

## 1. Control de Acceso y Usuarios

El sistema utiliza **Spatie Laravel-Permission** para gestionar roles y permisos.

### 1.1 Roles del Sistema
- **Admin**: Acceso total a todas las secciones, incluyendo gestión de usuarios, configuración global y sincronización con Holded.
- **Coordinador**: Administra Clientes, Proyectos, Mantenimientos y Software. No puede gestionar usuarios ni configuraciones globales del sistema.
- **Visor**: Acceso de solo lectura a proyectos y mantenimientos.

### 1.2 Seguridad de Usuario
- **Cambio Obligatorio de Password**: Al crear un usuario desde el panel, se le asigna una clave temporal y se activa el flag `must_change_password`. El sistema fuerza el cambio en el primer login.
- **Notificaciones**: Las comunicaciones de verificación de email y recuperación de contraseña están totalmente localizadas al español con plantillas personalizadas.

---

## 2. Gestión de Gastos Fijos (Infraestructura)

### 2.1 Modelado de Software y Hosting (S/H)
El sistema utiliza un modelo de **Costo Compartido** para repercutir los gastos de licencias y servidores en los proyectos activos.
- **Software**: Se registran licencias individuales con su coste anual. Solo las marcadas como "Activa" computan para el cálculo.
- **Software Anual**: Suma de todos los costes de software activos.
- **Snapshot de Overhead**: Para evitar que cambios futuros en el precio del software alteren el balance histórico, cada proyecto/mantenimiento guarda en su creación el `coste_software_anual` y el `%_software` vigentes.

### 2.2 Extensiones y Herramientas
- **Repositorio**: Inventario de plugins, temas o herramientas específicas.
- **Precio Aplicado**: Al vincular una extensión, el sistema copia su precio actual a la relación (`pivot`). Esto permite subir el precio global de una extensión sin afectar a lo ya facturado a clientes antiguos.

---

## 3. Mantenimientos (SaaS & Recurrencia)

### 3.1 Ingresos y Prorrateo
Los ingresos de mantenimiento se calculan según el `tipo_pago`:
- **Mensual**: El `importe` se registra íntegro cada mes.
- **Trimestral**: Se divide el `importe` entre 3 para obtener la repercusión mensual estadística.
- **Anual**: Se divide el `importe` entre 12 para los cálculos de balance mensual.
- **Persistencia Histórica**: El sistema utiliza la tabla `mantenimiento_precios` para registrar cada cambio de tarifa. Esto garantiza que el balance de meses pasados use el precio que el cliente pagaba en ese momento, no el actual.

### 3.2 Tasa Horaria Reducida
- Se aplica un coeficiente (`descuento_mantenimiento`) definido en configuración sobre el precio/hora base.
- Todos los servicios realizados dentro de un contrato de mantenimiento se computan automáticamente con esta tasa reducida, a menos que se especifique un precio fijo.

---

## 4. Proyectos y Balance Financiero

### 4.1 Cálculo de Rentabilidad (Balance)
El beneficio real de un Proyecto o Mantenimiento se calcula como:
`Beneficio = Ingresos - (Coste Servicios + Coste Extensiones + Repercusión Software)`

- **Coste Servicios**: `Sum(minutos / 60 * precio_hora)`.
- **Repercusión Software**: `(Total Software Anual * % Repercusión) / 100`.

### 4.2 Estados y Ciclo de Vida
- **En proceso**: Computan para las estadísticas de "MRR" y carga actual.
- **Finalizado**: Requieren `fecha_fin`. Se mantienen en el histórico para el balance anual.
- **Borrado Lógico**: La mayoría de entidades usan `SoftDeletes` para preservar la integridad referencial de los informes financieros.

### 4.3 Centralización de Cálculos (Single Source of Truth)
Para garantizar que el panel web y los PDFs exportados muestren datos idénticos, la lógica financiera se ha centralizado en el modelo `Client` mediante atributos computados:
- `active_projects_budget`: Suma de presupuestos de proyectos "En proceso".
- `monthly_maintenance_income`: Suma prorrateada de ingresos por mantenimientos activos.

---

## 5. Integración con Holded (API Sync)

### 5.1 Sincronización de Presupuestos
- **Estrategia**: Sincronización local proactiva a través del módulo de Presupuestos. El sistema opera prioritariamente sobre la base de datos local para evitar latencias de API en las vistas de clientes.
- **Almacenamiento en Google Drive**:
    - Los PDFs se descargan de Holded y se almacenan automáticamente en una carpeta dedicada de Google Drive (`/Presupuestos/{Año}/{DocNum}.pdf`).
    - **Estructura Dinámica**: `{Año}/VENTAS/{Trimestre}tri/{docNumber}.pdf` (dentro de la carpeta raíz configurada `GOOGLE_DRIVE_FOLDER_ID_FACTURAS`).
    - **Cálculo de Trimestre**: Automático basado en la fecha de emisión (`ceil(mes / 3)`).
    - **Resiliencia**: El sistema busca o crea las carpetas recursivamente (Año -> VENTAS -> Trimestre) en la carpeta de facturas designada.
    - **Configuración**: Se usa el disco `google_facturas` para aislar este almacenamiento.
    - **Independencia**: Se guarda el ID de archivo de Google Drive localmente. Las lecturas subsiguientes se sirven directamente desde Drive usando la API nativa, eliminando la dependencia de Holded para la visualización de documentos históricos.
    - **Backups**: El almacenamiento usa un disco aislado (`google_presupuestos`) para no interferir con las copias de seguridad del sistema.
- **Mapeo de Datos**: Se almacenan el ID, contacto, estados y el objeto JSON original (`raw_data`).

### 5.2 Sincronización de Clientes
- Los contactos de Holded se vinculan mediante el `CIF/NIF` o email.
- **Rendimiento**: Se ha eliminado la sincronización automática en cada carga del listado para garantizar una navegación instantánea.
- **Integridad de Datos**: Existe un comando de mantenimiento `php artisan holded:fix-contacts` para recuperar IDs de contacto perdidos escaneando los presupuestos existentes.

### 5.3 Vinculación Proyectos - Presupuestos
- **Asociación**: Se permite vincular un presupuesto de Holded a un Proyecto. La selección se filtra dinámicamente según el cliente del proyecto.
- **Visualización**: El PDF del presupuesto asociado es accesible directamente desde la ficha del proyecto (sección Información General), utilizando el visor PDF unificado.
- **Seguridad**:
    - **Logs**: Se registran errores de conexión con la API en `laravel.log`.
    - **Fallos de Datos**: Si un cliente no tiene ID de Holded, el selector de presupuestos muestra "No hay opciones disponibles" en lugar de fallar, y se debe usar el comando de reparación.
98: 
99: ### 5.4 Gestión de IDs Secundarios (Duplicados)
100: - **Problema**: Holded permite tener múltiples fichas para un mismo cliente (ej: errores tipográficos o duplicados históricos). Esto fragmenta los presupuestos.
101: - **Solución**: Se ha implementado un campo `secondary_contacts` (JSON Array) en la tabla `clients`.
102:     - El sistema fusiona automáticamente los presupuestos del ID principal (`contact`) y los IDs secundarios al listarlos.
103:     - **Autogestión**: Los administradores pueden añadir manualmente estos IDs extra desde el formulario de edición de cliente, garantizando que todos los documentos aparezcan unificados sin intervención técnica.

### 5.5 Gestión de Facturas
- **Sincronización Inteligente**:
    - **Visual** ("Lazy"): Al visitar el listado, se sincronizan los metadatos de Holded.
    - **Documental**: Al solicitar el PDF, el sistema verifica si existe en Drive. Si no, lo descarga de Holded, lo sube a Drive y guarda la referencia.
    - **Masiva**: Comando `holded:drive-sync-facturas {año}` para procesar lotes completos en segundo plano.
- **Almacenamiento y Naming**:
    - Estructura: `{Año}/VENTAS/{Trimestre}tri/`.
    - Ficheros: `{Nº Factura} - {Cliente}.pdf` (sanitizado).
- **Lógica de Estados**:
    - **Pagada**: `paymentsPending` = 0.
    - **Pendiente**: `paymentsTotal` = 0.
    - **Parcial**: Resto de casos.
- **UX**:
    - Filtrado nativo por estado.
    - Botón de sincronización manual en cabecera con feedback de progreso.

---

## 6. Frontend y UX Estándar

### 6.1 Visor de PDF Profesional
- **In-App**: Los documentos no se abren en una pestaña cruda. Se embeben en un componente Vue con cabecera fija y botón de retroceso SPA.
- **Compatibilidad**: Soluciona el problema de "atrapamiento" en móviles y la falta de controles de descarga en Safari.

### 6.2 Componentes Reutilizables
- **Debounced Search**: Búsqueda global con 300ms de espera para optimizar recursos del servidor.
- **DataTable**: Componente agnóstico que gestiona estados vacíos, alineaciones y clics en fila.
- **Accesibilidad**: Todos los inputs están vinculados mediante IDs y Nombres únicos para cumplir con los estándares de lectores de pantalla y autocompletado.
- **Formularios Dinámicos**: Uso de `DialogModal` y `ConfirmModal` para flujos de CRUD sin recarga de página.

### 6.3 Biblioteca de Componentes Base
Ubicados en `resources/js/Components/`:
*   `Badge.vue`: Etiquetas de estado personalizables.
*   `StatCard.vue`: Tarjetas de indicadores con soporte de iconos.
*   `SearchableSelect.vue`: Componente avanzado de selección con búsqueda integrada.
*   `Pagination.vue`: Control de paginación compatible con Eloquent/Inertia.

---

## 7. Rendimiento y Escalabilidad (Senior Optimizations)

### 7.1 Indexación de Base de Datos
- Se han implementado índices en las columnas `estado` de las tablas `proyectos` y `mantenimientos`. Esto asegura que los filtrados y cálculos agregados de los dashboards mantengan un rendimiento constante incluso con grandes volúmenes de datos.

### 7.3 Optimización de Consultas (Eager Loading)
- Se ha implementado una política estricta de carga ansiosa (`with(['precios', 'cliente', etc.])`) en todos los métodos de los modelos que alimentan gráficos y estadísticas. Esto ha reducido el número de consultas a la base de datos de cientos a menos de 10 por carga de página, garantizando una fluidez extrema incluso en dispositivos móviles.

---
*Documentación actualizada el 07/02/2026*
