FROM php:8.2-fpm-alpine

# Обновляем индексы репозиториев и устанавливаем системные зависимости
# Обратите внимание: postgres-client изменен на postgresql-client
RUN apk update && apk add --no-cache \
    bash \
    postgresql-client \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# Установка PHP расширений (включая pdo_pgsql для работы с Postgres)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip gd bcmath

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www

# Копирование существующих файлов проекта
COPY . .

# Открытие порта для php-fpm
EXPOSE 9000
CMD ["php-fpm"]
