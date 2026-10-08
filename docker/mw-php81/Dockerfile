FROM php:8.1-cli@sha256:76e563191d1ade120313a8736df24154d21da5155c0756f147c0b01bd19d9087

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
