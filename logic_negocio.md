# Documentación de Lógica de Negocio

Este documento detalla las reglas de negocio, cálculos financieros críticos y estándares arquitectónicos implementados en el sistema de gestión de Labs.

## 1. Gestión de Gastos Fijos

### 1.1 Modelado de Software y Hosting (S/H)
El sistema utiliza un modelo de "Costo Compartido" por porcentaje para el software y hosting global.
- **Configuración Global**: Existe un porcentaje estándar (por defecto 2%) definido en la tabla `configuracion`.
- **Costo Anual de Software**: Se calcula sumando el costo anualizado de todos los registros de software en estado "Activa".
- **Cálculo de Repercusión**: Para cada proyecto o mantenimiento, se calcula:
  `Gasto S/H = (Total Anual Software * Porcentaje Software) / 100`

### 1.2 Snapshots Financieros
Para garantizar la integridad histórica:
- **Al crear/editar**: Los proyectos y mantenimientos guardan una "foto" (snapshot) del `coste_software_anual` y el `porcentaje_software` vigentes en ese momento.
- **Persistencia**: Si los precios globales del software cambian en el futuro, los registros antiguos mantienen sus cálculos originales.

### 1.3 Extensiones
- **Precio Aplicado**: Al vincular una extensión a un proyecto/mantenimiento, se guarda el precio actual en la tabla pivot (`precio_aplicado`).
- **Soft Deletes**: Las extensiones y el software utilizan borrado lógico. Si se eliminan, permanecen en la base de datos para no invalidar registros históricos, pero desaparecen de los selectores de nueva creación.

## 2. Proyectos y Servicios

### 2.1 Estados y Filtrado
- **En proceso**: Proyectos activos sin fecha de finalización necesaria.
- **Finalizado**: Proyectos con `fecha_fin` definida.
- **Estadísticas Anuales**: Incluyen tanto proyectos "En proceso" como aquellos "Finalizados" cuyo `fecha_fin` caiga dentro del año actual.

### 2.2 Cálculos de Costo y Facturación
- **Servicios por Horas**: Los servicios se asocian a un proyecto. El costo se calcula multiplicando la duración por el `precio_hora` del proyecto.
- **Total Gastos Proyecto**: `Costo Extensiones (Snapshots) + Gasto S/H (Snapshot) + Suma de Servicios registrados`.
- **Margen de Beneficio**: La diferencia entre el `presupuesto` del proyecto y el `Total Gastos`.

## 3. Mantenimientos

### 3.1 Periodicidad de Ingresos
El sistema automatiza la imputación de ingresos según el tipo de pago:
- **Anual**: El importe se imputa una vez al año (en el mes de inicio).
- **Trimestral**: El importe se imputa cada 3 meses desde la fecha de inicio.
- **Mensual**: El importe se imputa todos los meses.

### 3.2 Tasa Horaria de Mantenimiento
- Se aplica un coeficiente de descuento global (`descuento_mantenimiento`) sobre el precio por hora base para todos los servicios realizados bajo un contrato de mantenimiento, incentivando la recurrencia.

## 4. Administración de Clientes e Importación

### 4.1 Gestión de Clientes
- Los clientes son la entidad raíz de la mayoría de los registros. Se capturan datos fiscales y de contacto para su uso en proyectos y mantenimiento.

### 4.2 Importación desde Excel
- Se permite la importación masiva de clientes. El sistema valida el formato de columnas y procesa fechas de creación históricas para mantener la coherencia con sistemas previos.

## 4. Visualización y Dashboard

### 4.1 Gráfico "Repercutido por Extensiones/Software"
Muestra el impacto financiero real de los costos fijos:
- Suma el costo de las extensiones vinculadas a entidades activas.
- Agrega el gasto de software y hosting (overhead) calculado sobre dichas entidades.
- Permite visualizar qué extensiones generan mayor "costo de mantenimiento" para la empresa.

## 5. Arquitectura y Estándares de Código

### 5.1 Rendimiento (Caché a Nivel de Petición)
Para evitar redundancia de consultas (problema N+1):
- **`Configuracion::get()`**: Almacena estáticamente valores como el porcentaje de software.
- **`Software::getTotalAnual()`**: Evita recalcular el presupuesto global en listados de múltiples proyectos.

### 5.2 Integridad y Reutilización
- **Traits (`HandlesExtensionSnapshots`)**: Centraliza la lógica de snapshots para asegurar que Proyectos y Mantenimientos hereden las mismas reglas de negocio.
- **Query Scopes**: Uso de `active()` y `finishedThisYear()` para garantizar coherencia en todos los listados y estadísticas.

### 5.3 Accesibilidad y Frontend
- **Identificación de Campos**: Todos los componentes de formulario (`TextInput`, `SearchableSelect`, `MultiSelect`, `SearchInput`) deben recibir y vincular un `id` y `name` únicos. Esto es crítico para la accesibilidad (lectores de pantalla) y el correcto funcionamiento del auto-rellenado del navegador (Chrome warnings).
- **Búsqueda Debounced**: Las búsquedas globales en listas utilizan un debounce de 300ms para minimizar el número de peticiones al servidor mientras el usuario escribe.

## 6. Exportación de Informes (PDF)

### 6.1 Generación Dinámica
- **Motor**: Uso de `dompdf` para renderizar plantillas Blade en formato PDF.
- **Contexto Financiero**: Los PDFs de mantenimiento y proyectos incluyen el desglose completo de servicios y el balance calculado en el momento de la generación, respetando los filtros de período (mes/año) aplicados en la vista.

### 6.2 Modos de Entrega y Visualización
- **Visor Profesional (In-App)**: El sistema utiliza un componente dedicado (`PdfViewer.vue`) para embeber los PDFs. Esto permite mantener al usuario dentro de la aplicación, proporcionando una cabecera fija con controles de navegación ("Volver") y descarga. Es la solución estándar para evitar que los usuarios pierdan el contexto de navegación en dispositivos móviles.
- **Descargar (Attachment)**: Para casos donde se requiere el archivo físico (e.g. Safari compatibility), se ofrece un botón de descarga directa que fuerza el header `Content-Disposition: attachment`.
- **Compatibilidad Extensa**: Al usar un `<iframe>` dentro de un layout controlado, se resuelven los problemas de barras de navegación de navegadores móviles que ocultaban botones de retroceso.

## 7. Integración con Holded (API & Local Sync)

### 7.1 Gestión de Presupuestos (Sincronización Local)
El sistema utiliza un mecanismo de sincronización local para reducir la dependencia de la API de Holded y asegurar el rendimiento.
- **Persistencia Local**: Los presupuestos se almacenan en la tabla `presupuestos`, guardando campos como `holded_id`, `contact_id`, `total`, `status` y el JSON completo (`raw_data`).
- **Sincronización**: Al acceder a la lista de presupuestos, se dispara un proceso que descarga los documentos de Holded y utiliza `updateOrCreate` para actualizar la base de datos local.
- **Rango por Defecto**: Se sincronizan automáticamente los presupuestos desde el 01/01/2025 en adelante.

### 7.2 Sincronización de Contactos (Clientes)
Los contactos de Holded se sincronizan proactivamente con nuestra tabla de `clients`.
- **Filtro de Tipo**: Solo se procesan los contactos de tipo `client` en Holded.
- **Emparejamiento Inteligente**: Se utiliza el campo `code` de Holded para buscar coincidencias con el `cif_nif` local. Una vez emparejados, se guarda el `holded_id` en la columna `contact` para futuras actualizaciones.
- **Limpieza Segura**: Los contactos que dejan de ser de tipo `client` en Holded se eliminan localmente **solo si no tienen proyectos ni mantenimientos asociados**. En caso de tener historial vinculado, simplemente se desvinculan de Holded manteniendo el registro local.

### 7.3 Exportación y Visualización de PDF
- **Proxy de PDF**: El sistema utiliza el `holded_id` almacenado localmente para recuperar el binario del PDF desde la API de Holded.
- **Visor Integrado**: La visualización se realiza a través del `PdfViewer` del sistema, permitiendo al usuario ver el presupuesto de Holded sin salir de la ficha del cliente y pudiendo volver atrás con un solo clic.

### 7.4 Gestión de Errores
- **Feedback Visual**: El frontend muestra avisos específicos si la sincronización con Holded falla por temas de conexión o API Key, permitiendo seguir trabajando con los datos cacheados localmente.

---
*Ultima actualización: 12/01/2026*
