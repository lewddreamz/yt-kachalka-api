FROM php:8.4-fpm
RUN apt-get update && apt-get install -y \
    libzip-dev \
    acl
RUN docker-php-ext-install zip exif pcntl
# Устанавливаем Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Устанавливаем рабочую директорию
WORKDIR /var/www/html

#RUN mkdir -p /var/www/html/var /var/www/html/var/cache /var/www/html/var/log
# RUN HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx|[c]addy|[f]rankenphp' | grep -v root | head -1 | cut -d\  -f1)

# RUN setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var

# RUN setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
# Открываем порт 9000 (для FPM)
EXPOSE 9000
#entrypoint
COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Запускаем PHP-FPM
CMD ["php-fpm"]

