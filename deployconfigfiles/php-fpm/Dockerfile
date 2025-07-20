FROM php:8.3-fpm
WORKDIR "/application"

# Install Supervisor
RUN apt-get update && apt-get install -y supervisor

# Copy the Supervisor config file
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install system dependencies
RUN apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    sudo \
    libicu-dev \
    libpq-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl pdo_pgsql pgsql zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install git
RUN apt-get update \
    && apt-get -y install git \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*