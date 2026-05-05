FROM php:8.2-apache

# 1. Instalamos librerías de sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# 2. Instalamos extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Habilitamos Apache rewrite
RUN a2enmod rewrite

# 4. Directorio de trabajo
WORKDIR /var/www/html

# 5. COPIAMOS EL PROYECTO (Esto faltaba o estaba en el orden incorrecto)
COPY . /var/www/html

# 6. Copiamos la config de Apache
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# 7. Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 8. AHORA SÍ: Damos permisos (ya que los archivos existen)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
