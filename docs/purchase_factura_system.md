# Especificación Técnica: PurchaseFactura (Compras)

## 1. Arquitectura Base

- **Modelo (`App\Models\PurchaseFactura`)**:
  - Campos de cálculo: `net_amount` (Base), `tax_amount` (IVA), `irpf_amount` (IRPF) y `total`.
  - Seguridad: Soporta `SoftDeletes`.
  - Persistencia documental: Refleja enlace remoto en `google_drive_file_id`.
  - Persistencia de metadatos: Almacena el esquema JSON retornado por extracción en `raw_data`.

- **Controlador (`App\Http\Controllers\Admin\PurchaseFacturaController`)**:
  - Encargado del ciclo CRUD, peticiones de filtrado reactivo asíncrono y enrutamiento con los servicios de IA.

- **Servicios Integrados (`App\Services`)**:
  - `GeminiInvoiceService`: Implementa llamadas directas a la API de **Google Gemini 2.5 Flash** para NLP de facturas complejas.
  - `GoogleDriveService`: Actúa a través del Facade de *Storage* para el enrutamiento remoto de binarios en Flysystem.

## 2. Flujo de Subida y Procesamiento IA

1. **Placeholder (Init)**: Crea un registro modelo inicial en la DB iterado como `procesando` con máscara de identificador único.
2. **Transferencia Remota**: Despliega el PDF a Google Drive (`Storage::disk('google_facturas')`) usando el mapeo temporal año/semestre.
3. **Parseo Gemini**: Codifica el documento en `Base64` y despacha el prompt a Gemini API. El servicio restringe la validación del JSON pre-formateando divisas y discriminando al perfil del titular respecto al emisor de la factura.
4. **Guardado Condicional (`handleExtractedData`)**:
   - Revisa colisión por número de factura.
   - En caso de conflicto: Inyecta el flag `DUP-[timestamp]`, retiene estado a `duplicada` y aguarda la resolución en Front.
   - En ejecución exitosa: Renombra, actualiza sumatorios matemáticos detectados, mapea fechas oficiales a la carpeta definitiva de Google Drive moviendo el archivo y fija status a `recibida`.

## 3. Características Avanzadas (Backend/Frontend)

- **Resolver Colisiones**: La función `confirmOverwrite()` remueve iteraciones obsoletas (`forceDelete`) en DB y Driver.
- **Totales Agregados Dinámicos**: En el listado `Index` el controlador clona una petición SQL base y le sobrepone las interfaces `sum()`, logrando entregar pre-cálculos unificados de la base de datos sin incurrir en cuellos de botella mediante queries aisladas N+1.
- **Visualizador Stream**: El endpoint `showPdf` emite `Content-Disposition: inline` alimentando los Modals interactivos de VUE de forma directa.

## 4. Diagrama de Estados (`status`)

- `procesando`: Petición iniciada, API Gemini en curso.
- `recibida`: Transacción SQL y almacenamiento superado.
- `duplicada`: Conflicto por número ID y/o cif idéntico. Requiere Merge o Descarte manual.
- `error_ia`: Input incomprensible o denegado, timeout en request o estructura JSON corrupta.
- `pagado`: Meta-estado flag de uso contable.
