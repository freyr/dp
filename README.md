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

## Locally simplified
```bash
./composer install --ignore-platform-reqs
#to run routing test
php src/Routing/start.php
```

## HomeWork Day 1
1. Usunąć z systemu routingów w src/Routing wzorzec Chain Of Responsibility i zastąpić go Kolekcją lub dowolnie innym wybranym mechanizmem
2. Zbudować system logujący dane do pliku lub na ekran. 
   1. Klasa klienta wykonująca kilka czynności z której jedna to zalogowanie danych do LoggeraWykorzystać fabrykę (factory method). 
   2. Implementacja Loggera dostarczany do klienta ma się różnić w zależności od konfiguracji fabryki 
   3. Bonusowo można ten sam system zbudować wykorzystując template method


