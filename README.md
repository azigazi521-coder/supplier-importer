# Supplier Importer

Aplikacja Symfony 6 do importowania stanów magazynowych dostawców z plików CSV do MySQL. Projekt działa w Docker Compose i używa PHP 8.4, MySQL 8.0, Nginx oraz phpMyAdmin.

## Wymagania

- Docker i Docker Compose
- Git

## Uruchomienie

Sklonuj repozytorium i przejdź do katalogu projektu:

```bash
git clone https://github.com/azigazi521-coder/supplier-importer.git
cd supplier-importer
```

Utwórz lokalną konfigurację środowiska, jeżeli projekt jej jeszcze nie posiada:

```bash
cp .env .env.local
```

Uruchom kontenery:

```bash
docker compose up -d --build
```

Zainstaluj zależności PHP:

```bash
docker compose exec web composer install
```

Utwórz bazę i uruchom migracje:

```bash
docker compose exec web php bin/console doctrine:database:create --if-not-exists
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

## Dostępne usługi

- Aplikacja: http://localhost:8080
- API stanów magazynowych: http://localhost:8080/get-stocks
- phpMyAdmin: http://localhost:8081
    - użytkownik: `root`
    - hasło: `root`

## Import stanów magazynowych

Komenda importu przyjmuje ścieżkę pliku, nazwę dostawcy oraz opcjonalny tryb:

```text
app:import-stock <filepath> <supplier> [<mode>]
```

### Tryb legacy

Brak trzeciego argumentu uruchamia import rekord po rekordzie. Jest to tryb kompatybilny z dotychczasowymi wywołaniami:

```bash
docker compose exec web php bin/console app:import-stock data/trah.csv trah
docker compose exec web php bin/console app:import-stock data/lorotom.csv lorotom
```

Tryb używa `StockItemUpserter` oraz Doctrine ORM.

### Tryb multiline

Argument `multiline` uruchamia wielowierszowy upsert przez Doctrine DBAL i MySQL `INSERT ... ON DUPLICATE KEY UPDATE`:

```bash
docker compose exec web php bin/console app:import-stock data/trah.csv trah multiline
docker compose exec web php bin/console app:import-stock data/lorotom.csv lorotom multiline
```

Dane są przetwarzane strumieniowo w batchach. Rozmiar batcha jest wspólny dla obu trybów i wynosi obecnie 50 rekordów.

Obsługiwana wartość trybu to `multiline`. Wartość jest normalizowana przez usunięcie spacji i zamianę na małe litery, więc `MULTILINE` również jest poprawne. Nieznany tryb kończy komendę kodem `INVALID`.

## API

Endpoint wymaga co najmniej jednego parametru `mpn` lub `ean`:

```bash
curl 'http://localhost:8080/get-stocks?mpn=19-598'
curl 'http://localhost:8080/get-stocks?ean=5905694015970'
curl 'http://localhost:8080/get-stocks?mpn=19-598&ean=5905694015970'
```

Parametry są trimowane. Brak obu filtrów zwraca HTTP `400`:

```json
{
    "error": "Bad Request",
    "message": "At least one query attribute (mpn or ean) must be specified."
}
```

Poprawna odpowiedź zawiera między innymi pola `id`, `ean`, `mpn`, `producer_name`, `external_id`, `price`, `quantity` i `supplier`.

## Testy

Testy uruchamiaj z katalogu projektu. Testy działają w kontenerze `web` i używają środowiska `test`.


```bash
docker compose exec web php bin/phpunit
```


## Przydatne komendy

```bash
# Zatrzymanie kontenerów
docker compose down

# Shell kontenera aplikacji
docker compose exec web bash

# Logi aplikacji
docker compose logs -f web

# Czyszczenie cache Symfony
docker compose exec web php bin/console cache:clear

# Nowa migracja
docker compose exec web php bin/console make:migration

# Lista komend aplikacji
docker compose exec web php bin/console list
```
