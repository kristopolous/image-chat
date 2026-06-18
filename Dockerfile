FROM php:8.2-apache-bookworm

ENV MYSQL_PASSWORD=changeme
ENV MYSQL_ROOT_PASSWORD=rootpass

RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-server \
    mariadb-client \
    pandoc \
    weasyprint \
    poppler-utils \
    qrencode \
    imagemagick \
    supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && a2enmod headers

RUN sed -i 's!/var/www/html!/var/www/html/web!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

RUN sed -i 's/^bind-address\s*=.*/bind-address = 127.0.0.1/' /etc/mysql/mariadb.conf.d/50-server.cnf \
    && sed -i '/^bind-address/a port = 33065' /etc/mysql/mariadb.conf.d/50-server.cnf

COPY . /var/www/html/
RUN mkdir -p /var/www/html/web/cache && chmod 777 /var/www/html/web/cache

COPY docker-supervisord.conf /etc/supervisor/conf.d/
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
