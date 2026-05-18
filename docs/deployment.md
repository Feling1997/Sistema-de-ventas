# Guía de Despliegue

## Entorno de Desarrollo

### Requisitos
- PHP 8.0+
- MySQL 8.0+
- Composer
- Git

### Instalación Local
1. Clonar repositorio
2. Instalar dependencias: `composer install`
3. Crear BD y ejecutar script SQL
4. Configurar `configuraciones/base_datos.php`
5. Apuntar servidor web a `publico/`

## Despliegue en Producción

### Servidor Requerido
- Apache/Nginx con PHP-FPM
- MySQL/MariaDB
- SSL certificate (Let's Encrypt recomendado)

### Pasos de Despliegue

#### 1. Preparación del Servidor
```bash
# Instalar PHP y extensiones
sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar MySQL
sudo apt install mysql-server
sudo mysql_secure_installation
```

#### 2. Configuración de la Base de Datos
```sql
CREATE DATABASE sistema_ventas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Ejecutar el script de creación de tablas
```

#### 3. Despliegue de la Aplicación
```bash
# Clonar código
git clone <repository-url> /var/www/ventas
cd /var/www/ventas

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Configurar permisos
sudo chown -R www-data:www-data /var/www/ventas
sudo chmod -R 755 /var/www/ventas
sudo chmod -R 777 /var/www/ventas/almacenamiento
```

#### 4. Configuración de Apache
```apache
<VirtualHost *:80>
    ServerName ventas.ejemplo.com
    DocumentRoot /var/www/ventas/publico

    <Directory /var/www/ventas/publico>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ventas_error.log
    CustomLog ${APACHE_LOG_DIR}/ventas_access.log combined
</VirtualHost>
```

#### 5. Configuración SSL (Opcional pero recomendado)
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d ventas.ejemplo.com
```

#### 6. Configuración de PHP
```ini
# php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

### Variables de Entorno
Crear archivo `.env` en la raíz del proyecto:
```
DB_HOST=localhost
DB_NAME=sistema_ventas
DB_USER=ventas_user
DB_PASS=secure_password
APP_ENV=production
APP_KEY=your_app_key_here
```

### Optimizaciones de Producción

#### 1. Caché de Composer
```bash
composer install --no-dev --optimize-autoloader
```

#### 2. Configuración de OPcache
```ini
# php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=7963
opcache.revalidate_freq=0
```

#### 3. Configuración de MySQL
```ini
# my.cnf
[mysqld]
innodb_buffer_pool_size=1G
innodb_log_file_size=256M
query_cache_size=256M
max_connections=200
```

### Monitoreo y Logs

#### Logs de Aplicación
- Logs de error: `almacenamiento/logs/`
- Logs de acceso: Configurar en servidor web

#### Monitoreo
- Configurar logrotate para logs
- Monitorear uso de CPU/memoria
- Configurar backups automáticos de BD

### Backup y Recuperación

#### Backup de Base de Datos
```bash
mysqldump -u usuario -p sistema_ventas > backup_$(date +%Y%m%d).sql
```

#### Backup de Archivos
```bash
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/ventas
```

#### Restauración
```bash
mysql -u usuario -p sistema_ventas < backup.sql
tar -xzf backup_files.tar.gz -C /var/www/
```

### Seguridad

#### Configuraciones Básicas
- Cambiar credenciales por defecto
- Usar HTTPS
- Configurar firewall (ufw/iptables)
- Actualizar sistema regularmente

#### Configuraciones Avanzadas
- Configurar fail2ban
- Usar SELinux/AppArmor
- Configurar rate limiting
- Implementar WAF (ModSecurity)

### Escalabilidad

#### Balanceo de Carga
- Usar Nginx como load balancer
- Configurar sesiones en Redis
- Implementar cache (Redis/Memcached)

#### CDN
- Servir assets estáticos desde CDN
- Configurar headers de cache apropiados

### Troubleshooting

#### Problemas Comunes
1. **Error de conexión BD**: Verificar credenciales y permisos
2. **Archivos no se suben**: Verificar permisos en `almacenamiento/`
3. **Página en blanco**: Verificar logs de PHP y Apache
4. **Error 500**: Verificar sintaxis PHP y dependencias

#### Comandos Útiles
```bash
# Ver logs de error
tail -f /var/log/apache2/ventas_error.log

# Ver procesos PHP
ps aux | grep php

# Verificar sintaxis PHP
php -l aplicacion/controladores/ControladorAuth.php

# Reiniciar servicios
sudo systemctl restart apache2
sudo systemctl restart mysql
```