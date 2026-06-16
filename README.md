# Symfony 6 Application (Docker)

Projekt aplikacji Symfony 6 uruchamiany w środowisku Docker z serwerem Nginx, PHP 8.4, bazą MySQL 8.0 oraz narzędziem phpMyAdmin.

## Wymagania wstępne

Przed uruchomieniem projektu upewnij się, że masz zainstalowane:
*   [Docker i Docker Compose](https://docker.com)
*   [Git](https://git-scm.com)

---

## Instalacja i uruchomienie krok po kroku

### 1. Klonowanie repozytorium
Skopiuj projekt na swój dysk lokalny i wejdź do katalogu projektu:
```bash
git clone https://github.com/azigazi521-coder/supplier-csv-parser.git
cd supplier-csv-parser/
```

### 2. Konfiguracja zmiennych środowiskowych
Utwórz lokalny plik konfiguracyyjny Symfony. Skopiuj domyślny szablon:
```bash
cp .env .env.local
```

### 3. Uruchomienie kontenerów Docker
Zbuduj i uruchom kontenery w tle:
```bash
docker compose up -d --build
```

### 4. Instalacja zależności Composer
Zainstaluj biblioteki PHP wewnątrz kontenera aplikacji (`web`):
```bash
docker compose exec web composer install
```

### 5. Przygotowanie bazy danych

Utwórz nową bazę danych (jeżeli jeszcze nie istnieje):
```bash
docker compose exec web php bin/console doctrine:database:create --if-not-exists
```

Uruchom migracje, aby stworzyć niezbędne tabele w bazie danych:
```bash
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Dostęp do aplikacji

Po poprawnym wykonaniu powyższych kroków usługi są dostępne pod adresami:

*   **Aplikacja (Symfony):** [http://localhost:8080](http://localhost:8080)
*   **Baza danych (phpMyAdmin):** [http://localhost:8081](http://localhost:8081)
    *   *Użytkownik:* `root`
    *   *Hasło:* `root`

---

## Import danych (Zasilanie bazy)

Aby dodać początkowe wpisy i zaimportować dane z plików CSV do bazy danych, uruchom w terminalu następujące komendy:

```bash
# Import danych dla dostawcy 'trah'
docker compose exec web php bin/console app:import-stock data/trah.csv trah

# Import danych dla dostawcy 'lorotom'
docker compose exec web php bin/console app:import-stock data/lorotom.csv lorotom
```

---

## Pobranie danych

Endpoint do weryfikacji poprawnosci danych jest dostępny pod adresam:

*   **Pobieranie danych z bazy (Endpoint):** [http://localhost:8080/get-stocks](http://localhost:8080/get-stocks)
---

## Przydatne komendy deweloperskie

*   **Zatrzymanie kontenerów:** `docker compose down`
*   **Wejście do konsoli kontenera PHP:** `docker compose exec web bash`
*   **Czyszczenie pamięci podręcznej Symfony:** `docker compose exec web php bin/console cache:clear`
*   **Tworzenie nowej migracji bazy danych:** `docker compose exec web php bin/console make:migration`
*   **Podgląd logów aplikacji w czasie rzeczywistym:** `docker compose logs -f web`
