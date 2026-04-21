# Guía de Instalación - IP Unblock Service

## Tabla de contenidos

1. [Requisitos previos](#requisitos-previos)
2. [Instalación local de desarrollo](#instalación-local-de-desarrollo)
3. [Instalación en servidor Plesk](#instalación-en-servidor-plesk)
4. [Configuración de Cloudflare Turnstile](#configuración-de-cloudflare-turnstile)
5. [Primeros pasos](#primeros-pasos)
6. [Verificación](#verificación)

## Requisitos previos

### Software requerido

```bash
# Verificar versiones instaladas
php --version           # Debe ser 8.2+
composer --version      # Última versión
node --version          # 16.0+
npm --version           # 8.0+
```

### En servidor Linux/Plesk

```bash
# Verificar Plesk CLI availability
/usr/local/psa/bin/ip_ban --help

# Verificar Fail2Ban
systemctl status fail2ban
fail2ban-client status
```

## Instalación local de desarrollo

### Paso 1: Clonar y preparar

```bash
git clone <URL_REPOSITORIO> delist-ip-webpy
cd delist-ip-webpy

# Windows
composer install
npm install

# macOS / Linux
composer install
npm install
```

### Paso 2: Configurar entorno

```bash
cp .env.example .env

# Editar .env con valores para desarrollo
nano .env
```

Cambios mínimos en `.env`:

```env
APP_ENV=local
APP_DEBUG=true
APP_KEY=  # Se generará en el paso siguiente
DB_DATABASE=database/database.sqlite
```

### Paso 3: Generar claves y migrar

```bash
php artisan key:generate
touch database/database.sqlite
chmod 666 database/database.sqlite
php artisan migrate
```

### Paso 4: Iniciar servidor de desarrollo

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Build assets (si usas Vite)
npm run dev

# Terminal 3: Queue listener (opcional, para logs en tiempo real)
php artisan queue:listen
```

Accede a: `http://localhost:8000`

### Paso 5: Verificar funcionamiento

En desarrollo, sin Turnstile configurado el portal dirá "Validación de seguridad fallida", que es normal.

Para testing completo, registra Turnstile en Cloudflare y configura las keys.

## Instalación en servidor Plesk

### Opción A: Clonar en /var/www

```bash
cd /var/www
git clone <URL_REPOSITORIO> delist-ip.example.com
cd delist-ip.example.com

composer install --no-dev --optimize-autoloader
npm install
npm run build

chmod -R 775 storage bootstrap/cache database
chmod -R 755 public
chown -R nobody:nobody .
```

### Opción B: Crear sitio desde Plesk UI

1. **Hosting & Subscriptions** > **Add Subscription**
2. `Nombre del dominio`: `unblock.example.com`
3. `Espacio en disco`: Mínimo 500 MB
4. `Ancho de banda`: Según necesidad
5. `Crear sitio con la misma dirección`

Luego:

```bash
cd /var/www/vhosts/unblock.example.com
git clone <URL_REPOSITORIO> .
# ... mismo proceso que Opción A
```

### Paso 1: Configuración web en Plesk

En el panel **Plesk**, para el dominio:

1. **Sitios web** > Seleccionar dominio
2. **Configuración de PHP**:
   - PHP 8.2+
   - Modo FPM (recomendado)
3. **Modelos de hospedaje**:
   - Nginx + PHP-FPM (recomendado)
   - O Apache + mod_php
4. **SSL/TLS**: Activar Let's Encrypt (obligatorio para producción)

### Paso 2: Configuración .env en Plesk

```bash
# SSH como admin de Plesk o usuario del sitio
ssh usuario@unblock.example.com

# O si no tienes SSH directo, usa sftp para editar
```

Editar `/var/www/vhosts/unblock.example.com/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://unblock.example.com
APP_KEY=  # Si no está presente, ejecuta: php artisan key:generate

# BD
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/vhosts/unblock.example.com/database/database.sqlite

# Turnstile
TURNSTILE_SITE_KEY=tu_site_key
TURNSTILE_SECRET_KEY=tu_secret_key

# Proxies (si Plesk está detrás de CDN)
TRUSTED_PROXIES=CloudflareIPs

# Seguridad
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15
UNBLOCK_COOLDOWN_MINUTES=60

ALLOWED_JAILS=plesk-panel,dovecot,postfix

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Paso 3: Permisos en Plesk

```bash
# Como root en el servidor Plesk
chmod -R 775 /var/www/vhosts/unblock.example.com/storage
chmod -R 775 /var/www/vhosts/unblock.example.com/bootstrap/cache
chmod -R 775 /var/www/vhosts/unblock.example.com/database
chmod 666 /var/www/vhosts/unblock.example.com/database/database.sqlite
```

### Paso 4: Base de datos en Plesk

En panel **Plesk**:

1. **Bases de datos** > **Nueva base de datos**
2. Nombre: `unblock_db`
3. Tipo: SQLite o MySQL
4. Usuario: `unblock_user`

Luego:

```bash
cd /var/www/vhosts/unblock.example.com
php artisan migrate --force
```

### Paso 5: Configurar Plesk CLI access

Para que la aplicación pueda ejecutar `plesk bin ip_ban`:

```bash
# Como root
/usr/local/psa/bin/admin -u <admin_plesk_user> -p <password>

# O configure sudo para el usuario web
echo "apache ALL=(root) NOPASSWD: /usr/local/psa/bin/ip_ban" >> /etc/sudoers
# Y en PleskService.php, usa: sudo /usr/local/psa/bin/ip_ban
```

## Configuración de Cloudflare Turnstile

### 1. Crear cuenta en Cloudflare

1. Accede a: https://dash.cloudflare.com
2. Regístrate o inicia sesión
3. Añade tu dominio (si no lo tienes ya)

### 2. Obtener claves Turnstile

1. En Cloudflare Dashboard: **Security > Turnstile**
2. **Create Site**:
   - `Site name`: `delist-ip` o tu nombre
   - `Domain`: `unblock.example.com`
   - `Mode`: Managed (recomendado para autoservicio)
3. **Create**

Copiarás dos claves:
- **Site Key** (pública, vale exponerla)
- **Secret Key** (privada, NUNCA la expongas)

### 3. Configurar en Laravel

En `.env`:

```env
TURNSTILE_SITE_KEY=tu_site_key_aqui
TURNSTILE_SECRET_KEY=tu_secret_key_aqui
```

**NO EXPONGAS la Secret Key en HTML, JavaScript, etc.**

### 4. Testing de Turnstile

```bash
# En terminal con PHP artisan tinker
php artisan tinker

# Hacer una llamada a la función de validación
$service = app('App\Services\PleskService');
# Probar la lógica manualmente

# O hacer un POST a la API:
curl -X POST http://localhost:8000/api/ip/unblock \
  -H "Content-Type: application/json" \
  -d '{"turnstile_token": "test_token_invalido"}'

# Debe retornar error de validación
```

## Primeros pasos

### 1. Crear primer usuario de testing

Si configuras autenticación después:

```bash
php artisan tinker

# Crear usuario admin
$user = App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
]);

# Crear token API si es necesario
$token = $user->createToken('api-token')->plainTextToken;
```

### 2. Probar endpoints manualmente

```bash
# Obtener estado de IP actual
curl -X GET http://localhost:8000/api/ip/status \
  -H "Accept: application/json"

# Intentar desbloquear (fallará sin token Turnstile válido)
curl -X POST http://localhost:8000/api/ip/unblock \
  -H "Content-Type: application/json" \
  -d '{"turnstile_token": "invalid"}'
```

### 3. Verificar logs

```bash
# Real-time log
tail -f storage/logs/laravel.log

# O vista en UI (si la creas)
php artisan tinker
App\IpUnblockLog::latest()->limit(20)->get();
```

## Verificación

### Checklist final

- [x] PHP version >= 8.2
- [x] Composer dependencies installed
- [x] `.env` properly configured
- [x] `APP_KEY` generated
- [x] Database migrated
- [x] Permissions set correctly (775 storage, 755 public)
- [x] Turnstile keys configured (si producción)
- [x] Plesk CLI accessible from PHP process
- [x] HTTPS enabled (si producción)
- [x] Firewall allows necessary ports (80, 443)
- [x] Rate limiting configured
- [x] Logs monitored

### Comandos útiles

```bash
# Ver estado de la aplicación
php artisan tinker
>>> config('app.env')
>>> config('app.debug')

# Verificar base de datos
sqlite3 database/database.sqlite ".tables"

# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Recompilar assets
npm run build

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ejecutar comandos Plesk manualmente
/usr/local/psa/bin/ip_ban --banned
/usr/local/psa/bin/ip_ban --status 192.168.1.100
```

## Siguientes pasos

1. **Acceso personalizado**: Configura dominios custom, subdominios.
2. **Monitoreo**: Integra con New Relic, Datadog, o similar.
3. **Backups**: Programa backups automáticos de base de datos.
4. **SSL Automático**: Usa `certbot` para renovación automática de certificados.
5. **CDN**: Integra Cloudflare para caché y DDoS protection.

¡Listo! Tu portal IP Unblock Service está funcionando. 🚀