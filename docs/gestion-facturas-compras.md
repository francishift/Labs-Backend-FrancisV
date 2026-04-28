# Sistema de Facturas de Compras (IA + Google Drive)

El módulo de Compras de Labs Backend es uno de los componentes más avanzados y automatizados del sistema. Utiliza un motor de **Inteligencia Artificial (Google Gemini)** combinado con la **API de Google Drive** para extraer, almacenar y organizar todas las facturas enviadas por los proveedores (Gastos).

## 🤖 Extracción con Google Gemini AI

El corazón del módulo reside en `PurchaseFacturaService`. Cuando subes un PDF (o imagen) de una factura de un proveedor:
1. El archivo en bruto se envía directamente a los modelos multimodales de Google Gemini (vía la clase de IA configurada).
2. Gemini analiza visualmente y lee el documento, extrayendo mediante un prompt de sistema estructurado los siguientes datos:
   - Proveedor (Nombre exacto).
   - Fecha de emisión.
   - Número de factura.
   - Base Imponible (Importe Neto).
   - Porcentaje e importe de IVA.
   - Total final.
3. El servicio parsea el texto y normaliza la salida garantizando que sea matemáticamente precisa.

## ☁️ Resiliencia y Almacenamiento en Drive

A diferencia de los PDFs de venta (que se generan al vuelo), las facturas de proveedores son documentos originales que sí necesitamos preservar por temas legales y fiscales. Para no saturar el servidor local (cumpliendo con nuestras directrices de Escalabilidad), utilizamos una solución Cloud-First:

1. El servicio `GoogleDriveDocumentService` toma el PDF subido y se conecta vía OAuth Service Account a la unidad compartida de la empresa.
2. Identifica automáticamente el trimestre fiscal de la factura (basándose en la fecha extraída por la IA) y la carpeta (Ej. `Facturas_Gastos/1TRIMESTRE`).
3. Sube físicamente el archivo allí y nos devuelve un `file_id`.
4. El servidor local solo guarda en base de datos la información extraída (importes) y el `file_id`.
5. Cuando el administrador desea ver la factura, el sistema hace un _stream_ en directo usando la ID sin descargar el archivo entero en el disco duro.

## 🛡️ Prevención de Duplicados

`PurchaseFacturaService` cuenta con un motor anti-duplicados. Si subes la misma factura dos veces, o si detecta el mismo número de factura y mismo proveedor, el sistema:
1. Levanta un Flag transaccional.
2. Presenta al usuario una alerta visual indicando que es un posible duplicado.
3. Ofrece una opción de confirmación ("Sobrescribir archivo"), la cual invoca un método de resolución de conflictos que elimina el registro viejo (tanto en base de datos como en Google Drive) y consolida el nuevo.

## 🧪 Pruebas Automatizadas

Este motor IA cuenta con su propia suite de pruebas automatizadas en `tests/Feature/Admin/PurchaseFacturaAITest.php`, asegurando que la conexión simulada, la detección de duplicados y los errores de lectura jamás comprometan el funcionamiento general de la plataforma.
