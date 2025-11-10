# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# -----------------------------------------------------------------------
# CAMBIO CRÍTICO: Instalar librerías y herramientas de desarrollo de PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    rm -rf /var/lib/apt/lists/*
# -----------------------------------------------------------------------

# INSTALACIÓN DEL DRIVER PDO PARA POSTGRESQL (SOLUCIONA 'could not find driver')
# -----------------------------------------------------------------------
RUN docker-php-ext-install pdo_pgsql
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