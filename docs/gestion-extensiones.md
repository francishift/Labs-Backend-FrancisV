# Gestión de Extensiones de Terceros

La sección de **Extensiones de terceros** permite gestionar las licencias de software, complementos (plugins) y demás servicios externos utilizados en los Proyectos y Mantenimientos.

## Características Principales

1. **Campos Principales**:
   - `nombre`: Nombre comercial de la extensión (ej. Yootheme Pro, Akeeba Backup).
   - `url`: Enlace de referencia o sitio web oficial.
   - `descripcion`: Detalles adicionales o notas breves sobre la extensión.
   - `precio`: Coste base de la extensión.
   - `tipo_licencia`: Define la periodicidad ('Anual', 'Mensual', 'Pago único').
   - `estado`: Define si la extensión está activa ('Activada') o si se ha dejado de renovar/usar ('Cancelada'). Se introdujo para evitar borrar el histórico de las extensiones obsoletas.

2. **Relación con Proyectos y Mantenimientos**:
   - Las extensiones se asocian de base a `Proyectos` y `Mantenimientos` mediante tablas pivote (`proyecto_extension` y `mantenimiento_extension`).
   - El precio puede ser sobrescrito en el pivote especificando el `precio_aplicado` particular a un proyecto o mantenimiento.

3. **Dashboard y Analíticas (`Uso de Extensiones`)**:
   - En el Dashboard de la aplicación se presenta un gráfico vertical sobre el **Uso de Extensiones**.
   - **Optimización**: Para evitar el problema comunmente conocido como *N+1*, el método principal encargado de calcular estos usos (`getUsageStatsForChart` en `app/Models/Extension.php`) utiliza `withCount(['proyectos', 'mantenimientos'])`.
   - Sólo se computan las repeticiones en Proyectos y Mantenimientos que se encuentren **Activos** o bien que hayan **finalizado en el transcurso del año actual**. Las extensiones que tengan $0$ usos siguiendo esta lógica no se muestran en el gráfico.

## Aspectos Técnicos

El ciclo de la consulta (Backend -> Frontend) para el gráfico de uso es el siguiente:
1. `DashboardController` invoca la función estática `getUsageStatsForChart()` alojada en el modelo `Extension`.
2. Se inyecta la métrica junto con los demás datos bajo el *key* `uso_extensiones`.
3. El frontend de Vue (`Dashboard.vue`) alimenta el componente de gráficos ECharts.

*(Documentación generada y actualizada en marzo 2026).*
