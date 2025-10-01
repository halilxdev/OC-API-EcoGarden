FROM php:8.4-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libicu-dev \
    libzip-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP requises pour Symfony 7
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    opcache

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration du répertoire de travail
WORKDIR /var/www/symfony

# Configuration des permissions
RUN useradd -m symfony && chown -R symfony:symfony /var/www/symfony
USER symfony

# Port exposé pour PHP-FPM
EXPOSE 9000