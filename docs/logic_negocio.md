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
- **Documentación Completa**: Lee la [Gestión de Extensiones](gestion-extensiones.md) para más detalles técnicos sobre el uso y las analíticas de Dashboard.

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

### 4.4 Visualización Avanzada de Proyectos (Show.vue)
- **Tarjeta de Información General**:
    - Layout de 3 columnas para optimizar el espacio: [Cliente | Presupuesto | Total Facturado].
    - **Cálculo de Cobertura**: Se compara el `totalFacturadoNeto` (Base Imponible) contra el Presupuesto para obtener un porcentaje de cobertura real ("peras con peras").
    - **Total Facturado**: Se muestra el importe bruto (con impuestos) para referencia rápida del usuario, etiquetado claramente.
    - **Enlaces Directos**: Las facturas asociadas y el presupuesto vinculado son accesibles directamente mediante un clic en sus respectivas tarjetas/iconos, abriendo el visor PDF integrado.

---

## 5. Integración con Holded (API Sync)

### 5.1 Sincronización de Presupuestos
- **Estrategia**: Sincronización local proactiva a través del módulo de Presupuestos. El sistema opera prioritariamente sobre la base de datos local para evitar latencias de API en las vistas de clientes.
- **Almacenamiento en Google Drive**:
    - Los PDFs se descargan de Holded y se almacenan automáticamente en una carpeta dedicada de Google Drive.
    - **Estructura Dinámica**: `{Año}/VENTAS/{Trimestre}tri/{docNumber}.pdf`.
    - **Independencia**: Las lecturas subsiguientes se sirven directamente desde Drive usando la API nativa, eliminando la dependencia de Holded.

### 5.2 Sincronización de Clientes
- Los contactos de Holded se vinculan mediante el `CIF/NIF` o email.
- **Integridad de Datos**: Existe un comando de mantenimiento `php artisan holded:fix-contacts` para recuperar IDs de contacto perdidos.

### 5.3 Gestión de IDs Secundarios (Duplicados)
- **Problema**: Holded permite tener múltiples fichas para un mismo cliente.
- **Solución**: Campo `secondary_contacts` (JSON Array) en la tabla `clients`. El sistema fusiona automáticamente los presupuestos del ID principal y los secundarios.

### 5.4 Gestión de Facturas de Venta
- **Sincronización Inteligente**: Visual ("Lazy") en el listado, y Documental al solicitar el PDF.
- **Almacenamiento**: `{Año}/VENTAS/{Trimestre}tri/`.

---

## 6. Gestión de Facturas de Compra (Gtos)

### 6.1 Procesamiento con IA
- El sistema utiliza **Google Document AI** para extraer automáticamente los datos de facturas PDF subidas manualmente.
- **Extracción**: Número, Proveedor, Fecha, Base Imponible, IVA y Total.

### 6.2 Almacenamiento Estructurado
- Las facturas se guardan en Google Drive siguiendo la estructura: `{Año}/COMPRAS/{Trimestre}tri/`.
- El sistema mueve automáticamente el archivo si la fecha detectada por la IA pertenece a un periodo distinto al de subida.

### 6.3 Control de Duplicados
- Detección por número de factura. Permite la sustitución (sobrescritura) completa del registro y el archivo en Drive si el usuario lo confirma.

---

## 7. Frontend y UX Estándar

### 7.1 Visor de PDF Profesional
- **In-App**: Embebido en un componente Vue con cabecera fija y botón de retroceso SPA.
- **Compatibilidad**: Optimizado para móviles y Safari.

### 7.2 Arquitectura DRY y FormRequests
- Las validaciones de datos sensibles de entrada pesada (incluidas comprobaciones de fecha/tiempo complejo) son delegadas íntegramente a `FormRequest` personalizados y a su hook interactivo `after()`, manteniendo a los controladores delgados en el sistema de gestión.

### 7.3 Componentes Reutilizables
- **Buscadores (SearchInput)**: Diseño estandarizado globalmente con icono de lupa insertado a la izquierda.
- **Debounced Search**: Búsqueda global obligatoriamente controlada por `debounce` con 500ms de espera. **Estrictamente prohibido asignar `throttle` a los inputs**, ya que interrumpe e interfiere con el tecleo del usuario al lanzar refrescos antes de terminar de escribir.
- **Selects y Filtros**: Las tarjetas `<Card>` de filtros deben fusionarse con el layout usando el espaciado predeterminado y sin forzar fondos negros duros, heredando el estilo del componente base (`SearchableSelect` y `SelectInput`).
- **DataTable**: Componente agnóstico para listados.
- **Flash Messages**: Notificaciones con soporte para HTML.

---

## 8. Rendimiento y Escalabilidad

### 8.1 Indexación de Base de Datos
- Índices en columnas críticas como `estado`, `number` y `date` para garantizar rapidez en filtros y dashboards.

### 8.2 Optimización de Consultas (Eager Loading)
- Política estricta de carga ansiosa en todos los controladores para eliminar consultas N+1. Uso de `->with([...])` en listados y analíticas.

---
*Documentación actualizada el 05/03/2026*
