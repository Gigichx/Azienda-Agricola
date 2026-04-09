# Usa l'immagine base PHP con Apache
FROM php:8.2-apache

# Installa l'estensione mysqli (necessaria per connettersi a MySQL)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# (Opzionale) Installa anche PDO per compatibilità futura
RUN docker-php-ext-install pdo pdo_mysql

# Copia il codice PHP nel container
COPY ./ /var/www/html

# Imposta la directory di lavoro
WORKDIR /var/www/html
