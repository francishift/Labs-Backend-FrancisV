# Gestión de Facturas de Ventas

A diferencia de las **Facturas de Compras** (las cuales se suben en PDF manualmente y son procesadas localmente por Google Gemini AI), en *Labs Backend*, las **Facturas de Ventas** operan mediante un sistema híbrido que prioriza la **independencia y resiliencia de los datos del negocio**.

## 🔄 Flujo de Integración y Sincronización Local

El ciclo de vida de la facturación de ingresos inicia de manera asociada a los **Proyectos** y **Mantenimientos**, integrándose inicialmente con **Holded**, pero persistiendo localmente para evitar la dependencia a largo plazo.

### 1. Sincronización Estructural de Base de Datos
La aplicación cuenta con una tabla local de `facturas` (`Factura.php`). A través de `HoldedService.php`, la información de las facturas no solo se guarda tal cual llega ("Raw JSON"), sino que **el sistema desglosa algorítmicamente y guarda en columnas nativas de MySQL** los datos contables vitales:
- `subtotal` (Base Imponible)
- `tax_amount` (Cuota de IVA desglosada)
- `irpf_amount` (Retención desglosada)

Esto permite una independencia plena. El cálculo del _Dashboard_ o los Resúmenes Anuales se ejecutan localmente mediante agregaciones rápidas (`SUM()`) en la base de datos sin necesidad de procesar en memoria o consultar en vivo a Holded.

### 2. Filtros y Estados de Interfaz
El listado de facturas incluye **filtros rápidos** reactivos integrados con **Inertia.js** para garantizar que los estados elegidos (selectores de Cliente, Fechas de Emisión, Estado de Pago) permanezcan visualmente exactos tras la recarga y paginación bidireccional, evitando borrados inesperados gracias a la retención de los props del enrutador. Todos los paneles de filtros unifican además un botón global de **"Limpiar"**, representado mediante un icono circular que devuelve el listado al estado por defecto.

### 2. Respaldo Inmutable en Google Drive
Uno de los componentes principales del área de facturación técnica es la función `ensureInDrive` en el `FacturaController`.
- **Descarga Mágica:** Cuando se visualiza o se sincroniza una factura, el backend descarga el PDF original provisto por Holded en formato base64.
- **Upload Estructurado:** El sistema sube de manera automatizada este documento a la estructura de carpetas de Google Drive de la empresa (**.../{Año}/VENTAS/{Trimestre}tri/...**).
- **Fallback Directo (El verdadero origen):** Cada vez que un usuario intenta leer o descargar el documento asociado a una factura en Labs Backend, **el sistema busca PRIMERO el archivo en Google Drive**. Si el enlace está vinculado, se sirve el archivo persistente desde Drive y no desde Holded.

### Ventajas Históricas de esta Arquitectura

1. **Supervivencia "Day-After":** Si el día de mañana la empresa decide migrar de Holded o el servicio de API expira, **Labs Backend seguirá funcionando sin interrupciones**. Todas las facturas previas estarán a salvo estructuralmente en MySQL (con base imponible e impuestos reales calculados), y sus archivos físicos listos trimestre a trimestre en Drive.
2. **Alta Velocidad Perimetral:** Al no depender de decodificar masivamente JSON, los "dashboards" financieros cargan al instante los datos agregados. Y al ver una factura individual, no es necesario solicitar documentos por lenta vía API cada vez, agilizando los tiempos de carga al proveer el Stream directamente desde Google Drive local.
