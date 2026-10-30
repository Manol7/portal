# Portal

Prywatny portal rozwijany w Symfony. Użytkownicy logują się do aplikacji, a konta tworzy superadministrator. Nie ma publicznej rejestracji.

Projekt jest rozwijany lokalnie na Windows w VS Code i Docker Desktop. Kod trafia przez GitHub na serwer Ubuntu działający w maszynie Hyper-V. Wdrożenie uruchamiamy ręcznie przez SSH.

## Technologie

- Symfony 7.4 i PHP 8.4 (PHP-FPM).
- Twig — szablony HTML.
- Doctrine ORM i Doctrine Migrations — obsługa danych i zmian struktury bazy.
- MariaDB 11.4 — baza danych.
- Symfony Security — logowanie, hashowanie haseł i uprawnienia.
- Symfony Forms i Validator — formularze i sprawdzanie danych.
- Caddy — serwer HTTP przekazujący żądania PHP do PHP-FPM.
- Docker Compose — uruchamianie usług `php`, `caddy` i `db`.
- Git i GitHub — wersjonowanie oraz dostarczanie kodu na VM.

## Środowiska

| Element | Lokalnie | Serwer VM |
|---|---|---|
| System | Windows + Docker Desktop | Ubuntu 24.04 LTS w Hyper-V |
| Katalog projektu | `C:\Users\manol\projects\portal` | `/home/kamil/deploy/portal` |
| Adres strony | http://localhost:8080 | http://172.31.215.17:8080 |
| Terminal | PowerShell w VS Code | Bash przez SSH |
| Projekt Compose | domyślnie `portal` od nazwy katalogu | jawnie `-p portal` |

Adres VM pochodzi z sieci Hyper-V Default Switch i może się zmienić. Wtedy należy używać aktualnego IP również przy połączeniu SSH.

**Bazy są osobne.** Git przenosi kod i migracje, ale nie przenosi kont użytkowników, haseł ani innych danych z lokalnej bazy na VM.

## Struktura projektu

```text
portal/
├── app/                       # aplikacja Symfony
│   ├── config/                # konfiguracja, w tym security.yaml
│   ├── migrations/            # wersjonowane zmiany struktury bazy
│   ├── public/
│   │   ├── scripts/           # app.js (wejście) + modules/ (moduły ES per funkcja)
│   │   └── styles/app.css     # arkusz stylów
│   ├── src/
│   │   ├── Account/           # reguły pól konta i walidacja edycji
│   │   ├── Command/           # polecenia konsolowe aplikacji
│   │   ├── Controller/        # obsługa stron i żądań
│   │   ├── Entity/            # encje Doctrine, np. User
│   │   ├── EventSubscriber/   # język, strony błędów, komunikat po logowaniu
│   │   ├── Form/              # formularze, np. CreateUserType
│   │   ├── Repository/        # pobieranie encji z bazy
│   │   ├── Security/          # Roles (lista ról) i UserPermissions (kto kim zarządza)
│   │   ├── Translation/       # DefaultTexts, Translations, tryb dynamicznego tłumaczenia
│   │   └── Twig/              # funkcje Twig (zasoby, role, limity)
│   ├── templates/             # szablony Twig; _partials/ to części szkieletu strony
│   ├── var/                   # cache i logi, poza Git
│   └── vendor/                # biblioteki Composer, poza Git
├── docker/
│   ├── php/Dockerfile
│   └── caddy/Caddyfile
├── scripts/deploy.sh          # wdrożenie na VM
├── compose.yaml
├── .env                       # konfiguracja Compose i hasła, poza Git
├── .env.example               # przykład konfiguracji bez prawdziwych haseł
├── .gitattributes             # m.in. zakończenia linii LF dla skryptów Bash
└── README.md
```

## Konfiguracja i dane

Główny plik `portal/.env` jest czytany przez Docker Compose. Zawiera:

```dotenv
LOCAL_UID=1000
LOCAL_GID=1000
DB_PASSWORD=UZUPELNIJ_LOSOWYM_HASLEM
DB_ROOT_PASSWORD=UZUPELNIJ_INNYM_LOSOWYM_HASLEM
```

Na Ubuntu `LOCAL_UID` i `LOCAL_GID` muszą odpowiadać użytkownikowi posiadającemu pliki projektu. Można je sprawdzić przez `id -u` i `id -g`.

To inny plik niż `app/.env`, który zawiera ustawienia Symfony. Compose przekazuje `DATABASE_URL` bezpośrednio do kontenera PHP, więc aplikacja korzysta z połączenia z bazą pod adresem `db:3306`.

Zapis `${DB_PASSWORD:?Ustaw DB_PASSWORD w .env}` pobiera wartość zmiennej. Jeśli jest pusta lub jej brakuje, Compose zatrzymuje operację z podanym komunikatem.

- Każde środowisko ma własne hasła w głównym `.env`.
- Prawdziwych haseł nie wpisujemy do plików śledzonych przez Git.
- Baza korzysta z wolumenu `db_data`; dane przetrwają usunięcie samego kontenera.
- Wolumen nie jest kopią zapasową.
- **Nie używaj `docker compose down -v` do zwykłego zatrzymywania projektu — usuwa także wolumeny z danymi.**
- Zmienne inicjalizacyjne MariaDB tworzą bazę i użytkownika przy pierwszym uruchomieniu na pustym wolumenie. Późniejsza zmiana hasła w `.env` nie zmienia automatycznie hasła w istniejącej bazie.

## Praca lokalna — PowerShell

Docker Desktop musi być uruchomiony i obsługiwać kontenery Linux. Wszystkie polecenia wykonujemy w głównym katalogu projektu:

```powershell
cd C:\Users\manol\projects\portal
```

### Uruchamianie i zatrzymywanie

```powershell
docker compose up -d --build
docker compose ps
docker compose logs --tail=50 php caddy db
docker compose stop
docker compose start
```

`stop` zatrzymuje kontenery. `start` uruchamia istniejące kontenery, a `up` uwzględnia zmiany konfiguracji i tworzy brakujące.

### Symfony i Composer

```powershell
# Instalacja bibliotek zgodnie z composer.lock
docker compose exec php composer install --no-interaction --prefer-dist

# Lista poleceń Symfony
docker compose exec php php bin/console list

# Czyszczenie cache
docker compose exec php php bin/console cache:clear

# Sprawdzenie konfiguracji i szablonów
docker compose exec php php bin/console lint:yaml config
docker compose exec php php bin/console lint:container
docker compose exec php php bin/console lint:twig templates

# Wejście do powłoki kontenera PHP
docker compose exec php sh
```

Biblioteki dodajemy lokalnie przez `composer require`. Zmienione `app/composer.json` i `app/composer.lock` zapisujemy w Git. Serwer instaluje dokładnie wersje zapisane w pliku lock.

### Baza i migracje

```powershell
# Sprawdzenie połączenia aplikacji z bazą
docker compose exec php php bin/console dbal:run-sql "SELECT DATABASE(), VERSION()"

# Lista tabel
docker compose exec php php bin/console dbal:run-sql "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()"

# Generowanie migracji po zmianie encji
docker compose exec php php bin/console make:migration

# Wykonanie migracji lokalnie
docker compose exec php php bin/console doctrine:migrations:migrate

# Status migracji
docker compose exec php php bin/console doctrine:migrations:status

# Interaktywne połączenie klientem MariaDB; podaj DB_PASSWORD
docker compose exec db mariadb -u portal -p portal
```

Migrację generujemy lokalnie, czytamy jej zawartość i sprawdzamy działanie. Plik z `app/migrations/` trafia do Git. Na VM wykonujemy istniejące migracje; nie generujemy ich ponownie.

### Użytkownicy

```powershell
# Tworzenie superadministratora; e-mail i hasło wpisujemy interaktywnie
docker compose exec php php bin/console app:create-super-admin

# Podgląd kont bez odczytywania hashy haseł
docker compose exec php php bin/console dbal:run-sql "SELECT id, email, roles FROM user"
```

Panel kont znajduje się pod http://localhost:8080/admin/users. Wylogowanie: http://localhost:8080/logout.

### Git — wysyłanie zmian

```powershell
git status
git diff
git add app
git diff --cached --stat
git commit -m "Describe the change"
git push origin main
```

Do `git add` dobieramy ścieżki do zmiany, np. `README.md`, `compose.yaml` lub `scripts/deploy.sh`. Przed commitem sprawdzamy, co zostało przygotowane. Sam `git push` **nie uruchamia wdrożenia**.

## Serwer — SSH i Bash

Połączenie wykonujemy z PowerShell:

```powershell
ssh kamil@172.31.215.17
```

Dalsze polecenia wykonujemy już na Ubuntu:

```bash
cd /home/kamil/deploy/portal

# Wdrożenie najnowszego main
bash scripts/deploy.sh

# Stan kontenerów
docker compose -p portal ps

# Ostatnie logi
docker compose -p portal logs --tail=50 php caddy db

# Cache i diagnostyka Symfony
docker compose -p portal exec php php bin/console cache:clear
docker compose -p portal exec php php bin/console doctrine:migrations:status

# Konto superadministratora w bazie VM
docker compose -p portal exec php php bin/console app:create-super-admin

# Aktualnie pobrany commit i lokalne zmiany
git log -1 --oneline
git status

# Zakończenie sesji SSH
exit
```

VM pobiera prywatne repozytorium przez klucz wdrożeniowy GitHub z prawem odczytu. Alias SSH `github-portal` wskazuje na GitHub, a origin ma adres `git@github-portal:Manol7/portal.git`. Klucz prywatny pozostaje na VM i nie trafia do repozytorium.

## Jak działa wdrożenie

1. Lokalnie zmieniamy kod, sprawdzamy go, wykonujemy commit i `git push origin main`.
2. Na VM uruchamiamy `bash scripts/deploy.sh`.
3. Skrypt przechodzi do katalogu wdrożenia i zakłada blokadę `flock`, aby nie dopuścić do dwóch wdrożeń jednocześnie.
4. Sprawdza czystość repozytorium. Jeśli są lokalne zmiany na VM, zatrzymuje wdrożenie.
5. Pobiera `main` przez `git pull --ff-only`. Nie tworzy automatycznych merge commitów.
6. Sprawdza konfigurację Compose i buduje obraz PHP.
7. Uruchamia bazę i czeka maksymalnie 180 sekund na gotowość potwierdzoną przez healthcheck.
8. Instaluje biblioteki z `composer.lock` w jednorazowym kontenerze PHP.
9. Sprawdza YAML, kontener usług Symfony i szablony Twig.
10. Wykonuje niewykonane migracje bez dodatkowego potwierdzenia i czyści cache.
11. Odtwarza kontenery `php` i `caddy`, bez wymuszania odtworzenia `db`.
12. Sprawdza, czy `/login` odpowiada HTTP 200, i wyświetla wdrożony commit. Przy niepowodzeniu pokazuje logi aplikacji.

`set -Eeuo pipefail` zatrzymuje skrypt przy błędzie. Kontrola HTTP sprawdza odpowiedź formularza, ale nie testuje pełnego logowania ani uprawnień.

### Aktualizacja samego skryptu

Gdy commit zmienia `scripts/deploy.sh`, przed jego uruchomieniem pobieramy nową wersję:

```bash
cd /home/kamil/deploy/portal
git pull --ff-only origin main
bash scripts/deploy.sh
```

### Obecne ograniczenia

- Wdrożenie działa w istniejącym katalogu. Kontenery korzystają z podmontowanych plików, więc pobrany kod jest widoczny jeszcze przed końcem wszystkich kontroli.
- Przy odtwarzaniu PHP i Caddy może wystąpić krótka przerwa.
- Nie ma automatycznego rollbacku ani kopii bazy przed migracją.
- Zmiana definicji usługi `db` może spowodować jej odtworzenie przez Compose; zwykłe wdrożenie bez zmiany tej usługi nie wymusza restartu bazy.
- Instalujemy obecnie również zależności developerskie. Konfiguracja VM nie jest jeszcze docelową konfiguracją produkcyjną.
- Serwer jest domową VM: dostępność zależy od hosta Windows i sieci. Nie jest jeszcze publicznym serwisem z domeną i HTTPS.

## Uprawnienia

| Rola | Obecne uprawnienia |
|---|---|
| `ROLE_USER` | Dostęp do portalu po zalogowaniu |
| `ROLE_ADMIN` | Dziedziczy `ROLE_USER`; dopuszczony przez ogólną regułę `/admin` |
| `ROLE_SUPER_ADMIN` | Dziedziczy `ROLE_ADMIN`; może wejść do panelu kont i tworzyć użytkowników |

Hierarchia ról znajduje się w **głównej** sekcji `security:` w `app/config/packages/security.yaml`. Sekcja `when@test:` dotyczy tylko środowiska testowego.

Reguły `access_control` są sprawdzane od góry i stosowana jest pierwsza pasująca:

- `/login` i `/logout` są publiczne.
- `/admin` wymaga `ROLE_ADMIN`.
- Pozostałe adresy wymagają `ROLE_USER`.

Panel `/admin/users` jest dostępny dla administratorów, ale reguły „kto kim zarządza” są w jednym miejscu: `app/src/Security/UserPermissions.php`. Administrator nie widzi i nie może nadać ani edytować roli superadministratora. Lista ról to `app/src/Security/Roles.php`; nowa rola wymaga wpisu tam, w hierarchii `security.yaml` i tekstu `role.<ROLA>` w `DefaultTexts`.

Hasła są hashowane przez Symfony. Formularz tworzenia kont sprawdza adres e-mail, jego unikalność, długość hasła oraz zgodność powtórzonego hasła (limity w `app/src/Account/AccountRules.php`). Rolę nowego konta wybiera się spośród tych, które zalogowany użytkownik może nadać; konto superadministratora można też utworzyć poleceniem konsolowym.

## Tłumaczenia

Wszystkie teksty mają klucze w `app/src/Translation/DefaultTexts.php` (polski). Języki wymienia stała `Translations::LOCALES` (pierwszy jest domyślny). Nowy język: dopisz kod do `LOCALES`, dodaj flagę `templates/_partials/flags/<kod>.html.twig` oraz teksty `lang.name_<kod>`, `lang.to_<kod>` i `tr.col_<kod>`. Przełącznik, strona `/admin/translations` i okno dynamicznego tłumaczenia budują się z tej listy automatycznie. Nowy tekst wystarczy dopisać do `DefaultTexts`.

Breadcrumbs ustawia szablon strony: `{% set breadcrumbs = ['nav.klucz'] %}` (opcjonalnie `{% set breadcrumbs_root = false %}`).

## Panel administratora

`/admin` (ROLE_ADMIN) zawiera listę akcji (użytkownicy, tłumaczenia, wiadomości, ustawienia mailera). Nowa akcja: wpis w `App\Admin\AdminActions::list()`. Ustawienia są w bazie (tabela `setting`, serwis `App\Settings\Settings`); nowe ustawienie = stała i metoda w `Settings`. Menu zębatki zawiera tylko live translate i link do panelu. Okruszki podstron admina linkują do panelu: `{% set breadcrumbs = [{label: 'nav.admin', route: 'app_admin'}, 'nav.klucz'] %}`.

## Zadania

`/tasks` (każdy zalogowany) to tablica kanban: statusy z `App\Task\TaskStatus` (do zrobienia / w toku / zrobione), priorytety (`TaskPriority`), termin z oznaczaniem przeterminowanych, tagi, podzadania (checklista w JSON), opis, pasek postępu oraz filtry, wyszukiwanie i sortowanie (`TaskFilter`, parametry w URL). Zadania są prywatne; administrator może przełączyć widok na zadania wszystkich (`?scope=all`) i nimi zarządzać. Karty przeciąga się między kolumnami (zapis przez `POST /tasks/reorder`; przeciąganie działa przy sortowaniu ręcznym i własnych zadaniach). Strona odświeża się dynamicznie: filtry, wyszukiwanie (z opóźnieniem 250 ms) i sortowanie działają bez przycisku i bez przeładowania (adres w pasku jest aktualizowany, więc widok można odświeżyć lub udostępnić), a zapis, usunięcie, odhaczenie podzadania i przeciągnięcie podmieniają statystyki i tablicę (`GET /tasks?partial=1` z bieżącymi filtrami, części oznaczone `data-live-part`). Dodatkowo w tle widok odświeża się co 8 s (pomijane przy otwartym oknie edycji, przeciąganiu i ukrytej karcie), więc zmiany z innej karty lub konta są widoczne bez akcji użytkownika. Walidację formularza robi `TaskInputMapper`, logika w `TaskController`, front w `public/scripts/modules/tasks.js` i `public/styles/tasks.css`. Nowy status lub priorytet: case w enumie i teksty `tasks.status.*` / `tasks.priority.*`.

## Mailing i kontakt

Wysyłką maili zajmuje się Symfony Mailer (`App\Mail\MailerFactory` buduje transport z ustawień). Wszystko konfiguruje się w `/admin/mailer`: DSN, adres i nazwa nadawcy, odbiorcy (do 10) oraz próbny mail. DSN jest zapisywany szyfrowany (sodium, klucz z `APP_SECRET` — po zmianie sekretu trzeba wpisać DSN ponownie) i nigdy nie jest pokazywany w całości; puste pole zostawia obecny, checkbox przywraca domyślny. Zmienne środowiskowe (przez `compose.yaml`, wzór w `.env.example`) są tylko wartościami domyślnymi:

| Zmienna | Znaczenie |
|---|---|
| `MAILER_DSN` | transport; domyślnie `null://null` (nic nie wysyła), produkcyjnie np. `smtp://user:haslo@smtp.example.com:587` |
| `MAILER_FROM` | adres nadawcy; musi być zgodny z domeną/kontem SMTP |
| `CONTACT_RECIPIENT` | domyślny odbiorca wiadomości z formularza; ustawienie w `/admin/mailer` ma pierwszeństwo |
| `COMPOSE_PROFILES` | `dev` uruchamia lokalnie Mailpit |

Lokalnie w `.env`: `COMPOSE_PROFILES=dev` i `MAILER_DSN=smtp://mailpit:1025`. Wszystkie wysłane maile są wtedy widoczne w Mailpit pod `http://localhost:8025` i nigdzie nie wychodzą.

Formularz `/contact` jest publiczny. Wiadomość jest walidowana (CSRF, limity pól), chroniona honeypotem i limitem 5 wysyłek na godzinę z jednego IP (`config/packages/rate_limiter.yaml`), zapisywana w bazie, a potem wysyłana mailem do `CONTACT_RECIPIENT` z `Reply-To` ustawionym na nadawcę. Błąd wysyłki jest logowany i nie psuje formularza; wiadomość zostaje w bazie. Administratorzy czytają, oznaczają i usuwają wiadomości w `/admin/messages` (licznik nieprzeczytanych jest w menu zębatki). Kolejne maile: nowa klasa w `app/src/Mail/` i szablony w `app/templates/email/`.

## Zrobione

- [x] Ubuntu na Hyper-V i dostęp przez SSH.
- [x] Lokalne środowisko Docker Desktop oraz praca w VS Code.
- [x] Symfony, PHP-FPM, Caddy i strona główna w Twig.
- [x] Prywatne repozytorium GitHub i klucz odczytu na VM.
- [x] Ręczne wdrożenie jednym skryptem, z blokadą i kontrolami.
- [x] MariaDB z trwałym wolumenem i osobnymi bazami lokalnie oraz na VM.
- [x] Doctrine, encja użytkownika i pierwsza migracja.
- [x] Konto superadministratora, logowanie i wylogowanie.
- [x] Hierarchia ról i kontrola dostępu.
- [x] Panel tworzenia użytkowników i lista kont — sprawdzone lokalnie, w tym odmowa dostępu zwykłemu użytkownikowi.
- [x] Automatyczne wykonywanie migracji w skrypcie wdrożenia.

## TODO

### Najbliższe kroki

- [ ] Potwierdzić wdrożenie panelu użytkowników na VM i sprawdzić jego działanie tam.
- [ ] Edycja ról użytkowników, z ochroną ostatniego superadministratora.
- [ ] Zmiana hasła przez użytkownika i reset hasła przez superadministratora.
- [ ] Blokowanie kont, także skuteczne dla istniejących sesji.
- [ ] Spójny wygląd logowania, strony głównej i panelu oraz wspólna nawigacja.
- [ ] Pierwszy moduł użytkowy portalu.

### Przed przechowywaniem ważnych danych i publicznym uruchomieniem

- [ ] Kopie zapasowe bazy, przechowywanie poza VM i test odtworzenia.
- [ ] Kopia bazy przed migracjami zmieniającymi istotne dane.
- [ ] Konfiguracja produkcyjna: `APP_ENV=prod`, `APP_DEBUG=0`, osobny `APP_SECRET` i zależności bez pakietów developerskich.
- [ ] Ograniczenie prób logowania i przegląd zabezpieczeń sesji.
- [ ] Testy uprawnień, logowania, tworzenia kont i migracji.
- [ ] Wdrożenia przez katalogi wydań, przełączanie wersji i procedura rollbacku z uwzględnieniem bazy.
- [ ] Logi operacji administracyjnych i monitoring błędów oraz dostępności.
- [ ] Docelowy hosting, domena, HTTPS i przegląd dostępu sieciowego.

### Później

- [ ] Paginacja i wyszukiwanie użytkowników.
- [ ] Rozbudowa uprawnień pod konkretne moduły.
- [ ] CI do automatycznych kontroli kodu i testów; automatyczne wdrożenia po ustaleniu procedury.

## Szybka diagnostyka

| Objaw | Co sprawdzić |
|---|---|
| Brak połączenia z silnikiem Dockera na Windows | Czy Docker Desktop jest uruchomiony; `docker info` |
| Baza nie startuje | `docker compose logs --tail=50 db`, hasła w głównym `.env` |
| Hasło bazy przestało pasować po zmianie `.env` | Zmiana pliku nie aktualizuje hasła w już zainicjalizowanej bazie |
| Superadministrator dostaje 403 przy `/admin/users` | Rolę w bazie oraz `role_hierarchy` w głównej sekcji `security:` |
| Zwykły użytkownik dostaje 403 przy `/admin/users` | To oczekiwane działanie |
| Po zmianie konfiguracji aplikacja działa jak wcześniej | Wyczyść cache; po zmianie zmiennych kontenera wykonaj `docker compose up -d php` |
| SSH nie łączy z VM | Czy VM działa i czy jej adres IP się nie zmienił |
| Deploy zatrzymuje się przez lokalne zmiany | `git status` na VM; ustal pochodzenie zmian, zamiast usuwać je w ciemno |

