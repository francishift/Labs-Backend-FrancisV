# Arquitectura y Lógica de Negocio

La filosofía de diseño de Labs Backend pivota sobre la **Clean Architecture** y los principios **SOLID**. El objetivo primordial es que los Controladores sean extremadamente ligeros (Thin Controllers) y deleguen el 100% de la lógica pesada a capas inferiores.

## 1. La Barrera de Seguridad (FormRequests)

Ningún controlador procesa variables del objeto `$request` directamente.
Se utilizan clases como `StoreUserRequest`, `StoreMantenimientoRequest` o `UpdateProyectoRequest`.
- **Ventajas**: Las validaciones de emails únicos, reglas complejas de fechas, o permisos de inyección se interceptan aquí. Si los datos son erróneos, Laravel devuelve un código `422` sin que la petición llegue siquiera a asomar por el controlador.

## 2. La Capa de Negocio (Services)

La lógica transaccional, cálculos matemáticos, conexiones a APIs de terceros y guardados en base de datos residen en la carpeta `app/Services`.
- **Ejemplo `MantenimientoService`**:
  Se encarga de iniciar una transacción de base de datos (`DB::transaction()`), crear el mantenimiento, calcular la periodicidad, sincronizar las extensiones de servidor asociadas y guardar en la base de datos el "Price Snapshot" (La foto del precio en el momento exacto para garantizar inmutabilidad histórica).
- **Ejemplo `UserService`**:
  No solo crea al usuario en MySQL, sino que invoca asíncronamente al módulo de WireGuard, le genera una IP dentro de la VPN, levanta el log de acceso y envía el email de bienvenida todo envuelto en una transacción. Si el envío del email falla, la VPN y el usuario no se crean para evitar registros huérfanos.

## 3. Factoría de Documentos (DocumentPdfService)

Todos los PDFs de la aplicación que no vengan de proveedores externos (es decir, que sean generados por Labs) se canalizan a través de `DocumentPdfService`.
- **Por qué**: Evitamos que 4 controladores distintos (Clientes, Proyectos, Facturas, Mantenimientos) instancien librerías visuales como DomPDF o traten imágenes Base64. Si mañana cambiamos el motor DomPDF por Snappy o Browsershot, solo tocaremos una clase.

## 4. Inmutabilidad Histórica (Snapshots)

A diferencia de sistemas básicos, Labs Backend no solo vincula una "Extensión" a un "Mantenimiento" con una simple relación *BelongsToMany*.
Para las finanzas de proyectos y mantenimientos, el sistema utiliza **Snapshots** (`syncExtensionSnapshots()`). Esto significa que clona y guarda el precio exacto que tenía esa extensión/software en el momento en el que se contrató.
- **Beneficio**: Si dentro de 3 años le subes el precio a la extensión de servidor "Hostinger" de 10€ a 15€, los balances históricos de los proyectos de 2024 seguirán calculándose a 10€, asegurando cuadros de mando 100% precisos (MRR exacto).

## 5. Pruebas Automatizadas (Testing)

Todo este engranaje está sostenido por una suite robusta en `tests/`. 
1. **Feature Tests (`*CrudTest.php`)**: Simulan al administrador interactuando con los endpoints (Controladores) verificando que el guardado, actualización y las rutas seguras funcionan como se espera (incluyendo rechazar validaciones erróneas).
2. **Unit Tests**: Verifican las funciones puramente lógicas de los Servicios y los Modelos (por ejemplo, `FacturaServiceTest` asegura que el cálculo del 21% de IVA sobre un sumatorio de líneas es matemáticamente preciso hasta el segundo decimal).
