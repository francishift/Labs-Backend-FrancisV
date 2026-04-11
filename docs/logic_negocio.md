# Lógica de Negocio y Reglas Core

Especificación técnica de la capa de negocio, cálculos financieros y arquitectura del sistema.

## 1. Control de Acceso (ACL)

El ecosistema ACL se rige por `Spatie Laravel-Permission`.

### 1.1 Jerarquía de Roles
- **Admin**: Privilegios absolutos (Gestión de Usuarios, Configuraciones Globales, Sincronización Holded).
- **Coordinador**: CRUD sobre Clientes, Proyectos, Mantenimientos y Software (S/H). Bloqueo sobre recursos de nivel sistema.
- **Visor**: Lectura restringida a visualización de métricas de Proyectos y Mantenimientos.

### 1.2 Políticas Transversales
- **Rotación Inicial**: Registro de usuarios fuerza el flag `must_change_password`. El middleware bloquea peticiones subsecuentes obligando a la actualización de credenciales en el primer inicio de sesión.
- **Localización**: Overrides completos en plantillas de mail (Verificación y Recuperación) forzando el idioma (ES).

## 2. Gestión Financiera Interna (Infraestructura S/H)

### 2.1 Repercusión de Software
Implementa modelo de costo compartido (`Overhead`):
- Los registros de licencia en estado "Activa" impactan en la variable global `coste_software_anual`.
- Los Modelos `Proyecto` y `Mantenimiento` capturan en su ciclo de inserción (Create) un snapshot inmutable del `coste_software_anual` y del `%_software` para aislar sus métricas de futuras fluctuaciones de precio.

### 2.2 Extensiones Base
Integración a nivel pivote (`belongsToMany`):
- El esquema divide el costo base de una extensión entre el conteo dinámico agregado de Proyectos (status: "En Proceso") y Mantenimientos (status: "En Curso") enlazados.
- El Worker `extensions:recalculate` propaga y sobrescribe el atributo `precio_aplicado` en todos los pivotes afectados al detectarse altas o bajas, garantizando la equidad iterativa. 

## 3. Facturación Recurrente (Mantenimientos)

### 3.1 Motor Prorrateador
El atributo `tipo_pago` determina la división matemática de rentabilidad en el dashboard mensual:
- **Mensual**: Inyección íntegra x1 (`importe / 1`).
- **Trimestral**: Inyección estadística x3 (`importe / 3`).
- **Anual**: Inyección estadística x12 (`importe / 12`).
- Las mutaciones de precio ingresan como nuevos registros en `mantenimiento_precios` estableciendo fecha límite. Funciones sumatorias consultan esta tabla con operadores lógicos (`<= date`) en lugar del precio nominal del modelo `Mantenimiento`.

### 3.2 Tasa Horaria de Servicios
- La inserción de un nuevo servicio en Mantenimiento captura el **Precio Hora Global** (`precio - descuento_mantenimiento`).
- El Profit/Neto del servicio es evaluado siempre mediante el costo indexado (`(minutos / 60) * precio_hora_indexado`), previniendo que alteraciones de tarifas afecten a la métrica prehistórica registrada.

## 4. Rentabilidad Estratégica (Proyectos)

### 4.1 Ecuación de Balance
El KPI final (Beneficio) se calcula bajo la siguiente constante:
`Beneficio = Presupuesto_Oficial - (Coste_Horas_Servicios + Coste_Compartido_Extensiones + Overhead_Software)`

### 4.2 Centralización de Consultas Anidadas
El Modelo `Client` encapsula atributos Accessor delegando las sumatorias complejas al SGBD:
- `getActiveProjectsBudgetAttribute`: SUM de presupuestos filtrados por status "En proceso".
- `getMonthlyMaintenanceIncomeAttribute`: Ejecución de algoritmos de prorrateo sobre colecciones anidadas.

### 4.3 Output PDF (DomPDF Stream)
- El endpoint ignora el almacenamiento en disco (`Storage::put`). La respuesta invoca `->output()` inyectando los binarios directamente a memoria para su emisión adjunta por correo (`Mailable`) bajo el Content-Type `application/pdf`.

## 5. API Holded Sync

### 5.1 Clientes
- La importación resuelve llaves foráneas validando por campo `CIF/NIF` o `Email`.
- Redundancia de ID: El campo estructurado JSON `secondary_contacts` en la tabla `clients` soporta la vinculación M:1 de entidades fraccionadas provenientes del ERP de Holded.

### 5.2 Facturas de Venta
- Indexación híbrida `Lazy`. Listado consume API real-time, pero la apertura (`downloadPdf`) mapea el registro remoto, lo persiste en Drive bajo la nomenclatura `{Año}/VENTAS/{Trimestre}tri/` y genera acceso de caché autónomo DB.

## 6. IA para Facturas de Compra (PurchaseFactura)

- **Gemini NLP**: Integración asíncrona hacia `Gemini 2.5 Flash` decodificando el BLOB del PDF a JSON.
- **Mapeo Físico**: Jerarquía estricta remota `{Año}/COMPRAS/{Trimestre}tri/`. Movimiento dinámico automático (Storage `move()`) caso que la fecha extraída y validada del emisor diverja al mes de subida.
- **Resolución Unicidad**: Colisiones levantan el flag `duplicada` y el prefijo temporal `DUP-`. La consolidación es delegada mediante el endpoint `confirmOverwrite()`, aplicando `forceDelete` al expediente previo y `move()` en Google Drive.

## 7. Frontend Architecture (Vue / Inertia)

### 7.1 Validación Lado Servidor
- **Bloqueo Brick/Math**: Laravel convierte validaciones numéricas pesadas (`max:bytes`) nativas en floats. Al procesarlas la clase intrínseca `BigNumber::of()` crashea emitiendo Exceptions. 
- **Solución Obligada**: Archivos pesados deben validarse usando abstracción de metaclase `$value->getSize() > 10485760` dentro de un `<Closure>` customizado en el FormRequest.

### 7.2 Componentes e Interfaz Común
- **Búsquedas Globales**: Requerimiento técnico para el uso estricto de `lodash/debounce` sobre cualquier input teclear. Cancelada la compatibilidad con directiva `throttle` para Input Searchs.
- **Totales Asíncronos**: El listado de tablas clona el framework `Illuminate\Database\Eloquent\Builder` antes de instanciar `$query->paginate()`. Invoca operaciones sumatorias directas (`clone $query->sum('base_amount')`) rindiendo información reactiva multi-tabla en O(1) peticiones.

## 8. Sincronización Google Calendar

La capa del modelo `Nota` dispara un Objeto `NotaObserver` sobre mutaciones CRUD delegando peticiones `cURL` asíncronas vía Google API SDK si la columna `sync_calendar` retorna $true. Las peticiones inyectan un header configurado con `UseDefault: false` (reminders) blindando redundancia de avisos entre iOS nativo PWA y Google Apps.
