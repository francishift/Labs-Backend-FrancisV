# Especificación Técnica: Presupuestos Nativos

## 1. Arquitectura de Datos

El sistema de Presupuestos utiliza un motor relacional local y opera de manera autónoma, almacenando la totalidad de la información en MySQL.

- **Modelo Principal (`App\Models\Presupuesto`)**:
  - `number`: Identificador autogenerado (ej. `PR-5893`).
  - `client_id`: Clave foránea referenciando a la tabla `clients` para la carga de datos demográficos (CIF, dirección, email).
  - `description`: Campo destinado a texto enriquecido (HTML) proveniente del componente WYSIWYG.
  - `status`: Estado del ciclo de vida gestionado obligatoriamente por un Enum estricto (`App\Enums\PresupuestoStatus`). Los estados permitidos son: `PENDING` (Pendiente), `APPROVED` (Aprobado), `CANCELED` (Anulado), y `REJECTED` (Rechazado).
  - `raw_data`: Contenedor JSON heredado para retrocompatibilidad con facturas antiguas (Fallback).

- **Lógica Financiera y Estados**: 
  - Regla de negocio estricta: Los cálculos de totales financieros globales informados a nivel listado y dashboard actúan únicamente sobre los presupuestos que se encuentran en estado `PENDING` o `APPROVED`. Presupuestos `CANCELED` o `REJECTED` se excluyen de la contabilidad activa de la empresa.

- **Modelo Relacional (`App\Models\PresupuestoLinea`)**:
  - Mantiene las líneas de concepto individuales, cada una con su desglose parametrizado: `precio_unitario`, `cantidad`, `porcentaje_iva` y `porcentaje_irpf`.

## 2. Componentes Frontend (Vue 3 / Inertia)

- **WYSIWYG Integrado**: Se emplea la librería `@vueup/vue-quill` en los formularios para permitir la inyección de descripciones formateadas HTML. Se expone un control de vista dual que alterna entre el editor gráfico y la vista de código fuente HTML.
- **Motor Financiero Frontend**: Los formularios `Create.vue` y `Edit.vue` calculan en tiempo real los totales `Neto` y `A Pagar` iterando asíncronamente las líneas añadidas por el usuario, antes de persistirlo de forma segura en el backend.

## 3. Renderizado PDF & Almacenamiento

### 3.1 PDF Generator (`DOMPDF`)
- El método `downloadPdf` exporta la vista `pdf.presupuesto`.
- El diseño forzosamente inyecta estilos CSS anidados utilizando la tipografía `Lexend` con color `#27272a`.
- Aplica reglas restrictivas de espaciado y márgenes sobre elementos `p` y `ul`/`ol` para estandarizar visualmente la salida de componentes externos copiados y pegados en el editor Quill.

### 3.2 Persistencia en Google Drive
- Al invocar el guardado de un presupuesto, un worker en segundo plano (Background Process):
   1. Genera la representación final cifrada del PDF.
   2. Localiza/Crea la carpeta estructurada en base al `Año Actual`.
   3. Sube el `.pdf` como registro inmutable asociando su hash al modelo, logrando un archivo documental replicado independientemente.

## 4. Email Integrado (Mailable)
- `PresupuestoPdfMail`: Clase configurada con plantillas Markdown. El Administrador dispara el evento vinculando internamente el binario del PDF generado adjuntándolo en memoria para el envío transaccional inmediato.
