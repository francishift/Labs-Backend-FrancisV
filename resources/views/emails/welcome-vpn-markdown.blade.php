<x-mail::message>
# ¡Hola! Te damos la bienvenida.

Tu cuenta ha sido creada exitosamente. Aquí tienes tus credenciales de acceso a la plataforma:

**Email:** {{ $email }}  
**Contraseña:** {{ $password }}  

Para acceder al sistema de forma segura, primero debes conectarte a nuestra red privada virtual (VPN) utilizando WireGuard.

<hr>
### Instrucciones de Acceso VPN:

**1. Descarga la aplicación WireGuard:**  
[Windows](https://download.wireguard.com/windows-client/wireguard-installer.exe) | [macOS](https://itunes.apple.com/us/app/wireguard/id1451685025) | [iOS](https://itunes.apple.com/us/app/wireguard/id1441195209) | [Android](https://play.google.com/store/apps/details?id=com.wireguard.android)

**2. Añade la configuración (Elige una opción):**

**Opción A (Móviles):** Escanea este código QR desde la app:

<div style="text-align: center; margin: 15px 0;">
    <img src="{{ $message->embedData($qrCode, 'qrcode.png') }}" alt="Código QR WireGuard" style="max-width: 200px; height: auto;" />
</div>

**Opción B (PC/Mac):** Descarga el archivo `vpn-acceso.conf` adjunto a este correo e impórtalo en la aplicación.

<hr>

Una vez conectado a WireGuard, podrás iniciar sesión en la plataforma.

Saludos,<br>
{{ $appName }}
</x-mail::message>
