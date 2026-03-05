# Usamos la imagen oficial de PHP con Apache (igual que XAMPP)
FROM php:8.2-apache

# Instalamos las extensiones necesarias para MySQL (Aiven)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copiamos los archivos de SIGED al servidor
COPY . /var/www/html/

# Le decimos a Apache que escuche en el puerto que Render asigne
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
