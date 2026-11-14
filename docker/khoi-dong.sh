#!/bin/sh
# Chạy mỗi lần container khởi động, trước Apache.
set -e

# Thư viện của dashboard (thư mục vendor không có trong git): chưa có thì cài
if [ ! -f /var/www/html/dashboard/vendor/autoload.php ]; then
    echo "[long-quay] Đang cài thư viện cho dashboard (composer install)..."
    composer install --working-dir=/var/www/html/dashboard --no-dev --no-interaction --no-progress --prefer-dist
fi

# Thư mục sao lưu phải ghi được
mkdir -p /var/www/html/dashboard/backups

exec docker-php-entrypoint "$@"
