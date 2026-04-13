# Gestión de Facturas de Ventas

A diferencia de las **Facturas de Compras** (las cuales se suben en PDF manualmente y son procesadas localmente por Google Gemini AI), en *Labs Backend*, las **Facturas de Ventas** operan de forma nativa e independiente, garantizando la resiliencia de los datos del negocio sin depender de plataformas de terceros.

## 🔄 Flujo de Gestión y Renderizado Local

El ciclo de vida de la facturación de ingresos inicia de manera natural o asociada a los **Proyectos** y **Mantenimientos**, procesándose y persistiendo directamente en la base de datos local (MySQL).

### 1. Arquitectura de Base de Datos Estructurada
La aplicación cuenta con una tabla robusta y local de `facturas` (`Factura.php`). Toda la información de facturación se genera, valida y archiva en columnas estables:
- `subtotal` (Base Imponible)
- `tax_amount` (Cuota de IVA desglosada)
- `irpf_amount` (Retención desglosada)
- `total` (Total a pagar numérico)
- `status` (Enum estricto de Enum/FacturaStatus: PENDIENTE, PAGADA, PARCIAL, ANULADA)

El cálculo del _Dashboard_ o los Resúmenes Anuales se ejecutan localmente mediante agregaciones rápidas (`SUM()`) en la base de datos, en una fracción de milisegundo.

### 2. Filtros y Estados de Interfaz
El listado de facturas incluye **filtros rápidos** reactivos integrados con **Inertia.js** para garantizar que los estados elegidos (selectores de Cliente, Fechas de Emisión, Estado de Pago) permanezcan visualmente exactos tras la recarga y paginación bidireccional, evitando borrados inesperados gracias a la retención de los props del enrutador. 

### 3. Respaldo Inmutable en Google Drive
Uno de los componentes principales es la función de almacenamiento inmutable en Background a Google Drive a través del `FacturaController`:
- **Renderizado Dinámico:** Cuando se crea, actualiza o anula una factura, el sistema utiliza `DOMPDF` para generar la factura digital incrustándole diseño dinámico. En el caso de estar anulada, le inyecta una marca de agua roja transparente y cambia asíncronamente su sufijo nominal en Drive.
- **Upload Estructurado:** El sistema sube de manera automatizada este documento a la estructura de carpetas de Google Drive de la empresa (**.../{Año}/VENTAS/{Trimestre}tri/{NºFactura} - {ClientName}.pdf**), y guarda internamente el identificador atómico `google_drive_file_id`.
- **Acceso Directo Streamer:** Cada vez que el administrador accede a verla y descargarla en el equipo de escritorio/móvil local, se le facilita el PDF original traído sin esfuerzo directamente bajo stream usando el driver de Google asociado a Laravel.

### Ventajas de esta Arquitectura

1. **Software Resiliente "Zero-Dependency":** En lugar de vincular la estabilidad de la empresa a un carísimo sistema transaccional de facturas (ERP de terceros), el sistema es enteramente propietario. No importan los vencimientos de APIs externas, Labs Backend posee todo el histórico documental.
2. **Alta Velocidad Perimetral:** Los "dashboards" financieros cargan al instante los datos agregados y no requieren costosas operaciones O(n) sobre peticiones remotas JSON.
