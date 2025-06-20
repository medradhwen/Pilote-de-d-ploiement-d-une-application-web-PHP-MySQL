# Utiliser une image de base officielle PHP avec Apache
FROM php:8.2-apache

# Installer l'extension mysqli pour la connexion à MySQL
# docker-php-ext-install est un script fourni par l'image de base pour faciliter l'installation
RUN docker-php-ext-install mysqli

# Copier le code source de l'application dans le répertoire web racine d'Apache
COPY index.php /var/www/html/

# Exposer le port 80, le port par défaut d'Apache
EXPOSE 80 