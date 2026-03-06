# Gestión de Presupuestos

La gestión de **Presupuestos** ("estimates" en Holded) dentro de Labs Backend está diseñada con un enfoque paralelo al de las [Facturas de Ventas](gestion-facturas-ventas.md), buscando minimizar la dependencia continua de la API externa y garantizando un rápido acceso a la información mediante almacenamiento y procesamiento local.

## 🔄 Sincronización Automática con Holded

Cuando se accede a la sección de presupuestos, la aplicación no consulta todos los recursos a Holded en tiempo real para mostrarlos. El flujo es el siguiente:

### 1. Sincronización a Base de Datos Local
Cada vez que se carga el listado (generalmente acotado por un rango de fechas), el sistema a través del `HoldedService` realiza una petición a Holded solicitando sincronizar los documentos de tipo `estimate`.
Esta información se almacena y actualiza en la tabla local `presupuestos` (modelo `Presupuesto.php`). 

2. **Lectura y Búsqueda de Alto Rendimiento:**
   Una vez sincronizados, los datos se sirven a la interfaz directamente desde la base de datos local de MySQL:
   - El buscador opera sobre la base local buscando por el nombre del contacto, el ID de Holded o el número de serie del documento (que se extrae del campo JSON `raw_data`).
   - Esto reduce drásticamente los tiempos de carga de la tabla, paginando los resultados velozmente.

### 3. Filtros y Estados de Interfaz
El listado de presupuestos incluye **filtros rápidos** reactivos integrados con **Inertia.js** para garantizar que los estados elegidos (ej. "Este trimestre", "Últimos 12 meses", selectores de Cliente) permanezcan visualmente exactos tras la recarga y paginación bidireccional, evitando borrados inesperados gracias a la fusión asimétrica del `quickFilter`. Todos los paneles de filtros unifican además un botón global de **"Limpiar"**, representado mediante un icono circular que devuelve el listado al estado por defecto.

## 📁 Descarga de PDFs y Respaldo Inteligente en Google Drive

El sistema de descarga de presupuestos cuenta con una estrategia de "fallback" (respaldo) continuo para proteger los documentos a largo plazo:

1. **Intento desde Google Drive (Caché):** Cuando un usuario solicita descargar el PDF de un presupuesto, el sistema verifica primero si existe el ID del archivo de Google Drive (`google_drive_file_id`) guardado localmente. Si es así, se descarga el archivo PDF directamente de Google Drive, ahorrando llamadas a Holded.
2. **Petición a Holded:** Si el ID no existe en la base de datos (por ser la primera vez que se descarga o aún no está respaldado), la plataforma solicita el documento en base64 a la API de Holded.
3. **Respaldo Automático:** Una vez obtenido de Holded, el backend **crea automáticamente la carpeta del año correspondiente** en el Google Drive de Presupuestos (vía variable de entorno `GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS`), sube el PDF y **guarda el nuevo ID** en la base de datos local. Las futuras consultas de este archivo saltarán directamente al paso 1.

## 🔗 Relación de Presupuestos y Proyectos

A nivel estructural, un presupuesto está diseñado para ser el origen de un **Proyecto**:
- El modelo `Presupuesto` cuenta con una relación que indica que un presupuesto puede originar múltiples proyectos (`$this->hasMany(Proyecto::class, 'presupuesto_id');`).
- Esto permite mantener una trazabilidad desde que se emite la propuesta comercial, hasta que el proyecto se entrega y posteriormente se factura.

Para más detalles técnicos, revisar la implementación del controlador principal en `app/Http/Controllers/Admin/Holded/PresupuestoController.php`.
