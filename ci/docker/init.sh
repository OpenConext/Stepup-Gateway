#!/usr/bin/env bash
uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=gateway" > .env

docker-compose up -d

docker-compose exec -T php-fpm.stepup.example.com bash -c '
  cp ./ci/config/*.yaml ./config/legacy/ && \
  mkdir -p app/files && \
  cp ./ci/certificates/* ./app/files/ && \
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \
  ./app/console assets:install --env=test --verbose
'
