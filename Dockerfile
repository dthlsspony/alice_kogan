FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libzip-dev \
        zip \
        unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache \
    && apt-get purge -y --auto-remove libonig-dev \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

# Лимиты загрузки: чтобы FileUpload maxSize (4 МБ) был достижим.
RUN { \
        echo "upload_max_filesize=8M"; \
        echo "post_max_size=10M"; \
        echo "max_file_uploads=20"; \
    } > /usr/local/etc/php/conf.d/zz-uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN chown -R www-data:www-data /var/www/html/

WORKDIR /var/www/html
