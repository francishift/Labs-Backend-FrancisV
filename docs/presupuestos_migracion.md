# Resumen: Migración a Presupuestos Nativos 🚀

Este documento detalla la transformación técnica del sistema de presupuestos, migrando hacia una arquitectura autónoma y nativa que elimina las llamadas síncronas a la red externa de Holded.

## 🔧 Actualizaciones Implementadas

### 1. Migración de Componentes Históricos
Se ha ejecutado un conjunto de procesos de bases de datos (`migrations`) que generan el modelado nativo para Presupuestos y sus Líneas (conceptos).
Un script de automatización (`MigrateHoldedPresupuestos`) ha procesado el JSON proveniente de Holded extrayendo el histórico de datos:
* ✅ Conversión de precios con IVA/IRPF individualizados por cada línea.
* ✅ Mantenimiento intacto del número oficial de documento (P. ej: *PR-5893*).
* ✅ Desvinculación de la petición API en tiempo real del `HoldedService`.

### 2. Panel de Control y Vistas Vue
Las vistas están estandarizadas a la arquitectura de componentes preexistente en (`resources/js/Pages/Admin/Presupuestos`):
* **El Dashboard (Index)**: Presenta la información en tablas de alto rendimiento sin depender de latencias externas.
* **Formulario Multi-línea (Create y Edit)**: Interfaz dinámica que permite gestionar líneas de concepto autocalculando en tiempo real el Resumen Financiero lateral (Neto y a Pagar).
* **Visor del Documento (Show)**: Presenta la tarjeta de información global junto con la vista previa interactiva (`Iframe`) del archivo PDF generado.

### 3. Generador de PDFs Corporativos (`DOMPDF`)
El sistema utiliza una plantilla HTML/CSS local dedicada para renderizar y exportar los presupuestos (`pdf.presupuesto`).
* El diseño implementa la hoja de estilos con paletas depuradas de alto contraste (#27272a), respetando bordes y jerarquías métricas para asimilarse al estándar de *Proyectos*.
* Incrusta de forma nativa el logotipo `logo-icono.png` en la cabecera.

### 4. Automatización de Google Drive
Almacenamiento persistente ejecutado en segundo plano tras interactuar con un presupuesto en el formulario principal:
1. Se autogenera el PDF.
2. El API de Drive consulta la existencia del directorio cronológico de Presupuestos, creándolo en caso de no existir.
3. El archivo final se inyecta en Drive asociando su hash ID único al modelo de base de datos.
> [!IMPORTANT]
> A diferencia del sistema manual anterior de la API externa que requería una sincronización forzada bajo demanda ('Forzar sincronización'), **cada presupuesto administrado replica el archivo PDF de manera instantánea** en el alojamiento pre-configurado de Google Drive.

### 5. Email Integrado
Implementación de sistema de mensajería asíncrona que despacha una plantilla mailable automática (`PresupuestoPdfMail`). Se vincula directamente desde la vista del documento integrando una redacción del presupuesto junto a su documento PDF como archivo adjunto de forma transparente e inmediata.
