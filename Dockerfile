# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# INSTALACIÓN DEL DRIVER PDO PARA MYSQL (SOLUCIONA 'could not find driver')
# -----------------------------------------------------------------------
RUN docker-php-ext-install pdo_mysql
# -----------------------------------------------------------------------

# Copiamos todo el código de su repositorio al directorio del servidor web
COPY . /var/www/html/

# Instalamos las dependencias de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# Habilitamos el módulo de reescritura de Apache (.htaccess)
RUN a2enmod rewrite

# El servidor arranca automáticamente
EXPOSE 80