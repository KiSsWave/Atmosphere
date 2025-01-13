FROM php:8.2-apache


RUN a2enmod rewrite \
    && apt-get update && apt-get install -y \
    iputils-ping \
    libxml2-dev \
    libxslt-dev \
    && docker-php-ext-install dom xsl

# Copier le projet dans le dossier par défaut d'Apache
COPY . /var/www/html/

# Donner les permissions nécessaires
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 8080
EXPOSE 8080

# Lancer Apache en tant que service principal
CMD ["apache2-foreground"]
