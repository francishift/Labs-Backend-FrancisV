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
- **Precio Prorrateado Dinámico**: El coste repercutido real de una extensión no es su coste base. El sistema divide el coste de la extensión entre la suma de Proyectos ("En Proceso") y Mantenimientos ("En Curso") que la están utilizando.
- **Retroactividad**: Al calcularse un nuevo precio (por ejemplo, porque un proyecto finaliza o un cliente se da de baja), se actualiza automáticamente el `precio_aplicado` en las tablas pivote de **todos** los proyectos y mantenimientos históricos que tengan esa extensión, manteniendo el balance siempre real y actualizado. Existen comandos (`extensions:recalculate`) para forzar este update masivo.
- **Documentación Completa**: Lee la [Gestión de Extensiones](gestion-extensiones.md) para más detalles técnicos sobre el uso y las analíticas de Dashboard.

---

## 3. Mantenimientos (SaaS & Recurrencia)

### 3.1 Ingresos y Prorrateo
Los ingresos de mantenimiento se calculan según el `tipo_pago`:
- **Mensual**: El `importe` se registra íntegro cada mes.
- **Trimestral**: Se divide el `importe` entre 3 para obtener la repercusión mensual estadística.
- **Anual**: Se divide el `importe` entre 12 para los cálculos de balance mensual.
- **Persistencia Histórica**: El sistema utiliza la tabla `mantenimiento_precios` para registrar cada cambio de tarifa. Esto garantiza que el balance de meses pasados use el precio que el cliente pagaba en ese momento, no el actual.

### 3.2 Tasa Horaria y Coste de Servicios
- **Cálculo Inmutable:** Al registrar un servicio, el sistema captura automáticamente y de forma inmutable el **Precio Hora Global** vigente en ese mismo instante (incluyendo el `descuento_mantenimiento`).
- **Independencia Histórica:** Este snapshot del precio garantiza que cambios futuros en la configuración global no alteren retroactivamente el balance de rentabilidad de los meses pasados.
- **Rentabilidad de Precisión:** El modelo financiero calcula la rentabilidad sumando el coste real e individual de cada intervención (`(minutos / 60) * precio_hora_capturado_en_el_servicio`), en lugar de aplicar promedios globales al total de horas mensuales.

### 3.3 Exportación e Informes de Periodo
- **Filtro Temporal:** Los PDFs de mantenimiento reflejan estrictamente el periodo visualizado (mes y año). Al exportarse, para evitar consultas N+1 en las tablas, los filtros se aplican directamente en la carga ansiosa `load(['servicios' => function(...) ])`.
- **Precios de Respaldo:** El informe calcula los importes utilizando una tasa de respaldo global (`$precioHoraFallback`) si algún servicio heredado no posee un snapshot directo de su tarifa.

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

### 4.5 Exportación a PDF y Envío Integrado
- **Generación en Memoria**: En lugar de obligar al usuario a descargar el informe para adjuntarlo manualmente, la app permite enviarlo en un clic. El PDF se construye en crudo mediante `DomPDF->output()` inyectándose al instante en la tubería de correo como `application/pdf`, evitando escrituras residuales en disco.

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

### 7.1 Visor de PDF Profesional y Envío In-App
- **Visor In-App**: Embebido en un componente Vue con cabecera fija y botón de retroceso SPA.
- **Acciones Rápidas (Anti-Spaghetti)**: Las vistas centralizan las descargas en el visor global suprimiendo botones redundantes. Se integran modales limpios para envío In-App (`DialogModal` reactivo) autocompletando la pre-selección de destinatarios con los datos asíncronos del modelo actual (`$page.props.proyecto.client.email`).

### 7.2 Arquitectura DRY y FormRequests
- Las validaciones de datos sensibles de entrada pesada (incluidas comprobaciones de fecha/tiempo complejo) son delegadas íntegramente a `FormRequest` personalizados y a su hook interactivo `after()`, manteniendo a los controladores delgados en el sistema de gestión.
- **Validación de Archivos (Bug Laravel 12 / brick/math):** Está estrictamente prohibido usar la regla nativa `max:tamanio` (ej. `max:10240`) para validar el peso de archivos subidos (como PDFs o Excels). Esto se debe a que Laravel convierte el peso a kilobytes generando un *float*, lo cual dispara una advertencia de depreciación (`Passing floats to BigNumber::of()`) en los logs del servidor por parte de la librería matemática interna. En su lugar, se debe utilizar siempre un *Closure* personalizado (ej. `if ($value->getSize() > 10485760) { $fail(...); }`).

### 7.3 Componentes Reutilizables
- **Buscadores (SearchInput)**: Diseño estandarizado globalmente con icono de lupa insertado a la izquierda.
- **Debounced Search**: Búsqueda global obligatoriamente controlada por `debounce` con 500ms de espera. **Estrictamente prohibido asignar `throttle` a los inputs**, ya que interrumpe e interfiere con el tecleo del usuario al lanzar refrescos antes de terminar de escribir.
- **Selects y Filtros**: Las tarjetas `<Card>` de filtros deben fusionarse con el layout usando el espaciado predeterminado y sin forzar fondos negros duros, heredando el estilo del componente base (`SearchableSelect` y `SelectInput`).
- **DataTable**: Componente agnóstico para listados.
- **Flash Messages**: Notificaciones con soporte para HTML.

### 7.4 Correos Electrónicos Estándar (Markdown)
- **Consistencia Visual:** Para garantizar que todos los correos automáticos (desde los informes de Proyectos hasta las Notificaciones de Copias de Seguridad de Spatie) compartan siempre el mismo estilo, logotipo y variables globales, **siempre** deben utilizarse las mallas nativas `<x-mail::message>`. 
- Se prohíbe terminantemente crear diseños de correos HTML en crudo o tablas independientes que rompan la cohesión de la plantilla unificada de Laravel.

---

## 8. Rendimiento y Escalabilidad

### 8.1 Indexación de Base de Datos
- Índices en columnas críticas como `estado`, `number` y `date` para garantizar rapidez en filtros y dashboards.

### 8.2 Optimización de Consultas (Eager Loading)
- Política estricta de carga ansiosa en todos los controladores para eliminar consultas N+1. Uso de `->with([...])` en listados y analíticas.

### 8.3 Agregaciones Dinámicas de Totales (Sumatorias)
- Para mostrar totales reactivos a filtros (como la suma de bases imponibles, IVAs y totales en los listados de facturas), el sistema clona la consulta principal de base de datos (`$totalsQuery = clone $query;`) inmediatamente después de aplicar los filtros y antes de la paginación.
- Luego ejecuta funciones de agregación nativas (`sum()`) de SQL sobre esta copia. Esto garantiza el cálculo ultra-rápido y exacto sobre el total de los registros filtrados sin cargar modelos individuales a la memoria, previniendo cuellos de botella y evitando completamente operaciones N+1.

---

## 9. Sincronización con Google Calendar (Notas)

### 9.1 Automatización
- El módulo de `Notas` cuenta con sincronización bidireccional en segundo plano usando el SDK oficial de `google/apiclient`.
- **Eventos Eloquent**: Un `NotaObserver` escucha las operaciones CRUD sobre el modelo `Nota` y delega en el `GoogleCalendarService` si el campo `sync_calendar` es verdadero.

### 9.2 Privacidad e Interrupciones
- **Notificaciones Override**: Los eventos se suben con `UseDefault: false` en los *reminders* de la API para asegurar que no se solapen con las alertas nativas PWA, garantizando una única notificación silenciosa del PWA al usuario.
- **Identificación**: A los eventos sincronizados se les adjunta el sufijo o emoji (📌) para distinguirlos visualmente dentro del calendario de los eventos creados manualmente.

Para más detalles léase la [Documentación de Integración de Google Calendar](integracion-google-calendar.md).

---
*Documentación actualizada el 26/03/2026*
