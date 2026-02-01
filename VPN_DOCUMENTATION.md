# VPN Module Documentation

## Overview
The VPN module allows administrators to create and manage WireGuard VPN entries for users directly from the administrative panel. It connects `users` with `vpn_devices` and interfaces with the system's WireGuard service.

## Requirements
- **OS**: Linux (tested on Ubuntu)
- **Software**: WireGuard (`wg` tools installed)
- **Permissions**: The web user must have sudo privileges to run specific wireguard commands or use a sudo wrapper.

## Configuration

### Environment Variables
Add the following to your `.env` file:
```env
VPN_ENDPOINT=213.165.65.4:51892
```
This defines the endpoint public IP and port used in client configurations.

### Database
The module uses:
- `vpn_devices` table: Stores public keys, allocated IPs, and user relationships.
- `vpn_access_logs` table: Stores audit logs for creation, deletion, and connection attempts (if enabled).

### IP Allocation
- The internal network uses `10.0.0.0/24`.
- Server IP is assumed to be `10.0.0.1`.
- Client IPs start from `10.0.0.2`.
- The system automatically reclaims IPs from soft-deleted devices to avoid exhaustion.

## Usage

### Creating a Device
1. Go to **Admin > Users**.
2. Select a user and click "Grant Network Access" (or similar).
3. A key pair is generated:
   - **Private Key**: Embedded in the generated configuration (QR Code/Download). **Not stored in DB**.
   - **Public Key**: Stored in `vpn_devices`.
4. The peer is added to the running WireGuard interface immediately.

### Revoking Access
1. Delete the VPN device from the user's profile.
2. The system executes `wg set ... remove` to kill the connection immediately.
3. The device record is soft-deleted.

## Troubleshooting
- **Logs**: Check `storage/logs/laravel.log` for errors prepended with `VPN Error`.
- **System Logs**: Use `journalctl -u wg-quick@wg0` to see WireGuard backend logs.
