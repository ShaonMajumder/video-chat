FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    libssl-dev \
    redis-server \
    supervisor \
    dos2unix \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip sockets intl

RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js and npm (v18 LTS recommended)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    node -v && npm -v

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf

RUN if [ -n "$APP_ENV_FILE" ] && [ -f environment/$APP_ENV_FILE ]; then \
    chmod 644 environment/$APP_ENV_FILE; \
    fi

RUN [ -d /var/www/html/storage ] && chown -R www-data:www-data /var/www/html/storage || echo "storage not found" && \
    [ -d /var/www/html/bootstrap/cache ] && chown -R www-data:www-data /var/www/html/bootstrap/cache || echo "cache not found" && \
    git config --global --add safe.directory /var/www/html
# for windows
# 0.484 fatal: detected dubious ownership in repository at '/var/www/html'
# 0.484 To add an exception for this directory, call:
# 0.484
# 0.484   git config --global --add safe.directory /var/www/html

# RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
#     && composer dump-autoload -o \
#     && php artisan package:discover
RUN if [ -f artisan ] && [ -f composer.json ]; then \
    composer install --no-interaction --prefer-dist --optimize-autoloader && \
    composer dump-autoload -o && \
    php artisan package:discover && \
    echo "Running npm install..." && \
    npm install && \
    echo "Running npm run dev..." && \
    npm run build; \
    else \
    echo "Laravel not found. Skipping composer install and artisan commands."; \
    fi


# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
# RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# RUN php artisan key:generate
RUN if [ -f artisan ]; then \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && \
    php artisan key:generate; \
    else \
    echo "Laravel not found. Skipping permissions and key generation."; \
    fi

COPY ./docker/startup.sh /usr/local/bin/startup.sh
# Convert line endings to Unix format, fix for windows
RUN dos2unix /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh
ENTRYPOINT ["/usr/local/bin/startup.sh"]

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
