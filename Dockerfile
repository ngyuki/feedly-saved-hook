FROM php:7.2-alpine

RUN mkdir -p /app/
WORKDIR /app/

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json /app/
COPY composer.lock /app/

RUN composer install -o --no-dev

ENV TZ=Asia/Tokyo \
    APP_CACHE_DIR=/app/data

EXPOSE 8080
VOLUME /app/data

CMD [ "tail", "-f", "/dev/null" ]

COPY script/ /app/script/
COPY src/    /app/src/
