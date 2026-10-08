#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/kamil/deploy/portal

# Blokada zapobiega równoczesnemu uruchomieniu dwóch wdrożeń.
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

echo "Budowanie obrazu PHP..."
docker compose -p portal build php

echo "Instalowanie bibliotek..."
docker compose -p portal run --rm --no-deps php \
    composer install --no-interaction --prefer-dist

echo "Sprawdzanie konfiguracji i szablonów..."
docker compose -p portal run --rm --no-deps php \
    php bin/console lint:container

docker compose -p portal run --rm --no-deps php \
    php bin/console lint:twig templates

echo "Odświeżanie cache..."
docker compose -p portal run --rm --no-deps php \
    php bin/console cache:clear

echo "Uruchamianie aplikacji..."
docker compose -p portal up -d --force-recreate

echo "Sprawdzanie odpowiedzi strony..."
for attempt in {1..15}; do
    if curl --fail --silent --output /dev/null http://localhost:8080/; then
        echo "Wdrożono commit: $(git rev-parse --short HEAD)"
        exit 0
    fi
    sleep 2
done

echo "Strona nie odpowiedziała poprawnie po wdrożeniu."
docker compose -p portal logs --tail=30
exit 1