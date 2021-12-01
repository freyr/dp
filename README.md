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

## HomeWork Day 2
W src/Checkout jest kontroller obsługujący żądanie wykonania płatności. Płatność wykonuje transaction processor z src/Payment.
Nowe wymaganie polega na dodaniu notyfikacji o wykonaniu płatności. Ważne jest aby wysyłka noyfikacji nie była realizowana w kontrolerze -
chcemy zintegrować ją bezpośrednio w proces realizacji płatności tak żeby była wysyłana automatycznie. Niestety src/Payment jest poza naszą kontrolą:
Nie możemy zmodyfikować plików tam zawartych. W jaki sposób zrealizować dodatkową funkcjonalność?

Uwagi:
Kodu nie trzeba uruchamiać. Wystarczy wykonać zmiany w src/Checkout (nie ma potrzeby dodawania routingów czy usług w kontenerze - zakładamy że zostana one wykonane pożniej)


