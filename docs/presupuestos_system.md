# Análisis del Sistema de Presupuestos (vía Holded)

El sistema de gestión de **Presupuestos** opera en base a una arquitectura de "Espejo Inteligente" (Sincronizador Automático) con la plataforma de software Holded, importando y respaldando la información y los PDFs de manera local y en la nube de Google Drive.

A continuación, se detalla la arquitectura y el comportamiento:

## 1. Arquitectura de Sincronización (HoldedService)

El núcleo recae en el servicio `App\Services\HoldedService.php`. Utiliza el API de Holded (mediante una API Key) para conectarse al módulo de facturación:

- **Sincronización `On-Demand`**: A diferencia de `PurchaseFactura` (donde se suben archivos manualmente), en Presupuestos, cuando un usuario administrativo entra en la pantalla, el sistema dispara `HoldedService->syncDocuments('estimate')` tomando en cuenta las fechas indicadas en el filtro (por defecto, el año actual).
- **Desglose Financiero Propio**: Al recibir los datos de Holded de un "estimate" (propuesta/presupuesto), el sistema recorre cada producto/línea del mismo para calcular de forma matemática localmente:
  - Total Neto (Base imponible).
  - IVA individual por línea.
  - IRPF / Retención. Como Holded incrusta el IRPF dentro de un array de textos (Ej. un tag `s_ret_15`), el servicio incluye una expresión regular (`preg_match('/ret_([0-9.]+)/'`) capaz de parsear esto y calcular el IRPF correspondiente.
- **Modelo Híbrido (`App\Models\Presupuesto`)**: Toda la metadata que viene de Holded se almacena sin procesar en formato JSON en una columna (`raw_data`), y se extrapolan valores esenciales procesados (importes, contacto y fechas) en sus correspondientes columnas locales.

---

## 2. Flujo de Respaldo de PDFs en Google Drive

Una de las premisas del sistema es crear un mecanismo de caché / respaldo recurrente y perpetuo descargando y subiendo documentos PDF desde Holded hacia Google Drive de la organización de forma segura:

1. **Procedimiento de comprobación y guardado del PDF** (`PresupuestoController@downloadPdf`):
   - **Intentar Caché Drive (Paso 1)**: El sistema verifica si la base de datos presenta un registro asociado de Google Drive (`google_drive_file_id`). De coincidir el enlace, sirve el archivo alojado pasivamente abstrayéndose de llamadas a Holded mejorando consistentemente la visualización y estabilidad.
   - **Descarga desde Holded (Paso 2)**: A falta de registro, dispara la API (`getDocumentPdf`) y obtiene el PDF incrustado bajo una codificación Base64.
   - **Salvaguardado en Drive (Paso 3)**: Decodifica de forma binaria el stream y procede al empaquetado secundario subiendo el archivo al directorio maestro `GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS`. El organizador de Google jerarquiza las carpetas automáticamente según el **año** vigente (ej. `2024`, `2025`), almacenando la Id correspondiente.
   
---

## 3. Vinculaciones con la Base de datos (Local)

- **Proyectos**: El modelo `Presupuesto` está estructuralmente asociado con Proyectos (`App\Models\Proyecto`), lo que permite que un proyecto individual dependa en su creación de algún presupuesto comercial previamente expedido de acuerdo a su modelo asociado Eloquent.
- **Contactos**: Identifica y relaciona los `contact_id` nativos de Holded. Se emplea esta filosofía en pro de simplificar componentes y duplicados utilizando los parámetros `ID` y `contact_name` como las llaves que relacionan un expediente propio al cliente raíz de Holded.

## 4. Evolución a Sistema Nativo (Actualización)

A partir de la reciente iteración de desarrollo, el sistema ha evolucionado de ser puramente un "Espejo" de Holded a permitir una **gestión 100% Nativa e Independiente** de presupuestos, desligándose de Holded para futuras facturaciones pre-venta directas. Novedades clave:

- **Migración a Base de Datos Local Completa**: Los presupuestos ya no dependen de un ID de Holded forzoso; ahora generan su propia matriculación (ej. `PR-5893`), mantienen líneas de venta (`PresupuestoLinea`), control de IRPF e IVA por separado, estado independiente (borrador, enviado, firmado, etc.) y fecha nativa local.
- **Relaciones y Clientes Directos**: Se ha vinculado a cada presupuesto su propio `client_id` (migrado previamente desde `contact_id`), permitiendo cargar información demográfica real (CIF, dirección, etc.) almacenada sin requerir consultas cruzadas lentas, con un fallback (sistema de rescate) a la data heredada de Holded para retrocompatibilidad total con facturas antiguas.
- **Descripciones Enriquecidas (WYSIWYG)**: Se ha integrado el componente `@vueup/vue-quill` que permite incluir descripciones detalladas con formato HTML. Presenta capacidades híbridas, tolerando el modo oscuro, personalización de viñetas tipográficas o alternancia en vivo entre Código Puro (HTML) y Visión Gráfica.
- **Generador PDF Híbrido Corporativo**: Descartando la generación rígida externa, la plataforma renderiza sus propios archivos PDF a través de vistas locales (`pdf.presupuesto`), imponiendo forzosamente la tipografía corporativa (`Lexend`) con diferentes pesos, espaciados perfeccionados, directrices en listas/párrafos e imponiendo paletas de alto contraste (#27272a) para repeler imperfecciones en el código HTML inyectado externamente (pegados sucios de otras webs). 

## Resumen

El sistema opera combinando lo mejor del archivado histórico heredado con el control independiente en la sección de finanzas, siendo diseñado a prueba de fallos integrando documentos en caché mientras aporta una herramienta y vista detallada premium para la gestión y seguimiento del cliente.
