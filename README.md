# SETUP

## With docker
```shell
docker build . -t dp:latest
docker run dp:latest
```

## With docker-compose
```shell
docker-compose up --build
```

## Locally
```shell
pecl install redis
composer install
vendor/bin/phpunit
```

