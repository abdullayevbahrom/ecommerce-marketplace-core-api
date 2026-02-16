FROM ghcr.io/abdullayevbahrom/operator-base-php:8.3-fpm-alpine3.20

COPY --chown=www-data:www-data ./ ./

RUN mkdir -p runtime web/assets \
  && chmod -R 775 runtime web/assets \
  && chown -R www-data:www-data /var/www/html