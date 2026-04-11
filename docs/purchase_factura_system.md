# Análisis del Sistema `PurchaseFactura` (Facturas de Compra)

He realizado un estudio exhaustivo del sistema de gestión comercial de facturas de compra (`PurchaseFactura`), analizando tanto el backend (Laravel) como el frontend (Vue/Inertia), así como los servicios externos integrados.

A continuación, detallo la arquitectura, el flujo de procesamiento y las características principales del sistema.

## 1. Arquitectura y Componentes Principales

El sistema sigue el patrón MVC utilizando Laravel en el backend, e Inertia.js con Vue 3 en el frontend. Está diseñado como un sistema automatizado inteligente para el procesamiento, lectura mediante IA y archivo automático de facturas en la nube.

- **Modelo (`App\Models\PurchaseFactura`)**:
  - Almacena campos financieros clave: `net_amount` (Base), `tax_amount` (IVA), `irpf_amount` (IRPF) y `total`.
  - Incorpora `SoftDeletes` para recuperación y seguridad.
  - El campo `raw_data` almacena en formato JSON toda la información bruta extraída para futura referencia o corrección manual.
  - Guarda la referencial al archivo almacenado en Google Drive mediante `google_drive_file_id`.

- **Controlador (`App\Http\Controllers\Admin\PurchaseFacturaController`)**:
  - Centraliza el CRUD, el filtrado avanzado y actúa como orquestador entre la base de datos, el proveedor de IA y el API de Google Drive.

- **Frontend (`resources/js/Pages/Admin/PurchaseFacturas/Index.vue`)**:
  - Interfaz reactiva donde se presentan filtros avanzados y un cálculo asíncrono de sumatorios dinámicos ("Tarjetas de Totales").

- **Servicios Externos (`App\Services`)**:
  - `GeminiInvoiceService`: Encargado de la extracción de datos de Pdfs utilizando Google Gemini 2.5 Flash.
  - `GoogleDriveService`: (A través del Storage y un proveedor de Drive) Organiza el almacenamiento remoto.

---

## 2. Flujo de Subida y Procesamiento (Pipeline)

El método `store` del controlador define el ciclo de vida principal al subir un PDF. Es un proceso de 4 pasos fuertemente cohesionados:

### Paso 1: Registro Inicial o "Placeholder"
Al recibir el archivo, se crea inmediatamente un registro temporal en la base de datos con el estado `procesando` y un número autogenerado (ej. `PENDING-123456...`).

### Paso 2: Subida e Indexación en Google Drive
1. Se sube el archivo a Google Drive integrando un adaptador de Flysystem (`Storage::disk('google_facturas')`).
2. Se implementa un **sistema de archivero inteligente**: estructura las carpetas jerárquicamente por Año > COMPRAS > Trimestre (ej. `2024 > COMPRAS > 1tri`). Inicialmente, usa la fecha actual (`now()`) si no se dispone de la fecha real.

### Paso 3: Extracción de Datos por IA
El PDF (codificado en Base64) es enviado a `GeminiInvoiceService`, donde interactúa con Gemini 2.5 Flash. 
- **Prompting Restrictivo**: Obliga a la IA a devolver exclusivamente un JSON estructurado con reglas estrictas (no alucinar, preformatear a floats).
- **Heurística de corrección de proveedor/cliente**: Si la IA confunde y marca al titular ("LABS FRANCIS", "FRANCISCO VALENZUELA") como proveedor (emisor de factura), el servicio lo detecta vía hardcode de nombres permitidos, revierte los roles si es posible, o marca "Revisión manual necesaria".

### Paso 4: Resolución y Guardado Final (`handleExtractedData`)
1. Comprueba si el número de factura ya existe (Gestión de Duplicados).
2. Si **ya existe:** En lugar de fallar, renombra la factura como `DUP-[timestamp]-[numero]`, guarda el ID original al que duplica, y la marca en estado `duplicada` para su revisión en la interfaz.
3. Si **es nueva:** Actualiza los importes, fecha y nombre de proveedor real. 
4. Por último, actualiza la ubicación en Drive: si la "Fecha de Factura" extraída pertenece a un trimestre o año distinto al actual, mueve el archivo del Drive a su carpeta cronológica correcta.

---

## 3. Comportamientos y Lógica Interesante

### Manejo Inteligente de Duplicadas
Cuando el sistema detecta que se intenta subir una variante de una factura existente, bloquea la sobreescritura automática. Desde el Frontend (en `Index.vue`), el botón `Sustituir #[num_factura]` llama a la ruta y método `confirmOverwrite()`. Este método elimina el archivo en Drive de la versión antigua, realiza un `forceDelete()` sobre el registro antiguo de DB, y adopta definitivamente el estado y valores de la "nueva" subida, moviendo el archivo en Drive automáticamente.

### Cálculo de Totales Ágil
En `Index.php`, el backend pre-calcula los totales (Base, IVA, Total neto) de todas las facturas en vista (incluso si la lista está paginada) *clonando la query actual*. Esto evita generar problemas de desempeño `N+1` mientras mantiene sincronizados los visores del total arriba de la tabla con los filtros de proveedor y fecha estipulados.

### Visualización y Descarga "In-line"
El `PurchaseFacturaController@showPdf` descarga el flujo de datos del archivo en Drive y retorna un PDF directamente renderizado en el navegador con un `Content-Disposition: inline` que permite el pre-visualizador PDF en la misma pestaña y modal.

---

## 4. Áreas Claves del Estado de Facturas (`status`)

El modelo transita entre los siguientes estados que se renderizan mediante badgets de colores en Vue:
- `procesando` (Naranja): Recién subida, pendiente del hook de I.A.
- `recibida` (Verde): I.A completó la extracción con éxito y se archivó.
- `duplicada` (Rojo): Retenida manual, existe un conflicto de n° de factura.
- `error_ia` (Morado): Formato PDF ilegible, encriptado o la IA falló por Timeout/Parse JSON.
- `pagado` (Verde): Marcador administrativo (usualmente gestionado desde el crud de edición).

---

## Resumen

El sistema es robusto, automatiza completamente el ciclo de ordenamiento documental en "cloud" (Google Drive) y digitaliza la introducción de asientos de gastos (data entry) gestionando colisiones y extracciones fallidas de Gemini AI.
