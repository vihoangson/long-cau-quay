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

# Cho phép .htaccess hoạt động (file cấu hình riêng, nạp sau apache2.conf nên ghi đè được)
COPY docker/apache-htaccess.conf /etc/apache2/conf-available/long-quay.conf
RUN a2enconf long-quay

COPY docker/php.ini /usr/local/etc/php/conf.d/long-quay.ini
COPY docker/khoi-dong.sh /usr/local/bin/khoi-dong.sh
RUN sed -i 's/\r$//' /usr/local/bin/khoi-dong.sh && chmod +x /usr/local/bin/khoi-dong.sh

WORKDIR /var/www/html
ENTRYPOINT ["khoi-dong.sh"]
CMD ["apache2-foreground"]
