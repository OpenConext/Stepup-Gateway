#!/usr/bin/env bash
uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=gateway" > .env

docker-compose up -d --build

docker-compose exec -T php-fpm.stepup.example.com bash -c '
  composer install --prefer-dist -n -o && \
  ./app/console assetic:dump --env=webtest --verbose'
