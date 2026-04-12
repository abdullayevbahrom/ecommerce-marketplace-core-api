FROM ghcr.io/abdullayevbahrom/operator-base-php:8.3-fpm-alpine3.20

RUN curl -fsSL https://github.com/aptible/supercronic/releases/latest/download/supercronic-linux-amd64 \
    -o /usr/local/bin/supercronic \
    && chmod +x /usr/local/bin/supercronic

WORKDIR /var/www/html

COPY docker/cron/crontab /etc/crontab
COPY --chown=www-data:www-data ./ ./