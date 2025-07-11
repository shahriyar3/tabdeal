FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    libc-client-dev \
    libkrb5-dev \
    ca-certificates \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip imap

# Install yarn
RUN npm install -g yarn

RUN yarn config set registry https://registry.npmjs.org/


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html/

# نصب وابستگی‌های پلاگین و build آن
WORKDIR /var/www/html/plugins/GrapesJsBuilderBundle
RUN yarn install && yarn run build

# نصب وابستگی‌های اصلی پروژه و build
WORKDIR /var/www/html
RUN yarn install && yarn build

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN git config --global --add safe.directory /var/www/html

RUN sed -i 's/npm ci --prefer-offline --no-audit/yarn install/' composer.json

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p /var/www/html/var/cache \
    && mkdir -p /var/www/html/var/logs \
    && mkdir -p /var/www/html/var/tmp \
    && chown -R www-data:www-data /var/www/html/var

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]