# Arquitectura Frontend y Componentes UI

La capa de presentación de Labs Backend (basada en Vue 3 y Tailwind CSS) ha evolucionado para utilizar componentes reutilizables y asíncronos, garantizando un rendimiento óptimo y una experiencia de usuario fluida, en especial en dispositivos móviles y con grandes volúmenes de datos.

## 1. Selects Asíncronos (Carga AJAX)

Para evitar la sobrecarga de memoria del cliente y del servidor (que ocurría al inyectar miles de registros en la renderización inicial de Inertia), la plataforma cuenta con una arquitectura de carga asíncrona para los desplegables:

- **Componentes Core:** `AjaxSearchableSelect.vue` y `AjaxMultiSelect.vue`.
- **Funcionamiento:** Estos componentes ya no reciben el array de `options` completo desde el controlador principal de la vista. En su lugar, realizan una petición HTTP (AJAX) al `DropdownController` de la API interna (`/admin/api/dropdown/{type}`) mientras el usuario escribe, buscando coincidencias en tiempo real.
- **Ventaja Técnica:** Reduce drásticamente el peso del JSON inicial de Inertia, limpia los controladores de lógicas de extracción de catálogos (evitando cuellos de botella y exceso de consultas a base de datos) y previene bloqueos en el navegador o el servidor Vite.

## 2. Composable de Filtrado Reactivo (`useFilters.js`)

Se ha estandarizado la lógica de filtrado avanzado en las tablas de listados mediante la Composition API de Vue.

- **`useFilters`**: Extrae toda la lógica repetitiva (watches, debounce) y la unifica. Sincroniza automáticamente los filtros reactivos (ej. búsqueda por nombre, filtrado por cliente o proyecto asociado) con la URL a través de `router.get()`, utilizando `preserveState: true` y `preserveScroll: true` para no interrumpir la experiencia de navegación del usuario.
- **Implementación:** Se utiliza activamente en listados complejos como Servicios, Mantenimientos y Tareas.

## 3. DataTables Responsivos

El componente base `<DataTable.vue>` está optimizado para dispositivos móviles:

- Implementa un control de desbordamiento horizontal (`overflow-x-auto`) que permite scrollear la tabla en pantallas pequeñas sin romper la tarjeta contenedora (`<Card>`).
- Se integra de forma nativa con los componentes asíncronos y con la paginación genérica de Laravel.

## 4. Navegación Móvil (Sidebar)

El componente principal `Sidebar.vue` está preparado para adaptarse contextualmente al dispositivo del usuario:
- Implementa un control dinámico de cierre automático. Si el usuario está en móvil (`window.innerWidth < 1024`) y hace clic en un enlace de navegación, el menú lateral se oculta automáticamente (toggle local) al detectar que Inertia ha comenzado a cargar la nueva página.
