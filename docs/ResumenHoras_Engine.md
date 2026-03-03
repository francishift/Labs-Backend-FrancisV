# Motor de Rentabilidad Mensual (Dashboard)

## Lógica Anterior (Pre-2026)
Antes, el panel `Resumen de Horas` estaba estrechamente acoplado al "Tiempo Trabajado". La filosofía era simple:
- **Si el equipo registró horas en Abril:** Se creaba la fila de Abril y se sumaban las métricas correspondientes a esos servicios (tanto Proyectos como Mantenimientos).
- **Si el equipo NO registró horas en Abril:** El mes desaparecía por completo del sumatorio anual.

**El problema de esta lógica:** Los mantenimientos a los clientes se cobran *todos los meses* independientemente de que se abra una incidencia o haya horas trabajadas. Al estar acoplado visualmente al "Tiempo Trabajado", las gráficas y la facturación global no reflejaban el dinero real ingresado pasivamente y distorsionaban los KPI de rentabilidad.

## Nueva Arquitectura (Base Calendario)
Para corregir esto, el backend `ResumenHoraController.php` fue rediseñado usando un paradigma de "Ingreso Pasivo Mensual Puro":

1. **Iteración Calendárica:** El servidor inicia creando desde el mes 1 (Enero) hasta el mes relativo de Máximo ($maxMonth). Si pedimos el año actual, iterará hasta el *Mes Actual* (por ej: Junio = Mes 6). Si pedimos un año anterior, iterará siempre hasta Diciembre (Mes 12).
2. **Inyección de Cuota Activa (Mantenimientos):** Dentro de cada ciclo para cada mes pre-establecido (Enero, Febrero, Marzo...), el sistema llama la función `calculatePeriodIncome($mes, $año)` de *todos los Mantenimientos Activos* en la tabla de Contratos. De esta forma, cada mes suma el Ingreso Fijo de todos los clientes sin importar si se han registrado servicios.
3. **Cruce Estructural (Proyectos + N+1):**
    - Posteriormente, el Controlador arrastra la colección de **Horas Trabajadas** (`$todos`).
    - Esas horas se *encajan* o filtran por su respectivo mes en la plantilla del calendario que hemos creado.
    - Se suma el "Costo en Horas Interno" de dichas taréas.
    - Se acumula el total del "Ingreso Fijo de Proyectos" *únicamente* en el último mes trabajado de ese Proyecto en concreto en el año (evitando que un proyecto con horas en 4 meses distintos duplique su presupuesto ×4).
4. **Agrupación y Limpieza:** Los meses que tengan dinero inyectado ($) pasivamente de los Mantenimientos o tengan minutos (horas trabajadas), entran al render final.
5. **Estadísticas Nativas ($stats):** Se han dividido las variables en un objeto `$stats` con las llaves `total_facturado`, `total_mantenimientos` y `total_proyectos` para que la vista Vue pueda pintar un Desglose jerárquico directamente apoyado en Heroicons.

## Tests (Carbon Mock)
A raíz de la implantación de la "Base Calendárica" y la "Inyección de Cuota Mensual", el comando de tests tradicional variaría según en qué mes de la vida real estemos testeando. 
Para los Tests en CI/CD o locales, en `ResumenHoraTest.php` se forza la simulación temporal de un año y mes congelado utilizando el facade del Framework: 
`\Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2024, 6, 15));`
Con esto, nos aseguraremos estructuralmente de testear que para el mes 6 de un Mantenimiento Activo de 200€, el render devuelva matemáticamente $1.200 (200€ x 6 meses).

## Layout UI Responsivo (StatCards)
Todos los módulos de analítica superior (Resumen Horas, Proyectos y Mantenimientos) comparten una directriz estandarizada estéticamente para unificar toda la interfaz visual de Laravel admin:
- Tipografía estricta unificada (`small-value="true"` en todos los `<StatCard>`).
- Rejilla adaptativa personalizada que salta al formato escritorio/4 columnas **únicamente cuando la pantalla excede los `1530px` de ancho** (`grid-cols-1 md:grid-cols-2 min-[1530px]:grid-cols-X`) para salvaguardar el diseño del dashboard en resoluciones intermedias de laptops y tablets grandes, previniendo el desbordamiento u "aplastamiento" del flexbox.
- El módulo Resumen mantiene scroll lateral seguro `overflow-x-auto` en dispositivos móviles.
