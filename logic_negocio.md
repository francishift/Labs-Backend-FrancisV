# Documentación de Lógica de Negocio Completa

Este documento detalla todas las reglas de negocio, cálculos financieros, estándares de código y arquitecturas implementadas en el sistema de gestión de Labs.

---

## 1. Control de Acceso y Usuarios

El sistema utiliza **Spatie Laravel-Permission** para gestionar roles y permisos.

### 1.1 Roles del Sistema
- **Admin**: Acceso total a todas las secciones, incluyendo gestión de usuarios, configuración global y sincronización con Holded.
- **Coordinador**: Administra Clientes, Proyectos, Mantenimientos y Software. No puede gestionar usuarios ni configuraciones globales del sistema.
- **Visor**: Acceso de solo lectura a proyectos y mantenimientos.

### 1.2 Seguridad de Usuario
- **Cambio Obligatorio de Password**: Al crear un usuario desde el panel, se le asigna una clave temporal y se activa el flag `must_change_password`. El sistema fuerza el cambio en el primer login.
- **Notificaciones**: Las comunicaciones de verificación de email y recuperación de contraseña están totalmente localizadas al español con plantillas personalizadas.

---

## 2. Gestión de Gastos Fijos (Infraestructura)

### 2.1 Modelado de Software y Hosting (S/H)
El sistema utiliza un modelo de **Costo Compartido** para repercutir los gastos de licencias y servidores en los proyectos activos.
- **Software**: Se registran licencias individuales con su coste anual. Solo las marcadas como "Activa" computan para el cálculo.
- **Software Anual**: Suma de todos los costes de software activos.
- **Snapshot de Overhead**: Para evitar que cambios futuros en el precio del software alteren el balance histórico, cada proyecto/mantenimiento guarda en su creación el `coste_software_anual` y el `%_software` vigentes.

### 2.2 Extensiones y Herramientas
- **Repositorio**: Inventario de plugins, temas o herramientas específicas.
- **Precio Aplicado**: Al vincular una extensión, el sistema copia su precio actual a la relación (`pivot`). Esto permite subir el precio global de una extensión sin afectar a lo ya facturado a clientes antiguos.

---

## 3. Mantenimientos (SaaS & Recurrencia)

### 3.1 Ingresos y Prorrateo
Los ingresos de mantenimiento se calculan según el `tipo_pago`:
- **Mensual**: El `importe` se registra íntegro cada mes.
- **Trimestral**: Se divide el `importe` entre 3 para obtener la repercusión mensual estadística.
- **Anual**: Se divide el `importe` entre 12 para los cálculos de balance mensual.

### 3.2 Tasa Horaria Reducida
- Se aplica un coeficiente (`descuento_mantenimiento`) definido en configuración sobre el precio/hora base.
- Todos los servicios realizados dentro de un contrato de mantenimiento se computan automáticamente con esta tasa reducida, a menos que se especifique un precio fijo.

---

## 4. Proyectos y Balance Financiero

### 4.1 Cálculo de Rentabilidad (Balance)
El beneficio real de un Proyecto o Mantenimiento se calcula como:
`Beneficio = Ingresos - (Coste Servicios + Coste Extensiones + Repercusión Software)`

- **Coste Servicios**: `Sum(minutos / 60 * precio_hora)`.
- **Repercusión Software**: `(Total Software Anual * % Repercusión) / 100`.

### 4.2 Estados y Ciclo de Vida
- **En proceso**: Computan para las estadísticas de "MRR" y carga actual.
- **Finalizado**: Requieren `fecha_fin`. Se mantienen en el histórico para el balance anual.
- **Borrado Lógico**: La mayoría de entidades usan `SoftDeletes` para preservar la integridad referencial de los informes financieros.

---

## 5. Integración con Holded (API Sync)

### 5.1 Sincronización de Presupuestos
- **Estrategia**: Sincronización local proactiva. Al consultar, el sistema baja datos desde el **01/01/2025** para asegurar que el historial relevante esté disponible instantáneamente sin latencia de API.
- **Mapeo de Datos**: Se almacenan el ID, contacto, estados y el objeto JSON original (`raw_data`).

### 5.2 Sincronización de Clientes
- Los contactos de Holded de tipo `client` se vinculan mediante el `CIF/NIF`.
- **Limpieza Segura**: Si un cliente se elimina en Holded, el sistema local solo lo borra si no tiene proyectos o mantenimientos asociados. Si tiene datos, permanece como cliente local e independiente.

---

## 6. Frontend y UX Estándar

### 6.1 Visor de PDF Profesional
- **In-App**: Los documentos no se abren en una pestaña cruda. Se embeben en un componente Vue con cabecera fija y botón de retroceso SPA.
- **Compatibilidad**: Soluciona el problema de "atrapamiento" en móviles y la falta de controles de descarga en Safari.

### 6.2 Componentes Reutilizables
- **Debounced Search**: Búsqueda global con 300ms de espera para optimizar recursos del servidor.
- **DataTable**: Componente agnóstico que gestiona estados vacíos, alineaciones y clics en fila.
- **Accesibilidad**: Todos los inputs están vinculados mediante IDs y Nombres únicos para cumplir con los estándares de lectores de pantalla y autocompletado.

---
*Documentación finalizada el 12/01/2026*
