# Use an official PHP image with Apache
FROM php:7.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install libzip for ZipArchive (required by PHPExcel for .xlsx import)
RUN apt-get update && apt-get install -y libzip-dev && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install intl \
    && echo 'expose_php = Off' >> /usr/local/etc/php/conf.d/security.ini \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy custom Apache configuration
COPY ./docker/000-default.conf /etc/apache2/sites-available/yii2-app.conf
RUN a2ensite yii2-app

# Disable the default site to prevent conflicts
RUN a2dissite 000-default.conf

# Set working directory to Apache web root
WORKDIR /var/www/html/

# Copy entrypoint script and fix Windows line endings (CRLF → LF)
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]