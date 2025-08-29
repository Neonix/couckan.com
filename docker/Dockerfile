FROM php:8.2-cli

# Installer dépendances système + extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libssl-dev openssl \
    && docker-php-ext-install pdo pdo_mysql pcntl posix

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dossier de travail
WORKDIR /var/www/couckan

# Copier le projet
COPY . .

# Installer dépendances PHP (Workerman, GatewayWorker, etc.)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Générer certificats SSL auto-signés (pour HTTPS 443 et WS SSL 7272)
RUN echo "[dn]" > openssl.cnf && \
    echo "CN=localhost" >> openssl.cnf && \
    echo "[req]" >> openssl.cnf && \
    echo "distinguished_name = dn" >> openssl.cnf && \
    echo "[EXT]" >> openssl.cnf && \
    echo "subjectAltName=DNS:localhost" >> openssl.cnf && \
    echo "keyUsage=digitalSignature" >> openssl.cnf && \
    echo "extendedKeyUsage=serverAuth" >> openssl.cnf && \
    \
    openssl req -x509 -out localhost.crt -keyout localhost.key \
        -newkey rsa:4096 -nodes -sha256 \
        -subj "/CN=localhost" \
        -extensions EXT -config openssl.cnf && \
    rm openssl.cnf

# Commande par défaut : démarrer tous les workers en mode daemon
CMD ["php", "start.php", "start"]
