# Documentación de Seguridad y Personalización de Errores

Este documento detalla la configuración de seguridad implementada para restringir el acceso a la plataforma mediante VPN y la personalización de la página de error 403 (Prohibido).

## 1. Arquitectura de Seguridad (Zero Trust)

El acceso a esta aplicación está restringido a nivel de servidor (Nginx). Se utiliza una política de denegación predeterminada (`deny all`), permitiendo únicamente el tráfico originado desde la red privada de **WireGuard**.

### Beneficios:
- **Blindaje Total:** El servidor rechaza las peticiones antes de que lleguen a la aplicación PHP/Laravel, evitando posibles ataques a vulnerabilidades de código.
- **Acceso Privado:** Solo los dispositivos con una llave válida de WireGuard y configurados en el rango de IP `10.0.0.0/24` pueden visualizar el contenido.

## 2. Configuración de Nginx (Plesk)

Para que el sistema funcione correctamente con una página de error estética, la configuración en las "Directivas adicionales de nginx" debe seguir este esquema:

```nginx
# 1. Definición de la página de error personalizada
error_page 403 /error_docs/forbidden.html;

# 2. Excepción para recursos estáticos del error (Logo, HTML)
# El uso de 'alias' garantiza que Nginx encuentre los archivos fuera de la restricción
location /error_docs/ {
    alias /var/www/vhosts/TU_DOMINIO/error_docs/;
    allow all;
    access_log off;
}

# 3. Restricción de acceso VPN
allow 10.0.0.0/24;
allow 127.0.0.1;
deny all;
```

## 3. Personalización de la Página 403

La página de error se encuentra en `/error_docs/forbidden.html`. Se ha diseñado para que sea coherente visualmente con la aplicación principal.

### Detalles Técnicos:
- **Colores (Paleta Zinc):**
  - Fondo: `#09090b` (zinc-950)
  - Diálogo: `#18181b` (zinc-900)
  - Bordes: `#27272a` (zinc-800)
- **Tipografía:** Se utiliza la fuente **Lato** cargada vía Google Fonts.
- **Logotipo:** Se utiliza el archivo `logo.png` original. Para adaptarlo al modo oscuro sin crear un nuevo archivo, se aplica un filtro CSS:
  ```css
  filter: brightness(0) invert(1); /* Convierte el logo a blanco puro */
  ```

## 4. Mantenimiento

Si en el futuro se desea cambiar el mensaje o el logo:
1.  **Logo:** Reemplazar el archivo en `/var/www/vhosts/TU_DOMINIO/error_docs/logo.png`.
2.  **Texto/Diseño:** Editar el archivo `/var/www/vhosts/TU_DOMINIO/error_docs/forbidden.html`.
3.  **IPs Permitidas:** Si cambia el rango de la VPN, actualizar la directiva `allow` en el panel de Plesk.

---
*Configuración implementada el 21 de Febrero de 2026.*
