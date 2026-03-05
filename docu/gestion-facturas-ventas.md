# Gestión de Facturas de Ventas

A diferencia de las **Facturas de Compras** (las cuales se suben en PDF y son procesadas localmente por Google Gemini AI), en *Labs Backend*, las **Facturas de Ventas** se manejan íntegramente de manera automática a través de la integración con el ERP externo **Holded**.

## 🔄 Flujo de Integración (Holded -> Labs Backend)

La aplicación no emite facturas directamente ni mantiene un CRUD manual para ventas. El ciclo de vida de la facturación de ingresos está directamente vinculado a los **Proyectos** y **Mantenimientos**.

### 1. Proyectos
Cuando se crea o gestiona un proyecto en Labs Backend, el sistema se sincroniza en tiempo real con Holded utilizando la API oficial (`HoldedApiService.php`).

- **Presupuestos y Facturas Profesionales:** La facturación se inicia en Holded generando un presupuesto.
- **Vinculación:** En la interfaz del proyecto de Labs Backend, el usuario puede vincular directamente uno o varios presupuestos de Holded.
- **Sincronización:** Una vez vinculado, el importe y el estado de dicho presupuesto influyen en la analítica financiera del proyecto (MRR y rentabilidad).
- **Proactive Export:** A través de la vinculación, Labs Backend tiene la capacidad de acceder a la descarga de esos documentos PDF de Holded automáticamente sin salir de la plataforma.

### 2. Mantenimientos
Los servicios recurrentes (mensuales, semestrales, anuales) facturados a clientes funcionan como un reloj automático.
- Cada contrato de mantenimiento está vinculado contablemente a los contactos sincronizados de Holded.
- El sistema es capaz de generar informes proactivos en PDF de la rentabilidad anual, incluyendo historial de precios inmutables, pero la emisión oficial de la factura recurrente se delega en Holded.

## 🚀 Almacenamiento Resiliente (Google Drive)

Aunque la emisión se realiza en Holded, Labs Backend incorpora un sistema de resiliencia documental. Si se configura, los documentos PDF relacionados con facturación de ventas (por ejemplo, aquellos asociados a presupuestos de proyectos ganados) pueden ser respaldados automáticamente en la estructura de carpetas de Google Drive de la empresa (**.../VENTAS/1tri/...**).

Para más información, revisar la lógica de negocio en `docu/logic_negocio.md` y `docu/setup-google-backups.md`.
