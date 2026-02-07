# 🛡️ Documentación del Módulo VPN y Guía de Despliegue

## Descripción General
El **Módulo VPN de Labs Backend** permite a los administradores gestionar el acceso seguro a la red para los empleados directamente desde el panel de control. Se integra con **WireGuard** para la creación automática de claves y direcciones IP, vinculándolas a las cuentas de usuario.

**Capacidades principales:**
- **Auto-aprovisionamiento**: Genera claves privadas/públicas y asigna IPs (rango 10.0.0.x).
- **Revocación Instantánea**: Al eliminar un dispositivo se corta inmediatamente el túnel activo.
- **Configuración mediante Código QR**: Los usuarios pueden escanear un código QR para conectarse al instante.
- **Persistencia en el Arranque**: Restaura automáticamente el acceso tras los reinicios del servidor.

---

## 🏗️ Requisitos Previos del Servidor

Para desplegar este módulo en un nuevo servidor (ej. Ubuntu 20.04/22.04), sigue estos pasos:

### 1. Instalar WireGuard
```bash
sudo apt update
sudo apt install wireguard qrencode -y
```

### 2. Generar Claves del Servidor
```bash
cd /etc/wireguard
umask 077
wg genkey | tee privatekey | wg pubkey > publickey
```

### 3. Configurar la Interfaz del Servidor (`/etc/wireguard/wg0.conf`)
Crea el archivo y añade la siguiente configuración. Reemplaza `<SERVER_PRIVATE_KEY>` con el contenido de `/etc/wireguard/privatekey`.

```ini
[Interface]
Address = 10.0.0.1/24
SaveConfig = false
ListenPort = 51892
PrivateKey = <SERVER_PRIVATE_KEY>

# Enrutamiento de IP y Mascarada (NAT)
PostUp = sysctl -w net.ipv4.ip_forward=1
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o ens6 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o ens6 -j MASQUERADE
```
*Nota: Asegúrate de que el nombre de la interfaz de red principal sea el correcto (ej. `ens6`, `eth0`). Compruébalo con `ip addr`.*

### 4. Habilitar e Iniciar el Servicio WireGuard
```bash
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0
```

---

## 🔐 Permisos y Sudoers

La aplicación Laravel necesita privilegios simplificados para gestionar los pares (peers) activos de WireGuard.

### Configurar Sudoers
Crea un nuevo archivo de sudoers:
```bash
sudo visudo -f /etc/sudoers.d/vpn-management
```

Añade lo siguiente (reemplaza `username` por el usuario del sistema/web):
```bash
# Permitir al usuario web gestionar WireGuard
username ALL=(root) NOPASSWD: /usr/bin/wg
```

---

## ⚙️ Configuración de la Aplicación

### Variables de Entorno (`.env`)
```env
VPN_ENDPOINT=(IP del servidor):51892
```

### 🔄 Restauración Automática y Persistencia
Para asegurar que los pares se restauren tras un reinicio y que los apretones de manos (handshakes) sean monitorizados, añade lo siguiente a tu crontab (`crontab -e -u username` o a través de Tareas Programadas en Plesk):

```bash
# Programador de Laravel (Cada minuto) - Monitoriza handshakes
* * * * * cd /ruta/a/la/app && php artisan schedule:run >> /dev/null 2>&1

# Restauración al Reiniciar - Sincroniza todos los pares al arrancar
@reboot cd /ruta/a/la/app && php artisan vpn:sync-peers >> /dev/null 2>&1
```

---

## 🔍 Solución de Problemas

### Los clientes conectan pero no tienen internet
1. **DNS**: Asegúrate de que el perfil del cliente utiliza DNS públicos (ej. `DNS = 1.1.1.1, 8.8.8.8`).
2. **NAT**: Verifica que la regla `PostUp` en `wg0.conf` tiene el nombre de interfaz correcto (usa `ip addr`).

### "403 Forbidden" en el Panel
1. Asegúrate de que la IP pública del servidor está incluida en los `AllowedIPs` del cliente (ej. `AllowedIPs = 10.0.0.0/24, IP_SERVIDOR/32`).
2. Comprueba si la configuración de Nginx del dominio permite el rango `10.0.0.0/24`.
