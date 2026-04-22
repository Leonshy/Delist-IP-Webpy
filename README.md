# Delist IP — Portal de Autoservicio WebParaguay

Portal web de autoservicio que permite a los clientes de hosting detectar si su IP fue bloqueada por Fail2Ban en un servidor Plesk/Linux y desbloquearse ellos mismos, sin necesidad de contactar a soporte, mediante validación anti-bot con Cloudflare Turnstile.

---

## Características

- Detección automática de la IP real del visitante, incluyendo soporte a proxies inversos y CDN (Cloudflare)
- Integración remota con Fail2Ban en servidor Plesk vía SSH con clave privada
- Desbloqueo seguro sin login: el usuario solo necesita pasar el desafío Turnstile
- Mensajes de motivo amigables en español para todos los jails comunes de Fail2Ban
- Rate limiting por IP para prevenir abuso
- Cooldown configurable entre desbloqueos consecutivos
- Control de jails permitidas vs. críticas (SSH, recidive no son desbloqueables)
- Registro completo de auditoría en base de datos
- Secciones de contacto a soporte (WhatsApp y correo) con mensajes pre-completados
- Diseño responsivo con identidad de marca WebParaguay (Montserrat/Karla, dos columnas)

---

## Arquitectura

El portal corre en un servidor independiente (servidor A) y se comunica via SSH con el servidor Plesk/Fail2Ban (servidor B). El servidor A nunca ejecuta comandos Plesk localmente.

```
Cliente → Cloudflare CDN → Servidor A (Laravel) → SSH → Servidor B (Plesk + Fail2Ban)
```

### Flujo de desbloqueo

1. El cliente visita el portal; se detecta su IP real (considerando headers CF-Connecting-IP y X-Forwarded-For)
2. Se consulta al servidor Plesk via SSH si esa IP está baneada y en qué jail
3. Si está bloqueada y el jail es permitido, se muestra el widget Turnstile
4. El usuario completa el desafío; el token se valida en el backend contra la API de Cloudflare
5. Si es válido, se ejecuta el desbloqueo en Fail2Ban via SSH
6. Se registra el evento en la base de datos

---

## Requisitos

- PHP 8.2 o superior
- Laravel 12
- SQLite (por defecto) o MySQL/PostgreSQL
- Cuenta gratuita en Cloudflare (para Turnstile)
- Acceso SSH con clave privada al servidor Plesk/Fail2Ban remoto

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Leonshy/Delist-IP-Webpy.git
cd Delist-IP-Webpy
```

### 2. Instalar dependencias

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores reales (ver sección de configuración más abajo).

### 4. Base de datos

```bash
touch database/database.sqlite
php artisan migrate
```

### 5. Permisos

```bash
chmod -R 775 storage bootstrap/cache database
chmod -R 755 public
```

---

## Configuración

Todas las variables sensibles van en `.env`. Nunca se commitean al repositorio.

```env
APP_NAME="IP Unblock Service"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=sqlite
DB_DATABASE=/ruta/absoluta/database/database.sqlite

# Cloudflare Turnstile — obtener en dash.cloudflare.com > Turnstile
TURNSTILE_SITE_KEY=tu_site_key
TURNSTILE_SECRET_KEY=tu_secret_key

# Proxies de confianza — rangos CIDR de Cloudflare si usás su CDN
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22

# Conexión SSH al servidor Plesk remoto
PLESK_SSH_HOST=ip.del.servidor.plesk
PLESK_SSH_PORT=22
PLESK_SSH_USER=root
PLESK_SSH_KEY=/ruta/absoluta/a/tu/clave_ssh

# Jails de Fail2Ban habilitadas para autodesbloqueo
# No incluir ssh, recidive ni jails críticas
ALLOWED_JAILS=plesk-panel,plesk-one-week-ban,dovecot,postfix

# Rate limiting
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15

# Cooldown entre desbloqueos (minutos)
UNBLOCK_COOLDOWN_MINUTES=10
```

---

## Configuración SSH (servidor Plesk)

En el servidor Plesk, agregar la clave pública del servidor A con restricción de comandos en `~root/.ssh/authorized_keys`:

```
command="/usr/local/psa/bin/ip_ban $SSH_ORIGINAL_COMMAND",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ssh-rsa AAAAB3... tu_clave_publica
```

Esto garantiza que la clave SSH solo pueda ejecutar el comando `ip_ban`, sin acceso shell completo.

Pre-registrar el host conocido para el proceso PHP-FPM:

```bash
ssh-keyscan -p PUERTO IP_SERVIDOR >> /ruta/a/.ssh/known_hosts
```

---

## Jails soportadas

El portal incluye mensajes amigables en español para los jails más comunes:

| Jail | Mensaje mostrado |
|------|-----------------|
| plesk-panel | Demasiados intentos fallidos de acceso al panel |
| plesk-dovecot | Demasiados intentos fallidos de acceso al correo entrante |
| plesk-postfix | Demasiados intentos fallidos de autenticación de correo saliente |
| plesk-one-week-ban | Demasiados intentos fallidos de autenticación |
| plesk-wordpress | Demasiados intentos fallidos de acceso a WordPress |
| plesk-modsecurity | Acceso bloqueado por el WAF |
| dovecot | Demasiados intentos fallidos de correo entrante |
| postfix / postfix-sasl | Demasiados intentos fallidos de correo saliente |
| sshd / ssh | Demasiados intentos fallidos de acceso SSH |
| recidive | Bloqueado por repetición de eventos sospechosos |
| nginx-* / apache-* | Varios — ver `IpController.php` |

Para agregar jails personalizados, editar la constante `JAIL_FRIENDLY_NAMES` en [app/Http/Controllers/IpController.php](app/Http/Controllers/IpController.php).

---

## API

### `GET /api/ip/status`

Devuelve el estado de la IP del visitante.

```json
{
    "ip": "203.0.113.45",
    "blocked": true,
    "jail": "plesk-panel",
    "friendly_reason": "Demasiados intentos fallidos de acceso al panel de administración",
    "eligible_for_self_unblock": true,
    "turnstile_site_key": "0x..."
}
```

### `POST /api/ip/unblock`

Solicita el desbloqueo de la IP del visitante.

**Body:**
```json
{ "turnstile_token": "token_del_widget" }
```

**Respuesta exitosa:**
```json
{ "success": true, "message": "Tu IP ha sido desbloqueada exitosamente." }
```

Ambos endpoints tienen throttle de 30 requests/minuto por IP.

---

## Auditoría

Todos los intentos se registran en la tabla `ip_unblock_logs`:

| Campo | Descripción |
|-------|-------------|
| `ip` | IP que realizó la solicitud |
| `jail` | Jail de Fail2Ban involucrado |
| `was_blocked` | Si la IP estaba realmente bloqueada |
| `turnstile_valid` | Si el token Turnstile fue válido |
| `unblocked` | Si el desbloqueo fue exitoso |
| `reason` | Motivo del resultado |
| `created_at` | Timestamp del evento |

---

## Seguridad

- El `.env` nunca se commitea al repositorio
- La clave SSH privada nunca se almacena dentro del directorio público
- El acceso SSH al servidor remoto está restringido a un solo comando (`ip_ban`)
- La validación de Turnstile se realiza exclusivamente en el backend
- Las IPs se validan con `filter_var(FILTER_VALIDATE_IP)` antes de cualquier operación
- Los argumentos SSH se escapan con `escapeshellarg()`
- Jails críticas (`ssh`, `recidive`) no pueden desbloquearse desde el portal
- Rate limiting: máximo 5 intentos cada 15 minutos por IP
- Cooldown configurable entre desbloqueos

---

## Licencia

MIT
