FROM composer:2.5.5 AS builder

COPY ./app /app

RUN composer install

FROM farfromunique/unit:1.29.1-php8.2 AS runner

# port used by the listener in config.json
EXPOSE 8080

# Currently working as ROOT

RUN addgroup --gid 1000 futhark && \
    adduser --system --uid 1000 \
            --no-create-home --ingroup futhark \
            --disabled-password --disabled-login \
            futhark

RUN apt-get update \
    && apt-get upgrade -y
#    && apt-get install -y 

# This copies over JSON files, Shell Scripts, and .pem certificates
#  and automagically applies them.
COPY ./conf/unit /docker-entrypoint.d
COPY --from=builder --chown=1000:1000 /app /www/app
