FROM ghcr.io/abdullayevbahrom/operator-base-php:8.3-fpm-alpine3.20

WORKDIR /var/www/html

COPY --chown=www-data:www-data ./ ./

RUN mkdir -p /var/www/html/runtime /var/www/html/web/assets \
  && chmod -R 775 /var/www/html/runtime /var/www/html/web/assets \
  && chown -R www-data:www-data /var/www/html