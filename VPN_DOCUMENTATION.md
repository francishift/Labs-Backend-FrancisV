# 🛡️ VPN Module Documentation & Deployment Guide

## Overview
The **Labs Backend VPN Module** allows administrators to manage secure network access for employees directly from the dashboard. It integrates with **WireGuard** to provision keys and IP addresses automatically, linking them to user accounts.

**Key capabilities:**
- **Auto-provisioning**: Generates private/public keys and allocates IPs (10.0.0.x pool).
- **Instant Revocation**: Removing a device immediately kills the active tunnel.
- **QR Code Configuration**: Users can scan a QR code to connect instantly.

---

## 🏗️ Server Prerequisites

To deploy this module on a new server (e.g., Ubuntu 20.04/22.04), follow these steps:

### 1. Install WireGuard
```bash
sudo apt update
sudo apt install wireguard qrencode -y
```

### 2. Generate Server Keys
```bash
cd /etc/wireguard
umask 077
wg genkey | tee privatekey | wg pubkey > publickey
```

### 3. Configure Server Interface (`/etc/wireguard/wg0.conf`)
Create the file and add the following configuration. Replace `<SERVER_PRIVATE_KEY>` with the content of `/etc/wireguard/privatekey`.

```ini
[Interface]
Address = 10.0.0.1/24
SaveConfig = false
ListenPort = 51892
PrivateKey = <SERVER_PRIVATE_KEY>

# IP Forwarding & Masquerading (NAT)
PostUp = sysctl -w net.ipv4.ip_forward=1
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE
```
*Note: Ensure your main network interface is `eth0`. If distinct, adjust accordingly (e.g., `ens3`, `venet0`).*

### 4. Enable and Start WireGuard Service
```bash
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0
```

---

## 🔐 Permissions & Sudoers

The Laravel application runs as the web user (usually `www-data` or `psacln` on Plesk). It needs simplified privileges to manage WireGuard active peers without acting as full root.

### Configure Sudoers
Create a new sudoers file:
```bash
sudo visudo -f /etc/sudoers.d/www-data
```

Add the following lines to allow the web user to run `wg` commands without a password:

```bash
# Allow web user to manage WireGuard
www-data ALL=(root) NOPASSWD: /usr/bin/wg set wg0 peer *
www-data ALL=(root) NOPASSWD: /usr/bin/wg show wg0 public-key
```
*Replace `www-data` with your web server user if different (e.g., `labs` or `nginx`).*

---

## ⚙️ Application Configuration

### Environment Variables (`.env`)
Add the following key to your Laravel `.env` file. This is the **public IP and port** that clients will connect to.
```env
VPN_ENDPOINT=213.165.65.4:51892
```

### Middleware Protection
To restrict specific routes (like `/admin` or internal tools) to VPN users only, use the keys detected by the `VpnIpRestriction` middleware.

**Logic:**
- Allowed IPs: `10.0.0.0/24` (VPN Range), `127.0.0.1` (Localhost).
- Blocked: Everything else.

**Usage in Routes:**
```php
Route::middleware(['auth', 'vpn.restriction'])->group(function () {
    // Protected routes
});
```

---

## 🔍 Troubleshooting

### "Command requires root privileges"
If the logs show permission errors:
1. Verify the user running PHP (`ps aux | grep php`).
2. Check the sudoers syntax (`visudo -c`).
3. Ensure usage of full paths in code (`/usr/bin/wg`).

### Clients can connect but no internet
1. Check if IP forwarding is enabled: `sysctl net.ipv4.ip_forward`.
2. check iptables rules: `iptables -t nat -L -v`.

### Logo/Assets returning 403 Forbidden
If internal assets are blocked by the VPN middleware, ensure your **Public** folder is white-listed or use absolute URLs pointing to files not served through the Laravel pipeline (e.g., direct Nginx static file serving).
