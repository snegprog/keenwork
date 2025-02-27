FROM php:8.4-alpine3.21

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync

RUN mkdir -p /var/www/app  \
    && apk update  \
    && apk add bash vim net-tools tzdata openssh \
    && echo "set number" >> /etc/vim/vimrc \
    && echo "set ts=4" >> /etc/vim/vimrc

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/bin/ --filename=composer

RUN install-php-extensions yaml event ev gd zip ldap pgsql pdo pdo_pgsql pcntl redis

RUN ln -snf /usr/share/zoneinfo/Europe/Moscow /etc/localtime

WORKDIR /var/www/app

CMD ["/var/www/app/app.php", "start"]
