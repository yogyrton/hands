# HANDS — Фаза 1 (публичный сайт). Установка

Этот архив — оверлей поверх твоего проекта `hands2` (ветка `develop`). Распакуй в корень
проекта, файлы лягут по своим папкам. Что внутри: слоёная архитектура (Contracts/Repositories/
Services + DTO), модели `Service`/`Master`/`Faq`/`Setting`, миграции, сидеры с готовым контентом,
layout + `public/css/site.css`, публичные страницы (главная, услуга, мастер).

## Шаги

```bash
# 1) зависимости (в composer.json уже добавлены spatie/laravel-data и spatie/laravel-medialibrary)
composer install

# 2) окружение (если ещё не настроено)
cp .env.example .env
php artisan key:generate

# 3) БД — у тебя Postgres в docker-compose; подставь свои DB_* в .env, затем:
php artisan migrate --seed
```

Готово. Открой главную — `/`, услугу — `/services/classic`, мастера — `/masters/dmitriy`.

## Что важно знать

- **CSS без сборки.** `public/css/site.css` подключается напрямую (`asset('css/site.css')`),
  Vite/npm не нужны. Правь этот файл.
- **Фото — через Spatie MediaLibrary.** Сейчас фото нет (заглушки-градиенты). Загрузка появится
  в админке (Фаза 2). Коллекции: услуга — `card`, `hero`; мастер — `main`, `gallery`.
  Миграция `create_media_table` уже включена в архив.
- **Настройки студии** (ссылки YClients/Instagram, embed карты, gift) — в таблице `settings`,
  засеяны дефолтами, доступны во всех шаблонах как `$studio['ключ']`. Редактирование — в админке (Фаза 2).
- **Только активные записи** отдаются на сайт; неактивный slug → 404.
- **Биндинги** контрактов — в `app/Providers/DIServiceProvider.php` (зарегистрирован в `bootstrap/providers.php`).

## Проверено

`composer install` + `migrate --seed` + `php artisan serve`: `/` → 200, `/services/classic` → 200,
`/masters/dmitriy` → 200, неизвестный slug → 404. Pint (дефолтный пресет) — чисто. Все PHP-файлы — `php -l` ok.

## Дальше (не входит в Фазу 1)

Фаза 2 — админка Filament (CRUD услуг/мастеров/FAQ, загрузка фото, экран настроек).
Фаза 3 — учёт: посещения, сертификаты, отчёты (см. `docs/tz-hands.md`).
