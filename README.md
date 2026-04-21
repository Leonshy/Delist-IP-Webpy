# IP Unblock Service - Portal de Autoservicio para Clientes Plesk

Un portal de autoservicio web completo que permite a clientes de hosting en Plesk/Linux desbloquear automáticamente sus direcciones IP que han sido bloqueadas por Fail2Ban, sin requerir login ni acceso administrativo.

## 🎯 Características

- ✅ **Detección automática de IP del visitante**: Obtiene la IP real considerando proxies inversos
- ✅ **Integración con Plesk Fail2Ban**: Consulta y desbloquea IPs usando CLI oficial `plesk bin ip_ban`
- ✅ **Sin login requerido**: Portal 100% autoservicio accesible públicamente
- ✅ **Validación anti-bot**: Integración con Cloudflare Turnstile (validación server-side)
- ✅ **Motivos amigables**: Mapeo de jails de Fail2Ban a mensajes entendibles en español
- ✅ **Seguridad multinivel**:
  - Rate limiting por IP
  - Cooldown entre desbloqueos
  - Control de jails permitidas vs. críticas
  - Auditoría completa de eventos
- ✅ **Restricciones configurables**: Define qué jails pueden desbloquearse automáticamente
- ✅ **Logs y auditoría**: Base de datos con todos los intentos y resultados

## 📋 Requisitos

- **PHP**: >= 8.2
- **Laravel**: 12.0+
- **Base de Datos**: SQLite (por defecto), MySQL / PostgreSQL (configurable)
- **Plesk**: Versión Linux con Fail2Ban integrado
- **Cloudflare Turnstile**: Cuenta gratuita de Cloudflare
- **Node.js & npm**: Para compilar assets (opcional si usan pre-compilados)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO> delist-ip-webpy
cd delist-ip-webpy
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Configurar el archivo `.env`

```bash
cp .env.example .env
```

Edita `.env` con tus valores:

```env
APP_URL=https://tu-dominio.com
APP_ENV=production

# Base de datos
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Cloudflare Turnstile (obtén en https://dash.cloudflare.com/)
TURNSTILE_SITE_KEY=tu_site_key_de_turnstile
TURNSTILE_SECRET_KEY=tu_secret_key_de_turnstile

# Si estás detrás de un proxy inverso (Nginx, Apache, Cloudflare, etc.)
# Lista de IPs de proxy confiables
TRUSTED_PROXIES=10.0.0.1,10.0.0.2

# Jails permitidas para desbloqueo automático
ALLOWED_JAILS=plesk-panel,dovecot,postfix

# Seguridad y throttling
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15
UNBLOCK_COOLDOWN_MINUTES=60
```

### 4. Generar clave de aplicación

```bash
php artisan key:generate
```

### 5. Crear y migrar la base de datos

```bash
touch database/database.sqlite
php artisan migrate
```

### 6. Compilar assets (si en desarrollo)

```bash
npm run build    # Producción
npm run dev      # Desarrollo
```

### 7. Configurar permisos

```bash
chmod -R 775 storage bootstrap/cache database
chmod -R 755 public
```

## 📦 Despliegue

### Opción A: Apache

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/delist-ip/public

    <Directory /var/www/delist-ip>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/delist-ip-error.log
    CustomLog ${APACHE_LOG_DIR}/delist-ip-access.log combined
</VirtualHost>
```

Habilita mod_rewrite:
```bash
a2enmod rewrite
systemctl restart apache2
```

### Opción B: Nginx

```nginx
server {
    listen 80;
    server_name tu-dominio.com;

    root /var/www/delist-ip/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    error_log /var/log/nginx/delist-ip-error.log;
    access_log /var/log/nginx/delist-ip-access.log;
}
```

Reinicia Nginx:
```bash
systemctl restart nginx
```

### Opción C: Docker (Recomendado)

Crear archivo `Dockerfile`:

```dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    composer \
    git \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 775 storage bootstrap/cache database

EXPOSE 9000

CMD ["php-fpm"]
```

Docker Compose:
```yaml
version: '3.8'
services:
  app:
    build: .
    volumes:
      - .:/app
    ports:
      - "9000:9000"
  
  nginx:
    image: nginx:latest
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - .:/app
    depends_on:
      - app
```

Iniciar:
```bash
docker-compose up -d
```

## 🔧 Configuración Avanzada

### Usar una base de datos MySQL/PostgreSQL en Plesk

Modifica `.env`:

```env
# MySQL
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=delist_ip
DB_USERNAME=root
DB_PASSWORD=tu_password

# O PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=delist_ip
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

Luego migra:
```bash
php artisan migrate
```

### Jails personalizadas

El mapeo de jails a mensajes amigables está definido en [app/Http/Controllers/IpController.php](app/Http/Controllers/IpController.php#L16-L22).

Añade más jails:

```php
private const JAIL_FRIENDLY_NAMES = [
    'plesk-panel' => 'Demasiados intentos fallidos de acceso al panel de administración',
    'dovecot' => 'Demasiados intentos fallidos de acceso al correo entrante',
    'postfix' => 'Demasiados intentos fallidos de autenticación de correo saliente',
    'ssh' => 'Demasiados intentos fallidos de acceso SSH',
    'recidive' => 'La IP fue bloqueada nuevamente por repetición de eventos sospechosos',
    'tu-custom-jail' => 'Tu mensaje personalizado aquí',
];
```

### Proxies inversos configurables

Si tu aplicación está detrás de un proxy inverso (Cloudflare, AWS ALB, etc.), configura:

```env
# Ejemplo: Si Cloudflare es tu proxy
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22

# O si usas AWS
TRUSTED_PROXIES=10.0.0.0/8

# O si confías en todos (¡NO RECOMENDADO EN PRODUCCIÓN!)
TRUSTED_PROXIES=*
```

## 🔐 Recomendaciones de Hardening

### 1. **HTTPS obligatorio**

Usa Let's Encrypt:

```bash
certbot certonly --standalone -d tu-dominio.com
```

Redirige HTTP a HTTPS en `config/app.php`:

```php
'url' => env('APP_URL', 'https://tu-dominio.com'),
'force_https' => env('APP_ENV') === 'production',
```

O en Nginx:
```nginx
if ($scheme != "https") {
    return 301 https://$server_name$request_uri;
}
```

### 2. **CSRF Protection**

El formulario de desbloqueo valida token CSRF automáticamente. Asegúrate de que las headers estén correctas en tu frontend.

### 3. **Limitar acceso a rutas administrativas**

Si necesitas logs administrativos, protégelos con IP whitelist:

```php
Route::middleware(['ip.whitelist'])->group(function () {
    Route::get('/admin/logs', [...]);
});
```

### 4. **Rate Limiting**

Ya está implementado en el controlador. Configura en `.env`:

```env
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_DECAY_MINUTES=15
UNBLOCK_COOLDOWN_MINUTES=60
```

### 5. **Validación Turnstile obligatoria en backend**

La validación se realiza en servidor (`validateTurnstile` en [IpController.php](app/Http/Controllers/IpController.php#L300)). NUNCA confíes en validación client-side.

### 6. **Logs y monitoreo**

Los logs se almacenan en `storage/logs/laravel.log`. Configura rotación:

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

Para monitoreo en tiempo real:
```bash
tail -f storage/logs/laravel.log
```

### 7. **Jails críticas NO desbloqueables**

Por defecto, `ssh` y `recidive` NO pueden desbloquearse automáticamente. Modifica ALLOWED_JAILS solo si es necesario.

```env
# SEGURO
ALLOWED_JAILS=plesk-panel,dovecot,postfix

# RIESGOSO
ALLOWED_JAILS=plesk-panel,dovecot,postfix,ssh
```

### 8. **Permisos del sistema de archivos**

```bash
# Data permanente
chmod 775 storage bootstrap/cache database

# Solo lectura para público
chmod 755 public

# Logs solo para aplicación
chmod 600 storage/logs
```

### 9. **Headers de seguridad**

En `app/Http/Middleware/SetSecurityHeaders.php`:

```php
class SetSecurityHeaders
{
    public function handle($request, $next)
    {
        $response = $next($request);
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        return $response;
    }
}
```

Registra en `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \App\Http\Middleware\SetSecurityHeaders::class,
];
```

## 📊 API Reference

### GET `/api/ip/status`

Obtiene el estado de la IP actual.

**Response:**

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

### POST `/api/ip/unblock`

Desbloquea la IP actual si es elegible.

**Request:**

```json
{
    "turnstile_token": "0x4AAAAA..."
}
```

**Response (success):**

```json
{
    "success": true,
    "message": "Tu IP ha sido desbloqueada exitosamente. Intenta acceder nuevamente a tus servicios."
}
```

**Response (error):**

```json
{
    "success": false,
    "message": "Tu IP está bloqueada por una razón que requiere contactar a soporte.",
    "blocked_by": "Motivo del bloqueo"
}
```

## 📝 Logs y Auditoría

Todos los intentos se registran en la tabla `ip_unblock_logs`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `ip` | string | IP que intentó desbloquear |
| `jail` | string | Jail de Fail2Ban (si aplica) |
| `was_blocked` | boolean | Si la IP estaba realmente bloqueada |
| `turnstile_valid` | boolean | Si Turnstile fue válido |
| `unblocked` | boolean | Si el desbloqueo fue exitoso |
| `reason` | string | Motivo (success, rate_limit, etc.) |
| `created_at` | datetime | Timestamp del evento |

**Consultar logs:**

```bash
# Firebase de Laravel
php artisan tinker

DB\Table('ip_unblock_logs')->latest()->get();
DB\Table('ip_unblock_logs')->where('unblocked', true)->count();
```

O exportar a CSV:

```php
// routes/web.php
Route::get('/admin/logs-export', function () {
    $logs = \App\IpUnblockLog::all();
    return response()->csv($logs->toArray(), 'logs.csv');
})->middleware('auth.admin');
```

## 🐛 Troubleshooting

### "Plesk command not found"

Asegúrate de que:
1. `plesk bin` está en el PATH del usuario que ejecuta PHP
2. En Plesk, ejecuta manualmente: `/usr/local/psa/bin/ip_ban --banned`

Añade la ruta completa en [PleskService.php](app/Services/PleskService.php):

```php
$command = '/usr/local/psa/bin/ip_ban --banned';
```

### "Database permission denied"

```bash
chmod 755 database/
chmod 666 database/database.sqlite
```

### "Turnstile validation always fails"

1. Verifica que `TURNSTILE_SECRET_KEY` es correcto
2. Comprueba que la IP del servidor puede alcanzar `challenges.cloudflare.com`
3. Revisa los logs: `tail -f storage/logs/laravel.log`

### "IP no está detectada correctamente"

Si estás detrás de un proxy, configura:

```env
# Obtén la IP real del header correcto
TRUSTED_PROXIES=127.0.0.1,10.0.0.1
```

Si Cloudflare está activado:
```env
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.22.201.0/24,103.31.4.0/22,104.16.0.0/12,108.162.192.0/18,131.0.72.0/22,141.101.64.0/18,162.158.0.0/15,172.64.0.0/13,173.245.48.0/20,103.21.244.0/22,103.22.200.0/22
```

## 📚 Referencias Externas

- **Cloudflare Turnstile - Get Started**: https://developers.cloudflare.com/turnstile/get-started/
- **Turnstile - Server-side Validation**: https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
- **Turnstile - Client-side Rendering**: https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/
- **Plesk IP Ban CLI**: https://docs.plesk.com/en-US/obsidian/cli-linux/using-command-line-utilities/ip_ban-ip-address-banning-fail2ban.73594/
- **Plesk Fail2Ban Guide**: https://docs.plesk.com/en-US/obsidian/administrator-guide/server-administration/plesk-for-linux-protection-against-brute-force-attacks-fail2ban.73381/
- **Laravel Docs**: https://laravel.com/docs/12.x

## 🤝 Soporte y Contribuciones

Para reportar bugs, sugerir mejoras o contribuir:

1. Fork el repositorio
2. Crea una rama: `git checkout -b feature/tu-feature`
3. Commit: `git commit -am 'Añade tu feature'`
4. Push: `git push origin feature/tu-feature`
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo licencia MIT. Consulta [LICENSE](LICENSE) para más detalles.

## 🎓 Changelog

### v1.0.0 (Inicial)
- ✅ Detección automática de IP con soporte a proxies
- ✅ Integración con Plesk `ip_ban` CLI
- ✅ Validación Cloudflare Turnstile (server-side)
- ✅ Rate limiting y cooldown
- ✅ Auditoría completa
- ✅ UI responsiva y en español
- ✅ Documentación completa

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
