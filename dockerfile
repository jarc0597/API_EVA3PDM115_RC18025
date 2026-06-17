FROM php:8.2-apache
 
# Habilitar mod_rewrite para Slim
RUN a2enmod rewrite
 
# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql
 
# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
 
# Copiar todo el proyecto
COPY . /var/www/html/
 
WORKDIR /var/www/html
 
# Instalar dependencias de PHP (Slim Framework, etc.)
RUN composer install --no-dev --optimize-autoloader
 
# Apuntar Apache a la carpeta public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
 
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/apache2.conf
 
# Permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
 
EXPOSE 80
