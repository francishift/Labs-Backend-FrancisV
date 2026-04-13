# Gestión de Presupuestos

La gestión de **Presupuestos** dentro de Labs Backend está diseñada con un enfoque idéntico al de las [Facturas de Ventas](gestion-facturas-ventas.md), uniendo almacenamiento robusto local y un rápido procesamiento.

## 🔄 Flujo de Gestión Natural

Los presupuestos son el eslabón fundamental de conversión en el negocio, pudiendo enlazarse estructuralmente al inicio de un futuro "Proyecto". 

### 1. Modelado Transaccional y Formularios Modernos
La UI se sustenta sobre el ecosistema Vue 3. La creación o edición del listado utiliza un controlador que gestiona líneas asíncronas reactivas (Precio x Cantidad x IVA) interactuando con el usuario antes de invocar la persistencia al Backend. Todo se valida a nivel JSON contra el framework interno, persistiendo luego en su propia tabla relacional estricta MySQL local (`presupuestos`).

### 2. Filtros de Alto Rendimiento
Una vez generados, los presupuestos pueden ser filtrados o listados mediante agregaciones nativas (búsqueda LIKE, indexaciones WHERE por cliente o rango de fecha) impulsado por Query Builder, consiguiendo en milisegundos un pantallazo estadístico anual y general de operaciones PENDIENTES o APROBADAS, ignorando de la sumatoria los importes ANULADOS (los cuales no suponen volumen de negocio real).

## 📁 Descarga de PDFs y Respaldo Inteligente en Google Drive

El sistema de descarga y guardado cuenta con una arquitectura de sincronización segura documental a largo plazo:

1. **Auto-Generación Documental Expresiva:** Cada vez que la información es mutada o el presupuesto creado, DOMPDF entra en acción compilando la plantilla oficial (`presupuesto.blade.php`), insertos (Observaciones con retenciones de salto de página) y marca de agua `(CANCELED/REJECTED)` de ser caso.
2. **Subida Estructurada a Google Drive:** Laravel establece enlace mediante Service Account con Googe Workspace, crea dinámicamente la carpeta jerárquica del año actual (si es que no existía) dentro del repositorio remoto root y ubica formalmente el nuevo PDF.
3. **Persistencia del Hash:** El sistema asocia el `google_drive_file_id` devuelto por la Request al registro interno de Laravel. Resultando que las futuras cargas y emisiones manuales eviten la carga inútil del servidor y entreguen un link de streaming nativo directo desde los servidores de Google.

## 🔗 Relación de Presupuestos y Proyectos

A nivel estructural, un presupuesto está diseñado para ser el origen opcional de un **Proyecto**:
- El modelo `Presupuesto` cuenta con una relación que indica que un presupuesto puede originar múltiples proyectos (`$this->hasMany(Proyecto::class, 'presupuesto_id');`).
- Esto permite mantener una trazabilidad global unificada (Status: Aprobado -> Conversión Proyecto -> Terminado -> Factura) garantizada por las foreign keys MySQL interconectadas.
