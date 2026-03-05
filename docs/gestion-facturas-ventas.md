# Gestión de Facturas de Ventas

A diferencia de las **Facturas de Compras** (las cuales se suben en PDF manualmente y son procesadas localmente por Google Gemini AI), en *Labs Backend*, las **Facturas de Ventas** operan mediante un sistema híbrido que prioriza la **independencia y resiliencia de los datos del negocio**.

## 🔄 Flujo de Integración y Sincronización Local

El ciclo de vida de la facturación de ingresos inicia de manera asociada a los **Proyectos** y **Mantenimientos**, integrándose inicialmente con **Holded**, pero persistiendo localmente para evitar la dependencia a largo plazo.

### 1. Sincronización a Base de Datos
La aplicación cuenta con una tabla local de `facturas` (representada por el modelo `Factura.php`). A través de comandos y sincronizaciones mediante `HoldedApiService.php`, la información de las facturas emitidas (proveedor, fechas, importes, estado de pago) se guarda de manera persistente en la **base de datos de Labs Backend**.
- **Independencia Analítica:** Los datos de ingresos de Proyectos y rentabilidad de Mantenimientos *recurrentes* pueden calcularse consultando las tablas locales, incluso si la conexión a Holded falla.

### 2. Respaldo Inmutable en Google Drive
Uno de los componentes principales del área de facturación es la función `ensureInDrive` en el `FacturaController`.
- **Descarga Mágica:** Cuando se visualiza o se sincroniza una factura, el backend descarga el PDF original provisto por Holded en formato base64.
- **Upload Estructurado:** El sistema sube de manera automatizada este documento a la estructura de carpetas de Google Drive de la empresa (**.../{Año}/VENTAS/{Trimestre}tri/...**).
- **Fallback Directo (El verdadero origen):** Cada vez que un usuario intenta leer o descargar el documento asociado a una factura en Labs Backend, **el sistema busca PRIMERO el archivo en Google Drive**. Si el enlace está vinculado, se sirve el archivo persistente desde Drive y no desde Holded.

### Ventajas de esta Arquitectura

1. **Supervivencia "Day-After":** Si el día de mañana la empresa decide migrar de Holded o el servicio de Holded deja de estar activo, **Labs Backend seguirá funcionando sin interrupciones**. Todas las facturas de ventas estarán a salvo, estructuradas trimestre a trimestre en Drive y listadas operativamente en la base de datos MySQL local.
2. **Eficiencia en Búsquedas:** No es necesario solicitar documentos por API cada vez que un usuario pide ver el PDF, agilizando los tiempos de carga en las pantallas de proyectos al proveer el Stream directamente desde Google Drive local.

Para más detalles técnicos, véase la implementación en `app/Http/Controllers/Admin/Holded/FacturaController.php`.
