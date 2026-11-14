# =============================================================
# Môi trường chạy Lồng Quay May Mắn: PHP 8.2 + Apache
#   - extension: zip (đọc/ghi Excel), intl (chuẩn hóa dấu tiếng Việt),
#     mbstring, xml (có sẵn trong image gốc)
#   - Composer để cài thư viện của dashboard
#   - Apache đọc file .htaccess (chặn mở src/, vendor/, backups/…)
# =============================================================
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libicu-dev unzip \
    && docker-php-ext-install zip intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Cho phép .htaccess hoạt động
RUN sed -ri 's#(<Directory /var/www/>[^<]*AllowOverride) None#\1 All#' /etc/apache2/apache2.conf \
    && grep -A4 '<Directory /var/www/>' /etc/apache2/apache2.conf | grep -q 'AllowOverride All'

COPY docker/php.ini /usr/local/etc/php/conf.d/long-quay.ini
COPY docker/khoi-dong.sh /usr/local/bin/khoi-dong.sh
RUN sed -i 's/\r$//' /usr/local/bin/khoi-dong.sh && chmod +x /usr/local/bin/khoi-dong.sh

WORKDIR /var/www/html
ENTRYPOINT ["khoi-dong.sh"]
CMD ["apache2-foreground"]
