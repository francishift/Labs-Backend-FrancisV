# Sistema de Gestión de Facturas de Compra

Este sistema automatiza la recepción, procesamiento y almacenamiento de facturas de compra (gastos) utilizando Inteligencia Artificial y almacenamiento en la nube.

## Flujo de Trabajo

1.  **Subida de Archivos**:
    *   Soporta múltiples archivos PDF simultáneamente.
    *   Interfaz de arrastrar y soltar (Drag & Drop).
    *   Cola de subida uno a uno para evitar timeouts del servidor y monitorizar el progreso individual.

2.  **Procesamiento (Google Gemini 1.5 Flash)**:
    *   El sistema utiliza la inteligencia artificial de **Gemini** mediante el SDK oficial para extraer automáticamente:
        *   Número de factura, Proveedor y Fecha.
        *   Base imponible (`net_amount`), Cuota de IVA (`tax_amount`) y Total.
    *   **Prompting Estricto**: Se han configurado instrucciones precisas para que prefiera extraer el nombre legal/societario (ej. S.A.U., S.L.) sobre el nombre comercial y distinga el emisor del receptor.
    *   **Heurística de Validación**: El sistema cuenta con lógica post-procesado para evitar confundir a "Labs Francis" u otros nombres del cliente como el proveedor de la factura.

3.  **Gestión de Duplicados**:
    *   Detección inteligente por número de factura.
    *   **Sustitución**: Permite sobrescribir una factura existente, actualizando tanto la base de datos como el archivo en Google Drive (para auditorías).

4.  **Revisión Manual**:
    *   Cualquier factura puede ser editada manualmente mediante el icono de **lápiz**.
    *   Permite corregir errores de la IA en cualquier campo (número, proveedor, fecha, importes).
    *   **Sugerencias de Proveedor**: El campo proveedor ofrece sugerencias automáticas de proveedores existentes para mantener la integridad de los datos, pero permite crear nuevos escribiendo el nombre.

5.  **Almacenamiento (Google Drive)**:
    *   Organización automática: `Facturas / {AÑO} / COMPRAS / {TRIMESTRE}tri / {NOMBRE_ARCHIVO}.pdf`
    *   Si se cambia la fecha de una factura manualmente, el sistema **mueve** el archivo a su carpeta correspondiente automáticamente.

## Componentes Técnicos

### Backend
*   **Controlador**: `App\Http\Controllers\Admin\PurchaseFacturaController`
*   **Modelo**: `App\Models\PurchaseFactura`
*   **Servicio de IA**: `App\Services\GeminiInvoiceService`

### Frontend
*   **Página Principal**: `resources/js/Pages/Admin/PurchaseFacturas/Index.vue`
*   **Filtros**: Búsqueda por texto, filtro de proveedor (con sugerencias) y rango de fechas.
*   **Ordenación**: Todas las columnas son ordenables dinámicamente.
*   **Tematización**: Soporte completo para modo claro y oscuro.

## Mantenimiento y Auditoría

La estructura de carpetas en Google Drive y la trazabilidad de duplicados aseguran que el sistema esté siempre listo para una auditoría contable.
