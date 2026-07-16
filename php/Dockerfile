# Dockerfile для PHP-FPM з необхідними розширеннями для LinuxTest
FROM php:8.2-fpm-alpine

# Встановлення системних залежностей
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        intl

# Налаштування PHP конфігурації
RUN echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Europe/Kyiv" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini

# Встановлення робочого каталогу
WORKDIR /var/www

# Зміна власника файлів
RUN chown -R www-data:www-data /var/www

# Відкриваємо порт PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
