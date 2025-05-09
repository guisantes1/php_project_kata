# Usar imagen oficial de PHP con Apache
FROM php:8.2-apache

# Copiar archivos al contenedor
COPY . /var/www/html/

# Habilitar mod_rewrite y establecer permisos
RUN a2enmod rewrite && chown -R www-data:www-data /var/www/html

# Apache configurado para evitar advertencias
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
