FROM php:8.4-fpm

WORKDIR /app

ARG APP_ENV=prod
ARG DATABASE_URL=postgresql://database_user:database_password@0.0.0.0:5432/database_name?serverVersion=12&charset=utf8
ARG AUTHENTICATION_BASE_URL=https://users.example.com
ARG MESSENGER_RETRY_STRATEGY_DELAY=1000
ARG IS_READY=0
ARG SELF_URL=https://results.example.com

ENV APP_ENV=$APP_ENV
ENV DATABASE_URL=$DATABASE_URL
ENV AUTHENTICATION_BASE_URL=$AUTHENTICATION_BASE_URL
ENV IS_READY=$IS_READY
ENV SELF_URL=$SELF_URL
ENV MESSENGER_RETRY_STRATEGY_DELAY=$MESSENGER_RETRY_STRATEGY_DELAY

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get -qq update && apt-get -qq -y install  \
  git \
  libpq-dev \
  libzip-dev \
  supervisor \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir -p var/log/supervisor
COPY build/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY build/supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

COPY composer.json /app/
COPY bin/console /app/bin/console
COPY public/index.php public/
COPY src /app/src
COPY config/bundles.php config/services.yaml /app/config/
COPY config/packages/*.yaml /app/config/packages/
COPY config/routes.yaml /app/config/
COPY migrations /app/migrations

RUN mkdir -p /app/var/log \
  && chown -R www-data:www-data /app/var/log \
  && echo "APP_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)" > .env \
  && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts \
  && rm composer.lock \
  && php bin/console cache:clear

CMD supervisord -c /etc/supervisor/supervisord.conf
