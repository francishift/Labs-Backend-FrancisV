# Análisis del Sistema de Presupuestos (vía Holded)

He estudiado cómo funciona la gestión de **Presupuestos** en la aplicación. 
El sistema actúa como un "Espejo Inteligente" (Sincronizador Automático) con la plataforma de software Holded, importando y respaldando la información y los PDFs de manera local y en la nube de Google Drive.

A continuación te detallo la arquitectura y el comportamiento:

## 1. Arquitectura de Sincronización (HoldedService)

El núcleo recae en el servicio `App\Services\HoldedService.php`. Utiliza el API de Holded (mediante una API Key) para conectarse al módulo de facturación:

- **Sincronización `On-Demand`**: A diferencia de `PurchaseFactura` (donde subes archivos manualmente), en Presupuestos, cuando un administrador entra en la pantalla, el sistema dispara  `HoldedService->syncDocuments('estimate')` tomando en cuenta las fechas indicadas en el filtro (por default, el año actual).
- **Desglose Financiero Propio**: Al recibir los datos de Holded de un "estimate" (propuesta/presupuesto), el sistema recorre cada producto/línea del mismo para calcular de forma matemática localmente:
  - Total Neto (Base imponible).
  - IVA individual por línea.
  - IRPF / Retención. Como Holded incrusta el IRPF dentro de un array de textos (Ej. un tag `s_ret_15`), el servicio incluye una expresión regular (`preg_match('/ret_([0-9.]+)/'`) capaz de parsear esto y calcular el IRPF correspondiente.
- **Modelo Híbrido (`App\Models\Presupuesto`)**: Toda la metadata que viene de Holded se guarda sucia en una columna JSON (`raw_data`), pero se guardan valores curados (importes, contacto y fechas) en sus propias columnas.

---

## 2. Flujo de Respaldo de PDFs en Google Drive

Una de las responsabilidades principales de la aplicación con los presupuestos es crear un sistema de respaldo o caché de los documentos PDF de esos presupuestos, descargándolos de Holded y subiéndolos al entorno de Google Workspace de la empresa para tenerlos asegurados:

1. **Cuando el administrador solicita ver o descargar el PDF** (`PresupuestoController@downloadPdf`):
   - **Intentar Caché Drive (Paso 1)**: El sistema verifica si en la base de datos se guardó previamente un `google_drive_file_id`. De ser así, se conecta y trae el archivo directo de Drive, ahorrando llamadas a Holded y acelerando la visualización.
   - **Descarga desde Holded (Paso 2)**: Si el archivo no existe en el registro, hace una petición mediante la API (`getDocumentPdf`) que devuelve el PDF en codificación Base64.
   - **Salvaguardado en Drive (Paso 3)**: El controlador decodifica este string a binario y lo sube de fondo a tu carpeta designada `GOOGLE_DRIVE_FOLDER_ID_PRESUPUESTOS`. El archivador de Google Drive es más simplificado en este apartado: Únicamente los organiza en carpetas correspondientes al **año** del presupuesto (ej. carpeta `2024`, `2025`). Y guarda el ID vinculado.
   
---

## 3. Vinculaciones con la Base de datos (Local)

- **Proyectos**: El modelo `Presupuesto` es inyectado y vinculado con los Proyectos (`App\Models\Proyecto`), ya que un proyecto puede crearse a partir de un presupuesto (Relación uno-a-muchos).
- **Contactos**: Identifica y relaciona los `contact_id` nativos de Holded. Con esta filosofía, en lugar de manejar entidades o cuentas de cliente duplicadas, la app utiliza el ID y `contact_name` como llaves maestras para encontrar la entidad original en la plataforma de Holded.

## 4. Evolución a Sistema Nativo (Actualización)

A partir de la reciente iteración de desarrollo, el sistema ha evolucionado de ser puramente un "Espejo" de Holded a permitir una **gestión 100% Nativa e Independiente** de presupuestos, desligándose de Holded para futuras facturaciones pre-venta directas. Novedades clave:

- **Migración a Base de Datos Local Completa**: Los presupuestos ya no dependen de un ID de Holded forzoso; ahora generan su propia matriculación (ej. `PR-5893`), mantienen líneas de venta (`PresupuestoLinea`), control de IRPF e IVA por separado, estado independiente (borrador, enviado, firmado, etc.) y fecha nativa local.
- **Relaciones y Clientes Directos**: Se ha vinculado a cada presupuesto su propio `client_id` (migrado previamente desde `contact_id`), permitiendo cargar información demográfica real (CIF, dirección, etc.) almacenada sin requerir consultas cruzadas lentas, con un fallback (sistema de rescate) a la data heredada de Holded para retrocompatibilidad total con facturas antiguas.
- **Descripciones Enriquecidas (WYSIWYG)**: Se ha integrado el componente `@vueup/vue-quill` que permite incluir descripciones detalladas con formato HTML. Presenta capacidades híbridas, tolerando el modo oscuro, personalización de viñetas tipográficas o alternancia en vivo entre Código Puro (HTML) y Visión Gráfica.
- **Generador PDF Híbrido Corporativo**: Descartando la generación rígida externa, la plataforma renderiza sus propios archivos PDF a través de vistas locales (`pdf.presupuesto`), imponiendo forzosamente la tipografía corporativa (`Lexend`) con diferentes pesos, espaciados perfeccionados, directrices en listas/párrafos e imponiendo paletas de alto contraste (#27272a) para repeler imperfecciones en el código HTML inyectado externamente (pegados sucios de otras webs). 

## Resumen

El sistema opera combinando lo mejor del archivado histórico heredado con control total e independiente para las finanzas futuras de la empresa, siendo lo suficientemente resiliente para reparar carencias de datos antiguos sobre la marcha y entregando documentación al cliente de calidad estética premium.
