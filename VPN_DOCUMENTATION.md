# 🛡️ VPN Module Documentation & Deployment Guide

## Overview
The **Labs Backend VPN Module** allows administrators to manage secure network access for employees directly from the dashboard. It integrates with **WireGuard** to provision keys and IP addresses automatically, linking them to user accounts.

**Key capabilities:**
- **Auto-provisioning**: Generates private/public keys and allocates IPs (10.0.0.x pool).
- **Instant Revocation**: Removing a device immediately kills the active tunnel.
- **QR Code Configuration**: Users can scan a QR code to connect instantly.
- **Boot Persistence**: Automatically restores access after server reboots.

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
PostUp = iptables -A FORWARD -i wg0 -j ACCEPT; iptables -t nat -A POSTROUTING -o ens6 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; iptables -t nat -D POSTROUTING -o ens6 -j MASQUERADE
```
*Note: Ensure your main network interface is correct (e.g., `ens6`, `eth0`). Check with `ip addr`.*

### 4. Enable and Start WireGuard Service
```bash
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0
```

---

## 🔐 Permissions & Sudoers

The Laravel application needs simplified privileges to manage WireGuard active peers.

### Configure Sudoers
Create a new sudoers file:
```bash
sudo visudo -f /etc/sudoers.d/vpn-management
```

Add the following (replace `username` with the web/system user):
```bash
# Allow web user to manage WireGuard
username ALL=(root) NOPASSWD: /usr/bin/wg
```

---

## ⚙️ Application Configuration

### Environment Variables (`.env`)
```env
VPN_ENDPOINT=(IP del servidor):51892
```

### 🔄 Automatic Restoration & Persistence
To ensure that peers are restored after a reboot and handshakes are tracked, add the following to your crontab (`crontab -e -u username` or via Plesk Scheduled Tasks):

```bash
# Laravel Scheduler (Every minute) - Tracks handshakes
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1

# Reboot Restoration - Restores all peers on boot
@reboot cd /path/to/app && php artisan vpn:sync-peers >> /dev/null 2>&1
```

---

## 🔍 Troubleshooting

### Clients can connect but no internet
1. **DNS**: Ensure the client profile uses public DNS (e.g., `DNS = 1.1.1.1, 8.8.8.8`).
2. **NAT**: Verify the `PostUp` rule in `wg0.conf` has the correct interface name (use `ip addr`).

### "403 Forbidden" on the Dashboard
1. Ensure the server's public IP is included in the client's `AllowedIPs` (e.g., `AllowedIPs = 10.0.0.0/24, SERVER_IP/32`).
2. Check if the domain's Nginx configuration allows the `10.0.0.0/24` range.
