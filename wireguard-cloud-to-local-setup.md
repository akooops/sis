# WireGuard Cloud-to-Local App Connection Guide

This guide provides step-by-step instructions for connecting a cloud application to a local application using WireGuard VPN.

## Overview

WireGuard is a modern, fast, and secure VPN protocol that creates encrypted tunnels between your cloud infrastructure and local network. It's significantly faster and simpler to configure than traditional VPN solutions.

## Prerequisites

- Root/Administrator access on both cloud and local servers
- WireGuard installed on both ends
- Basic networking knowledge
- Firewall configuration permissions

## Architecture

```
Cloud App (10.0.0.1) <---> WireGuard Server <---> WireGuard Client <---> Local App (10.0.0.2)
```

## Part 1: WireGuard Server Setup (Cloud Side)

### 1. Install WireGuard

#### Ubuntu 20.04+/Debian 11+:
```bash
sudo apt update
sudo apt install wireguard
```

#### CentOS 8+/RHEL 8+:
```bash
sudo dnf install epel-release
sudo dnf install wireguard-tools
```

#### Ubuntu 18.04/Debian 10:
```bash
sudo add-apt-repository ppa:wireguard/wireguard
sudo apt update
sudo apt install wireguard
```

### 2. Generate Server Keys

```bash
# Create WireGuard directory
sudo mkdir -p /etc/wireguard
cd /etc/wireguard

# Generate server private and public keys
wg genkey | sudo tee server_private.key
sudo cat server_private.key | wg pubkey | sudo tee server_public.key

# Set proper permissions
sudo chmod 600 server_private.key
```

### 3. Server Configuration

Create `/etc/wireguard/wg0.conf`:

```ini
[Interface]
# Server private key
PrivateKey = SERVER_PRIVATE_KEY_HERE

# Server IP address in the VPN network
Address = 10.0.0.1/24

# Port to listen on
ListenPort = 51820

# Save configuration on shutdown
SaveConfig = true

# Post-up and post-down scripts for routing
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -A FORWARD -o %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -D FORWARD -o %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Client configuration will be added here automatically
```

### 4. Generate Client Keys

```bash
# Generate client private and public keys
wg genkey | sudo tee client_private.key
sudo cat client_private.key | wg pubkey | sudo tee client_public.key

# Set proper permissions
sudo chmod 600 client_private.key
```

### 5. Add Client to Server Configuration

Edit `/etc/wireguard/wg0.conf` and add:

```ini
[Peer]
# Client public key
PublicKey = CLIENT_PUBLIC_KEY_HERE

# IP addresses allowed for this client
AllowedIPs = 10.0.0.2/32

# Keep connection alive (optional)
PersistentKeepalive = 25
```

### 6. Enable IP Forwarding

```bash
# Enable IP forwarding temporarily
echo 1 | sudo tee /proc/sys/net/ipv4/ip_forward

# Make it permanent
echo 'net.ipv4.ip_forward=1' | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### 7. Configure Firewall

#### UFW (Ubuntu):
```bash
# Allow WireGuard port
sudo ufw allow 51820/udp

# Allow forwarding
sudo ufw route allow in on wg0 out on eth0
sudo ufw route allow in on eth0 out on wg0

# Enable UFW if not already enabled
sudo ufw --force enable
```

#### iptables:
```bash
# Allow WireGuard port
sudo iptables -A INPUT -p udp --dport 51820 -j ACCEPT

# Allow forwarding through WireGuard interface
sudo iptables -A FORWARD -i wg0 -j ACCEPT
sudo iptables -A FORWARD -o wg0 -j ACCEPT

# Enable NAT for VPN subnet
sudo iptables -t nat -A POSTROUTING -s 10.0.0.0/24 -o eth0 -j MASQUERADE

# Save rules
sudo iptables-save | sudo tee /etc/iptables/rules.v4
```

### 8. Start WireGuard Server

```bash
# Enable and start WireGuard
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0

# Check status
sudo systemctl status wg-quick@wg0

# Verify interface is up
sudo wg show
```

## Part 2: WireGuard Client Setup (Local Side)

### 1. Install WireGuard Client

#### Ubuntu/Debian:
```bash
sudo apt update
sudo apt install wireguard
```

#### Windows:
Download WireGuard for Windows from the official website.

#### macOS:
```bash
brew install wireguard-tools
```

Or download from Mac App Store.

### 2. Client Configuration

Create `/etc/wireguard/wg0.conf` (Linux) or `client.conf` (Windows/macOS):

```ini
[Interface]
# Client private key
PrivateKey = CLIENT_PRIVATE_KEY_HERE

# Client IP address in the VPN network
Address = 10.0.0.2/32

# DNS servers (optional)
DNS = 8.8.8.8, 8.8.4.4

[Peer]
# Server public key
PublicKey = SERVER_PUBLIC_KEY_HERE

# Server endpoint (replace with your cloud server IP)
Endpoint = YOUR_CLOUD_SERVER_IP:51820

# Routes to send through the VPN
# 0.0.0.0/0 routes all traffic through VPN
# 10.0.0.0/24 routes only VPN subnet
AllowedIPs = 10.0.0.0/24

# Keep connection alive
PersistentKeepalive = 25
```

### 3. Start WireGuard Client

#### Linux:
```bash
# Start WireGuard
sudo wg-quick up wg0

# Enable auto-start
sudo systemctl enable wg-quick@wg0

# Check status
sudo wg show
```

#### Windows:
- Import the configuration file in WireGuard GUI
- Click "Activate" to connect

#### macOS:
```bash
# Start WireGuard
sudo wg-quick up wg0

# Check status
sudo wg show
```

## Part 3: Application Configuration

### 1. Cloud App Configuration

Update your cloud application to connect to the local app using the WireGuard IP:

```yaml
# Example configuration
database:
  host: 10.0.0.2  # WireGuard IP of local client
  port: 5432
  username: app_user
  password: secure_password
  database: myapp

api:
  local_service_url: http://10.0.0.2:8080/api
  timeout: 30s

redis:
  host: 10.0.0.2
  port: 6379
```

### 2. Local App Configuration

Configure your local application to accept connections from the WireGuard subnet:

```yaml
# Example configuration
server:
  bind_address: 0.0.0.0  # Listen on all interfaces
  port: 8080
  
database:
  bind_address: 0.0.0.0
  port: 5432
  allowed_hosts:
    - 192.168.1.0/24  # Local network
    - 10.0.0.0/24     # WireGuard network

firewall:
  allowed_ips:
    - 10.0.0.1        # WireGuard server IP
```

## Part 4: Advanced Configuration

### 1. Site-to-Site Connection

For connecting entire networks:

#### Server Configuration:
```ini
[Interface]
PrivateKey = SERVER_PRIVATE_KEY_HERE
Address = 10.0.0.1/24
ListenPort = 51820
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

[Peer]
PublicKey = CLIENT_PUBLIC_KEY_HERE
AllowedIPs = 10.0.0.2/32, 192.168.1.0/24
```

#### Client Configuration:
```ini
[Interface]
PrivateKey = CLIENT_PRIVATE_KEY_HERE
Address = 10.0.0.2/32
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

[Peer]
PublicKey = SERVER_PUBLIC_KEY_HERE
Endpoint = YOUR_CLOUD_SERVER_IP:51820
AllowedIPs = 10.0.0.1/32, 10.10.0.0/24
PersistentKeepalive = 25
```

### 2. Multiple Clients

Add additional clients to server configuration:

```ini
[Peer]
PublicKey = CLIENT2_PUBLIC_KEY_HERE
AllowedIPs = 10.0.0.3/32

[Peer]
PublicKey = CLIENT3_PUBLIC_KEY_HERE
AllowedIPs = 10.0.0.4/32
```

### 3. Road Warrior Setup (Mobile Clients)

For mobile or roaming clients that need full internet access:

```ini
[Interface]
PrivateKey = MOBILE_CLIENT_PRIVATE_KEY
Address = 10.0.0.10/32
DNS = 8.8.8.8

[Peer]
PublicKey = SERVER_PUBLIC_KEY_HERE
Endpoint = YOUR_CLOUD_SERVER_IP:51820
AllowedIPs = 0.0.0.0/0  # Route all traffic through VPN
PersistentKeepalive = 25
```

## Part 5: Security Best Practices

### 1. Key Management
```bash
# Generate strong keys
wg genkey | tee private.key | wg pubkey > public.key

# Secure key files
chmod 600 private.key
chmod 644 public.key

# Store keys securely
sudo chown root:root /etc/wireguard/*.key
```

### 2. Network Segmentation
```ini
# Limit client access to specific services
[Peer]
PublicKey = CLIENT_PUBLIC_KEY_HERE
AllowedIPs = 10.0.0.2/32
# Only allow access to specific ports via iptables rules
```

### 3. Monitoring and Logging
```bash
# Monitor connections
sudo wg show

# Enable logging
echo 'module wireguard +p' | sudo tee /sys/kernel/debug/dynamic_debug/control

# View logs
sudo journalctl -u wg-quick@wg0 -f
```

## Part 6: Testing and Verification

### 1. Test VPN Connection

```bash
# Check WireGuard status
sudo wg show

# Test connectivity
ping 10.0.0.1  # From client to server
ping 10.0.0.2  # From server to client

# Test application connectivity
curl http://10.0.0.2:8080/health
telnet 10.0.0.2 5432
```

### 2. Performance Testing

```bash
# Test throughput
iperf3 -s  # On one end
iperf3 -c 10.0.0.1  # On the other end

# Monitor bandwidth usage
sudo iftop -i wg0

# Check latency
ping -c 10 10.0.0.1
```

### 3. Security Verification

```bash
# Verify encryption
sudo tcpdump -i eth0 port 51820

# Check for DNS leaks
nslookup google.com
```

## Part 7: Troubleshooting

### Common Issues

1. **Connection Fails**
   ```bash
   # Check if WireGuard is running
   sudo systemctl status wg-quick@wg0
   
   # Verify firewall rules
   sudo ufw status
   sudo iptables -L
   
   # Check keys
   sudo wg show
   ```

2. **No Internet Access**
   ```bash
   # Verify IP forwarding
   cat /proc/sys/net/ipv4/ip_forward
   
   # Check NAT rules
   sudo iptables -t nat -L
   ```

3. **DNS Issues**
   ```bash
   # Test DNS resolution
   nslookup google.com
   
   # Check DNS configuration
   cat /etc/resolv.conf
   ```

### Log Analysis

```bash
# WireGuard logs
sudo journalctl -u wg-quick@wg0

# System logs
sudo dmesg | grep wireguard

# Network interface logs
ip addr show wg0
```

## Part 8: Management Scripts

### 1. Client Management Script

Create `/usr/local/bin/wg-client-manager.sh`:

```bash
#!/bin/bash

WG_CONFIG="/etc/wireguard/wg0.conf"
CLIENTS_DIR="/etc/wireguard/clients"

add_client() {
    CLIENT_NAME="$1"
    CLIENT_IP="$2"
    
    # Generate client keys
    CLIENT_PRIVATE=$(wg genkey)
    CLIENT_PUBLIC=$(echo "$CLIENT_PRIVATE" | wg pubkey)
    
    # Create client config
    mkdir -p "$CLIENTS_DIR"
    cat > "$CLIENTS_DIR/$CLIENT_NAME.conf" << EOF
[Interface]
PrivateKey = $CLIENT_PRIVATE
Address = $CLIENT_IP/32
DNS = 8.8.8.8

[Peer]
PublicKey = $(sudo cat /etc/wireguard/server_public.key)
Endpoint = $(curl -s ifconfig.me):51820
AllowedIPs = 10.0.0.0/24
PersistentKeepalive = 25
EOF

    # Add peer to server config
    cat >> "$WG_CONFIG" << EOF

[Peer]
PublicKey = $CLIENT_PUBLIC
AllowedIPs = $CLIENT_IP/32
EOF

    # Restart WireGuard
    sudo systemctl restart wg-quick@wg0
    
    echo "Client $CLIENT_NAME added with IP $CLIENT_IP"
    echo "Config file: $CLIENTS_DIR/$CLIENT_NAME.conf"
}

remove_client() {
    CLIENT_NAME="$1"
    CLIENT_PUBLIC=$(grep -A 10 "# $CLIENT_NAME" "$WG_CONFIG" | grep PublicKey | cut -d' ' -f3)
    
    # Remove from server config
    sed -i "/# $CLIENT_NAME/,/^$/d" "$WG_CONFIG"
    
    # Remove client config
    rm -f "$CLIENTS_DIR/$CLIENT_NAME.conf"
    
    # Restart WireGuard
    sudo systemctl restart wg-quick@wg0
    
    echo "Client $CLIENT_NAME removed"
}

case "$1" in
    add)
        add_client "$2" "$3"
        ;;
    remove)
        remove_client "$2"
        ;;
    *)
        echo "Usage: $0 {add|remove} client_name [client_ip]"
        exit 1
        ;;
esac
```

Make it executable:
```bash
sudo chmod +x /usr/local/bin/wg-client-manager.sh
```

### 2. Monitoring Script

Create `/usr/local/bin/wg-monitor.sh`:

```bash
#!/bin/bash

echo "WireGuard Status:"
echo "=================="
sudo wg show

echo -e "\nInterface Status:"
echo "=================="
ip addr show wg0

echo -e "\nConnected Peers:"
echo "=================="
sudo wg show wg0 peers

echo -e "\nTraffic Statistics:"
echo "=================="
sudo wg show wg0 transfer

echo -e "\nFirewall Rules:"
echo "=================="
sudo iptables -L FORWARD | grep wg0
```

## Part 9: Docker Integration

### 1. WireGuard in Docker

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  wireguard:
    image: linuxserver/wireguard
    container_name: wireguard
    cap_add:
      - NET_ADMIN
      - SYS_MODULE
    environment:
      - PUID=1000
      - PGID=1000
      - TZ=UTC
      - SERVERURL=YOUR_CLOUD_SERVER_IP
      - SERVERPORT=51820
      - PEERS=client1,client2
      - PEERDNS=auto
      - INTERNAL_SUBNET=10.0.0.0
    volumes:
      - ./config:/config
      - /lib/modules:/lib/modules
    ports:
      - 51820:51820/udp
    sysctls:
      - net.ipv4.conf.all.src_valid_mark=1
    restart: unless-stopped
```

### 2. Application Behind WireGuard

```yaml
version: '3.8'

services:
  app:
    image: myapp:latest
    container_name: myapp
    environment:
      - DB_HOST=10.0.0.2
      - DB_PORT=5432
    networks:
      - wireguard_network
    depends_on:
      - wireguard

  wireguard:
    # ... wireguard configuration

networks:
  wireguard_network:
    driver: bridge
```

## Part 10: Performance Optimization

### 1. Kernel Module vs Userspace

```bash
# Check if kernel module is loaded
lsmod | grep wireguard

# For better performance, use kernel module
sudo modprobe wireguard
```

### 2. MTU Optimization

```ini
[Interface]
# Optimize MTU for your network
MTU = 1420
```

### 3. CPU Affinity

```bash
# Set CPU affinity for better performance
echo 2 | sudo tee /proc/irq/$(grep wg0 /proc/interrupts | cut -d: -f1)/smp_affinity
```

This comprehensive WireGuard guide provides everything needed to establish secure, high-performance connections between cloud and local applications. WireGuard's simplicity and speed make it an excellent choice for modern VPN deployments.

