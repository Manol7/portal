#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/kamil/deploy/portal

exec 9>/home/kamil/deploy/portal-deploy.lock
flock -n 9 || {
    echo "Inne wdrożenie już trwa."
    exit 1
}

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Na VM są lokalne zmiany. Wdrożenie zatrzymane."
    git status --short
    exit 1
fi

echo "Pobieranie kodu..."
git pull --ff-only origin main

echo "Sprawdzanie konfiguracji Dockera..."
docker compose -p portal config --quiet

echo "Budowanie obrazu PHP..."
docker compose -p portal build php

echo "Uruchamianie bazy i oczekiwanie na gotowość..."
docker compose -p portal up -d db --wait --wait-timeout 180

echo "Instalowanie bibliotek..."
docker compose -p portal run --rm --no-deps php \
    composer install --no-interaction --prefer-dist

echo "Sprawdzanie konfiguracji i szablonów..."
docker compose -p portal run --rm --no-deps php \
    php bin/console lint:yaml config

docker compose -p portal run --rm --no-deps php \
    php bin/console lint:container

docker compose -p portal run --rm --no-deps php \
    php bin/console lint:twig templates

echo "Wykonywanie migracji..."
docker compose -p portal run --rm --no-deps php \
    php bin/console doctrine:migrations:migrate --no-interaction

echo "Odświeżanie cache..."
docker compose -p portal run --rm --no-deps php \
    php bin/console cache:clear

echo "Uruchamianie aplikacji..."
docker compose -p portal up -d --no-deps --force-recreate php caddy

echo "Sprawdzanie formularza logowania..."
for attempt in {1..15}; do
    if status=$(curl --silent --show-error --fail \
        --connect-timeout 3 --max-time 5 \
        --output /dev/null --write-out "%{http_code}" \
        http://localhost:8080/login) && [[ "$status" == "200" ]]; then
        echo "Wdrożono commit: $(git rev-parse --short HEAD)"
        exit 0
    fi
    sleep 2
done

echo "Formularz logowania nie odpowiedział kodem HTTP 200."
docker compose -p portal logs --tail=30 php caddy
exit 1