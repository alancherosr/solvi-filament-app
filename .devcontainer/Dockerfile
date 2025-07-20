# Use the official PHP image with Apache
FROM php:8.4-apache

# Upgrade all packages to their latest versions to reduce vulnerabilities
RUN apt-get update && apt-get upgrade -y

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim optipng pngquant gifsicle \
    vim \
    postgresql-client \
    libpq-dev \
    supervisor \
    sudo \
    wget \
    ca-certificates

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Install Node.js 18.x
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a non-root user
RUN groupadd --gid 1000 vscode \
    && useradd --uid 1000 --gid vscode --shell /bin/bash --create-home vscode \
    && usermod -aG www-data vscode

# Install Claude Code globally
RUN npm install -g @anthropic-ai/claude-code

# Switch back to root for remaining setup
USER root

# Set working directory
WORKDIR /workspace

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache for Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /workspace/public\n\
    <Directory /workspace/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Give vscode user sudo access for Apache management and permission fixes
RUN echo "vscode ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

# Set working directory and initial permissions
WORKDIR /workspace
RUN mkdir -p /workspace/public /workspace/storage /workspace/bootstrap/cache \
    && chown -R vscode:www-data /workspace \
    && chmod -R 775 /workspace

# Switch to non-root user
USER vscode