#!/bin/sh
set -e

# Создаем папки, если их нет (даже если volume перекрывает)
mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Меняем владельца на www-data
chown -R www-data:www-data /var/www/html/var

exec "$@"
