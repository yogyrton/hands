#!/bin/sh
#
# webdevops запускает скрипты из /opt/docker/provision/entrypoint.d/ при старте
# контейнера (под root, до переключения на пользователя application).
#
# Из-за bind-mount (./:/app) права из образа перекрываются правами хоста, и
# php-fpm (пользователь application) не может писать в storage — падает загрузка
# файлов (Livewire temp) и сохранение медиа. Здесь гарантируем нужные каталоги и
# делаем их доступными на запись.

set -e

mkdir -p \
    /app/storage/app/private \
    /app/storage/app/public \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/framework/testing \
    /app/storage/logs \
    /app/bootstrap/cache

chmod -R 0777 /app/storage /app/bootstrap/cache 2>/dev/null || true
