#!/usr/bin/env bash
uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=gateway" > .env

docker-compose -f docker-compose-php72.yml up -d --build

docker-compose -f docker-compose-php72.yml exec -T php-fpm.stepup.example.com bash -c '
  composer install --prefer-dist -n -o && \
  npm install --only=prod && \
  ./app/console assetic:dump --env=webtest --verbose'