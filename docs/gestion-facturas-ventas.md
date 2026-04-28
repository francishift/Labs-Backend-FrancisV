# Sistema de Ventas: Facturas y Presupuestos (Arquitectura Nativa)

La aplicación Labs Backend ha evolucionado hacia un modelo **100% nativo e independiente**. Hemos eliminado cualquier dependencia de ERPs externos (como Holded) para gestionar las finanzas, logrando un control absoluto sobre los datos, un rendimiento instantáneo y un diseño arquitectónico superior.

## 🏗️ Arquitectura Limpia y Servicios

Tanto los Presupuestos como las Facturas de Venta están regidos por un patrón de arquitectura limpia:

1. **FormRequests (`StoreFacturaRequest`, `StorePresupuestoRequest`)**: Toda la validación matemática, la prevención de inputs nulos o la protección contra inyecciones SQL ocurre aquí. El controlador nunca recibe datos sucios.
2. **Servicios (`FacturaService`, `PresupuestoService`)**: Son el núcleo. Se encargan de procesar las líneas, calcular el IVA, sumar los totales y ejecutar operaciones en transacciones seguras de base de datos (`DB::transaction`). Si una sola línea del presupuesto falla al guardarse, se hace un *rollback* completo y no se guardan datos corruptos.
3. **Controladores Ligeros (`FacturaController`, `PresupuestoController`)**: Su única misión es recibir la petición del frontend, pasársela al servicio, y devolver la redirección o el PDF al usuario.

## 📄 Generación de PDFs "Stateless" (Al Vuelo)

**Regla de Oro:** En Labs Backend, los PDFs financieros generados por el sistema **no se guardan en el disco duro**. 

Utilizamos el `DocumentPdfService` acoplado al motor DomPDF. 
- Al pulsar "Descargar PDF", el sistema recupera la información de la base de datos en ese milisegundo exacto, dibuja el documento en la memoria RAM, lo entrega al navegador del usuario, y lo destruye de la memoria.
- **Ventaja de Escalabilidad**: Esto previene el abarrotamiento del disco duro, elimina la necesidad de sincronizar archivos en arquitecturas de servidores múltiples (AWS/IONOS), y asegura que si modificas un precio a nivel de base de datos, el PDF generado un segundo después ya lo reflejará correctamente sin necesidad de regenerar/borrar versiones antiguas.

## 🔗 Integración Proyecto-Factura

Las facturas y presupuestos son entidades relacionadas:
- Un **Presupuesto** puede estar enlazado a un **Proyecto**. Desde el panel de control del proyecto puedes ver y emitir su presupuesto nativo.
- Las **Facturas** pueden asignarse a proyectos (agrupándolas) para tener un cálculo real del MRR (Ingreso Recurrente) y del *Profit* global del proyecto.

## 📊 Optimización de Listados (N+1)

El renderizado de los listados web de ventas, que puede cargar miles de facturas y presupuestos de un plumazo, está altamente optimizado:
- Se utiliza el Eager Loading (`->with(['cliente:id,name'])`) filtrando solo las columnas estrictamente necesarias (ID y Nombre del cliente), evitando el infame cuello de botella de Base de Datos N+1.
- Todos los listados están paginados nativamente.
