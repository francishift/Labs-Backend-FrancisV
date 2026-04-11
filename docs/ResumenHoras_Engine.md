# Motor de Rentabilidad Mensual (Dashboard)

## Arquitectura de Procesamiento

El controlador `ResumenHoraController.php` estructura el resumen financiero a través de una generación calendárica mensual unificando ingresos fijos y horas trabajadas.

1. **Iteración Calendárica:** El sistema genera una matriz de 12 meses fijos.
2. **Extracción de Cuotas (Mantenimientos):** La función `calculatePeriodIncome($mes, $año)` procesa la tabla de contratos evaluando el estado activo de cada mantenimiento por mes. El sistema suma o prorratea el ingreso fijo mensual (independiente de si existen servicios registrados).
3. **Mapeo de Horas Trabajadas (Servicios):**
    - Se extrae la colección combinada de servicios asociados a Proyectos y Mantenimientos filtrada por rango de fechas (y opcionalmente por cliente).
    - Se calcula el costo interno multiplicando la duración registrada en el servicio por la franja tarifaria (`precio_hora` del proyecto, del mantenimiento o fallback de la configuración global).
    - Se agrupan bajo su mes respectivo en la iteración calendárica.
4. **Acumulación de Presupuestos (Proyectos):** El valor financiero asigando al presupuesto de un proyecto se acumula estrictamente en el último mes de trabajo registrado durante el año, evadiendo duplicidades de suma.
5. **Agrupación y Limpieza:** Los meses sin ingresos por mantenimiento ni minutos imputados son discriminados en el volcado final (`$resumenMensual`).
6. **Métrica Global (`$stats`):** Se exponen indicadores sumatorios globales como `total_facturado`, `total_mantenimientos` y `total_proyectos` consumidos por la capa de presentación (Vue).

## Testing

La simulación y testeo temporal (para validar el prorrateo mensual recurrente) demanda la modificación directa de la clase global `Carbon`. Los tests de integración implementan invariablemente:
`\Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2024, 6, 15));`

## Estructura UI y Componentes

El módulo despliega una jerarquía estandarizada compartida con Proyectos y Mantenimientos:
- Grid adaptativo `2xl:grid-cols-4` para los indicadores principales (`<StatCard small-value="true">`).
- Sistema de refiltrado reactivo apoyado en tarjetas base (`<Card>`) con inputs selectores agnósticos. Desplazamiento horizontal condicionado en modo móvil (`overflow-x-auto`).
