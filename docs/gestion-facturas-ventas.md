# Sistema de Ventas: Facturas y Presupuestos (Arquitectura Nativa)

La aplicación Labs Backend ha evolucionado hacia un modelo **100% nativo e independiente**. Hemos eliminado cualquier dependencia de ERPs externos (como Holded) para gestionar las finanzas, logrando un control absoluto sobre los datos, un rendimiento instantáneo y un diseño arquitectónico superior.

## 🏗️ Arquitectura Limpia y Servicios

Tanto los Presupuestos como las Facturas de Venta están regidos por un patrón de arquitectura limpia:

1. **FormRequests (`StoreFacturaRequest`, `StorePresupuestoRequest`)**: Toda la validación matemática, la prevención de inputs nulos o la protección contra inyecciones SQL ocurre aquí. El controlador nunca recibe datos sucios.
2. **Servicios (`FacturaService`, `PresupuestoService`)**: Son el núcleo. Se encargan de procesar las líneas, calcular el IVA, sumar los totales y ejecutar operaciones en transacciones seguras de base de datos (`DB::transaction`). Si una sola línea del presupuesto falla al guardarse, se hace un *rollback* completo y no se guardan datos corruptos.
3. **Controladores Ligeros (`FacturaController`, `PresupuestoController`)**: Su única misión es recibir la petición del frontend, pasársela al servicio, y devolver la redirección o el PDF al usuario.

## 🔄 Conversión de Presupuesto a Factura

La plataforma permite convertir un presupuesto directamente en una factura de venta con un solo clic.

### Flujo
1. El usuario accede al **listado** o a la **vista de detalle** (`Show`) del presupuesto.
2. Pulsa el botón/icono **"Convertir a Factura"** (disponible en ambos puntos de entrada).
3. Un modal de confirmación le pide que ratifique la acción.
4. El sistema crea la factura copiando todos los datos (cabecera + líneas) y redirige automáticamente a la nueva factura generada.

### Reglas de negocio
- **Solo se permite** convertir presupuestos en estado `Pendiente (0)` o `Aprobado (1)`.
- **Queda bloqueada** la conversión si el presupuesto está `Anulado (2)`, `Rechazado (3)` o ya `Facturado (4)`.
- Tras la conversión, el presupuesto queda marcado automáticamente como **`Facturado (4)`** (badge índigo).
- La nueva factura se genera con fecha de **hoy** y vencimiento según la configuración global (`default_vencimiento_dias`).
- Su PDF se sube a Google Drive de forma **asíncrona** (igual que en la creación manual).

### Numeración correlativa garantizada
El método `FacturaService::convertirDesdePresupuesto()` usa `lockForUpdate()` dentro de un `DB::transaction` para obtener el último número `FV-N` con bloqueo pesimista, garantizando que no se generen huecos ni duplicados aunque haya peticiones concurrentes.

### Estados del presupuesto (`PresupuestoStatus`)

| Valor | Nombre | Badge | Descripción |
|---|---|---|---|
| 0 | Pendiente | Gris | Recién creado, pendiente de revisión |
| 1 | Aprobado | Verde | Aceptado por el cliente |
| 2 | Anulado | Rojo | Cancelado, no generará factura |
| 3 | Rechazado | Rosa | Rechazado por el cliente |
| 4 | Facturado | Índigo | Convertido en factura de venta |

## 📄 Generación de PDFs "Stateless" (Al Vuelo)

**Regla de Oro:** En Labs Backend, los PDFs financieros generados por el sistema **no se guardan en el disco duro**. 

Utilizamos el `DocumentPdfService` acoplado al motor DomPDF. 
- Al pulsar "Descargar PDF", el sistema recupera la información de la base de datos en ese milisegundo exacto, dibuja el documento en la memoria RAM, lo entrega al navegador del usuario, y lo destruye de la memoria.
- **Ventaja de Escalabilidad**: Esto previene el abarrotamiento del disco duro, elimina la necesidad de sincronizar archivos en arquitecturas de servidores múltiples (AWS/IONOS), y asegura que si modificas un precio a nivel de base de datos, el PDF generado un segundo después ya lo reflejará correctamente sin necesidad de regenerar/borrar versiones antiguas.

## 🔗 Integración Proyecto-Factura

Las facturas y presupuestos son entidades relacionadas:
- Un **Presupuesto** puede estar enlazado a un **Proyecto**. Desde el panel de control del proyecto puedes ver y emitir su presupuesto nativo.
- Las **Facturas** pueden asignarse a proyectos (agrupándolas) para tener un cálculo real del MRR (Ingreso Recurrente) y del *Profit* global del proyecto.

## 📊 Optimización de Listados (N+1) y UX
 
 El renderizado de los listados web de ventas, que puede cargar miles de facturas y presupuestos de un plumazo, está altamente optimizado:
 - Se utiliza el Eager Loading (`->with(['cliente:id,name'])`) filtrando solo las columnas estrictamente necesarias (ID y Nombre del cliente), evitando el infame cuello de botella de Base de Datos N+1.
- **Descarga directa de PDF**: Desde la tabla de listado es posible descargar el archivo con un solo clic (vía parámetro `?download=1`) sin necesidad de abrir previamente la ficha o el visor.
- **Alineación matricial de acciones**: Las columnas de acciones de las tablas emplean ranuras de tamaño fijo (`w-7 h-7`) que preservan el espacio de cada función (ver, descargar, editar, convertir/duplicar, anular), asegurando que los iconos queden siempre perfectamente alineados en columnas verticales independientemente del estado de cada registro.
- Todos los listados están paginados nativamente.

## 📧 Envío de Correos y Archivos Adjuntos Seguros

La plataforma cuenta con un sistema robusto para el envío de facturas por correo:
1. **Generación al Vuelo:** El PDF se genera en memoria en el momento del envío usando `DocumentPdfService`.
2. **Archivos Adjuntos Volátiles:** Se permite subir archivos adjuntos adicionales (hasta 5 archivos, 10MB c/u). Estos archivos nunca se guardan permanentemente en el disco duro; viven temporalmente durante el ciclo de vida de la petición HTTP (`multipart/form-data`) y son inyectados directamente al Mailable (`FacturaPdfMail`). Una vez enviado el correo, el framework (Laravel) destruye los archivos de la memoria temporal.
3. **Validación Estricta:** El `SendFacturaEmailRequest` bloquea automáticamente scripts y ejecutables, admitiendo únicamente extensiones seguras (PDF, imágenes, ofimática) para prevenir inyecciones y *malware*.
4. **Registro SMTP:** Al utilizar el servidor SMTP corporativo (p. ej. Google Workspace), cada factura enviada queda registrada automáticamente en la carpeta de "Enviados" de la cuenta remitente, sin necesidad de guardar copias locales redundantes.
