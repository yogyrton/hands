# ТЗ. Сайт + внутренняя система учёта массажной студии HANDS

**Версия:** 1.0 (консолидированная)
**Стек:** Laravel 12/13, Blade, обычный CSS (`resources/css/site.css`), Filament 3/4 (админка), PostgreSQL 16, Redis, Docker
**Город/адрес:** Могилёв, переулок Пожарный, 3Б
**Статус:** черновик к обсуждению и реализации

> Документ самодостаточен. По нему можно собрать проект целиком.
> Он объединяет два ранее раздельных документа: (1) дизайн-хэндофф публичного сайта
> и (2) диалог по внутренней системе учёта посещений/сертификатов/зарплат.

---

## 0. Ключевая архитектурная идея

Проект — это **одно Laravel-приложение с двумя подсистемами**:

| Подсистема | Кто пользуется | Где | Индексация |
|---|---|---|---|
| **Публичный сайт** | гости, клиенты | Blade, серверный рендеринг | да, максимальный SEO |
| **Админка / учёт** | администратор, мастера | Filament, `/admin` | закрыта от индексации (`robots`, guard) |

**Модели `Service` и `Master` — общие для обеих подсистем.** Не дублировать.
У услуги есть и «витринные» поля (slug, SEO, тексты для страницы), и учётное `base_price`.
У мастера — и профиль для сайта (bio, галерея, yclients_url), и признак сотрудника для учёта
(`is_active`, soft delete, опциональная привязка к учётной записи `User`).

Публичная часть **никогда** не обращается к YClients по API — только ссылки/виджет записи.
Учётная часть **тоже** не общается с YClients. Это радикально упрощает архитектуру: нет вебхуков,
синхронизации, риска расхождения данных.

---

## 1. Границы MVP

### Входит
- Публичный сайт: главная, страница услуги (1 шаблон на все), страница мастера (1 шаблон на всех).
- Управление контентом сайта через админку (услуги, мастера, FAQ, настройки студии).
- Учёт: посещения (оказанные услуги), сертификаты + история операций, отчёты по выручке и зарплатам.
- Роли: администратор, мастер.
- SEO-фундамент (см. раздел 9).

### НЕ входит в MVP (осознанно отложено)
- Интеграция с YClients по API (только ссылки/виджет записи на сайте).
- Касса (Альфа-Касса), фискализация, налоги/ФСЗН, бухгалтерские проводки.
- Модуль «Клиенты» (ФИО/телефоны храним НЕ мы — они в YClients).
- Отдельная сущность «Акции»/промокоды — заменена текстовым полем `discount_reason` у посещения
  (решение по последней итерации). Возможно добавить сущность `Promotion` позже без ломки.
- SMS/уведомления (они в YClients), мобильное приложение.

---

## 2. Модели данных

Условные обозначения: PK — первичный ключ, FK — внешний ключ, `nullable` — может быть пустым.

### 2.1. `Service` — услуга (таблица `services`)

Общая модель: витрина + учёт.

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| slug | string, unique | URL `/services/{slug}` (`classic`, `sport`, `relax`, `back`, `face`, `figure`) |
| name | string | «Классический массаж» |
| level | tinyint (1–5) | «Проработка N/5» |
| base_price | decimal(10,2) | базовая цена для учёта/зарплаты (подставляется в форму посещения) |
| duration_label | string | витринная подпись «от 60 мин» |
| price_label | string | витринная подпись «от 50 р» |
| lead | text | вводный абзац под H1 (и короткое описание в карточке главной) |
| ideal | string | «Идеально, если хочется: …» |
| request_lead | string | вопрос-подзаголовок блока «Работаем по запросу» |
| seo_title | string | `<title>` |
| seo_description | string | meta description |
| sort_order | int | порядок вывода |
| is_active | bool | активна ли |
| timestamps | | |

Повторяющиеся текстовые блоки — **JSON-колонки** (редактирование через Filament Repeater):
- **includes** — `Что входит`: список `{ n, title, description }` (обычно 5 шт).
- **requests** — `Работаем по запросу`: список строк-чипов (4–5 шт).
- **details** — `Подробно об услуге`: список `{ title, body }` (3 шт: «Как проходит сеанс»,
  «На чём делаем акцент», «Что вы почувствуете после»).

**Фото — через Spatie MediaLibrary**: коллекция `card` (для карточки на главной) и `hero`
(для шапки страницы услуги). Конверсии в webp — там же.

Готовый контент всех 6 услуг — см. `docs/design/design-handoff.md`, раздел 9.

### 2.2. `Master` — мастер (таблица `masters`)

Общая модель: профиль на сайте + сотрудник в учёте. **Soft delete** (увольнение не удаляет историю).

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| slug | string, unique | `/masters/{slug}` (`dmitriy`, `anna`, `andrey`) |
| name | string | «Дмитрий» |
| name_dative | string | «Дмитрию» (для «Записаться к …») |
| role | string | «Массажист · спортивный и классический массаж» |
| yclients_url | string | персональная ссылка записи |
| experience_label | string | «8 лет» |
| bio1 | text | 1-й абзац |
| bio2 | text | 2-й абзац |
| sort_order | int | порядок показа (в карточках главной и в выборе) |
| is_active | bool | работает ли сейчас (для показа в списках выбора) |
| deleted_at | timestamp nullable | soft delete (увольнение) |
| timestamps | | |

**Фото — через Spatie MediaLibrary** (не строковые пути):
- коллекция `main` (single) — главное фото: идёт на карточку главной страницы и в шапку страницы мастера;
- коллекция `gallery` (3 фото) — показываются все 3 на странице мастера.
- В админке для каждого мастера: загрузка 1 главного + 3 фото галереи. Конверсии в webp — там же.

Связанные:
- **principles** — «Подход в работе»: список `{ title, description }` (3 шт). **JSON-колонка** (Filament Repeater).
- **services** — many-to-many со `Service` (какие услуги оказывает; блок «Услуги мастера»).

Правило увольнения: `is_active=false` + `deleted_at=now()`. Восстановление: обратно. Уволенный мастер
не показывается в выборе при создании посещений, но остаётся в отчётах и истории сертификатов.

Готовый контент 3 мастеров — см. `docs/design/design-handoff.md`, раздел 10.

### 2.3. `Faq` — вопрос-ответ главной (таблица `faqs`)

| Поле | Тип |
|---|---|
| id | bigint PK |
| question | string |
| answer | text |
| sort_order | int |
| is_active | bool |

6 готовых вопросов — см. дизайн-хэндофф, раздел 11 (блок FAQ).

### 2.4. Настройки студии (глобальные)

Таблица `settings` (key-value) **или** `config/studio.php`. Значения:
- `phone`
- `address` — «переулок Пожарный, 3Б, Могилёв»
- `instagram_url` — `https://www.instagram.com/hands.mg/`
- `yclients_main` — `https://n1865142.yclients.com`
- `yandex_map_embed` — URL iframe Яндекс-карты (координаты уточнить, сейчас примерные)
- `gift_min_delivery` — «400 р»

Рекомендация: сделать редактируемыми в админке (страница Filament Settings) — заказчик просил
«менять на главной ссылки yclients и прочее».

### 2.5. `User` — учётная запись (таблица `users`, стандартная Laravel)

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string, unique | вход в `/admin` |
| password | string | |
| role | enum(`admin`,`master`) | простой enum, без пакета прав (для 2 ролей достаточно) |

**Логин один общий** (решение заказчика): все мастера работают под одной учётной записью с
ролью `master`; при создании посещения мастер вручную выбирает себя из списка. Отдельной привязки
`User` ↔ `Master` не нужно. Плюс отдельный аккаунт администратора с ролью `admin`.

### 2.6. `Visit` — посещение (таблица `visits`) — центральная сущность учёта

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| master_id | bigint FK | кто оказал |
| service_id | bigint FK | какая услуга |
| base_price | decimal(10,2) | цена по прайсу (подставляется из услуги, редактируется вручную) |
| service_price | decimal(10,2) | **итоговая стоимость услуги** после скидки → база для зарплаты |
| paid_amount | decimal(10,2) | **фактически получено деньгами** (наличные/карта); может быть 0, если сертификат |
| payment_type | enum(`cash`,`card`,`mixed`,`certificate`) | как оплачено (доплата при сертификате тоже) |
| discount_reason | string nullable | текст скидки: «Ранняя пташка −10%», «Постоянный клиент −5 р» |
| certificate_id | bigint FK nullable | если использован сертификат |
| comment | text nullable | комментарий мастера |
| performed_at | datetime | когда оказана услуга (по умолчанию now) |
| timestamps | | |

**Ключевые правила:**
- Зарплата мастера считается от **`service_price`** (услуга оказана независимо от способа оплаты).
- Выручка кассы считается от **`paid_amount`** (реально пришедшие деньги).
- `service_price` может быть 0 (полностью покрыт сертификатом на посещения), но `base_price`
  сохраняем всегда — иначе зарплата обнулится.
- Валидация: `service_price >= 0` (не `> 0`, т.к. возможна оплата сертификатом).

### 2.7. `Certificate` — сертификат (таблица `certificates`)

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| number | string, unique | генерируется автоматически |
| type | enum(`visits`,`money`) | по количеству посещений или на сумму |
| initial_visits | int nullable | если `visits` (напр. 5) |
| initial_amount | decimal(10,2) nullable | если `money` (напр. 100) |
| remaining_visits | int nullable | остаток посещений (кеш для скорости) |
| remaining_amount | decimal(10,2) nullable | остаток суммы (кеш для скорости) |
| sold_at | date | дата продажи |
| expires_at | date | **всегда `sold_at + 3 месяца`** |
| status | enum(`active`,`used`,`expired`) | вычисляется/проставляется |
| timestamps | | |

`remaining_*` — кеш ради скорости. **Источник правды — таблица операций** (2.8): остаток всегда
можно пересчитать суммой операций.

### 2.8. `CertificateOperation` — история сертификата (таблица `certificate_operations`)

| Поле | Тип | Назначение |
|---|---|---|
| id | bigint PK | |
| certificate_id | bigint FK | |
| visit_id | bigint FK nullable | ссылка на посещение (для `usage` — обязательна) |
| type | enum(`sale`,`usage`,`correction`) | продажа / списание / корректировка |
| amount | decimal(10,2) | для `money`: например −40; для `visits`: −1 |
| created_at | timestamp | |

Зачем: если клиент спорит («был 4 раза, а не 5») — открываем операции сертификата и через связь
`operation.visit` показываем дату/время/мастера/услугу. Достаточно хранить `visit_id`, остальное
получаем через связи:
`$op->visit->service->name`, `$op->visit->master->name`, `$op->visit->performed_at`, `$op->visit->service_price`.

### 2.9. Диаграмма связей (текстом)

```
User (1)───(0..1) Master (N)───(M) Service
                    │                 │
                    │ (1)             │ (1)
                    ▼                 ▼
                  Visit ◄────────── Visit
                    │ (0..1)
                    ▼
              Certificate (1)───(N) CertificateOperation
                                          │ (N)
                                          └──(0..1)──► Visit
```

---

## 2A. Архитектура: слои, DTO и DI-биндинг (конвенция проекта)

Весь код следует слоёному паттерну проекта. Бизнес-логика **не** живёт в контроллерах и
Filament-ресурсах — только в сервисах.

```
Blade-контроллер / Filament ─▶ Service (через интерфейс) ─▶ Repository (через интерфейс) ─▶ Eloquent
                                     ▲                              ▲
                                DTO (Spatie\LaravelData\Data)  Builder<TModel>
```

**Базовый слой (уже есть у заказчика):**
- `App\Contracts\Repositories\BaseQueryRepositoryInterface<TModel>` / `App\Repositories\BaseQueryRepository` — generic CRUD (`all/find/create/update/delete/paginate`), абстрактный `query(): Builder<TModel>`.
- `App\Contracts\Services\BaseQueryServiceInterface<TModel>` / `App\Services\BaseQueryService` — обёртка над репозиторием (constructor injection интерфейса).
- DTO — классы `Spatie\LaravelData\Data`, передаются в `create/update`.
- Биндинг интерфейс → реализация — в `App\Providers\DIServiceProvider` (`registerServices()`, `registerRepositories()`).
- Стиль: `declare(strict_types=1)`, PHP 8.4 (`#[Override]`), Pint, дженерик-докблоки для Larastan.

**Артефакты на каждую доменную модель `X`** (по образцу `User`/`UserServiceInterface`):
1. `App\Models\X` — Eloquent (+ трейты: `SoftDeletes` где нужно, `InteractsWithMedia` для фото).
2. `App\Data\XData` — DTO (LaravelData) для create/update.
3. `App\Contracts\Repositories\XRepositoryInterface extends BaseQueryRepositoryInterface<X>`.
4. `App\Repositories\XRepository extends BaseQueryRepository implements XRepositoryInterface` (реализует `query()`).
5. `App\Contracts\Services\XServiceInterface extends BaseQueryServiceInterface<X>` (+ доменные методы).
6. `App\Services\XService` — реализация (чистый CRUD может переиспользовать `BaseQueryService`).
7. Регистрация обеих пар в `DIServiceProvider`.

**Пример (модель `Service`):**
```php
// App\Contracts\Services\ServiceServiceInterface
interface ServiceServiceInterface extends BaseQueryServiceInterface {} // @extends ...<Service>
// App\Repositories\ServiceRepository
public function query(): Builder { return Service::query(); }
// DIServiceProvider::registerRepositories()
$this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
// DIServiceProvider::registerServices()
$this->app->bind(ServiceServiceInterface::class, fn ($app) =>
    new BaseQueryService($app->make(ServiceRepositoryInterface::class)));
```

**Доменные сервисы (не CRUD — отдельные методы-команды):**
- `VisitServiceInterface::register(VisitData $data): Visit` — в `DB::transaction` + `lockForUpdate`
  на сертификате: создаёт `Visit`, при сертификате пишет `CertificateOperation(usage)` и обновляет
  остаток/статус. Вызывается из Filament (обработка формы/кастомный Action), **не** из тела ресурса.
- `CertificateServiceInterface::issue(CertificateData $data): Certificate` — генерит номер,
  `expires_at = sold_at + 3 мес`, `status=active`, пишет `CertificateOperation(sale)`.

**Где что использует слой:**
- Публичные контроллеры (`HomeController`/`ServiceController`/`MasterController`) — только чтение,
  через `*ServiceInterface` (`all()`/`find()` с активными записями).
- Filament: простой CRUD справочников — идиоматично, напрямую с Eloquent; **доменные операции**
  (регистрация посещения, выпуск/списание сертификата) — только через доменные сервисы.

**Пакеты к установке (в `composer.json` их ещё нет):**
`spatie/laravel-data`, `spatie/laravel-medialibrary` (+ `intervention/image` для webp),
`filament/filament`. Поднять `php` до `^8.4`, если используете `#[Override]`-стиль повсеместно.

---

## 3. Роли и доступ

| Возможность | Администратор | Мастер |
|---|---|---|
| Справочники Услуги (CRUD) | ✅ | 👁 только просмотр |
| Справочник Мастера (CRUD) | ✅ | 👁 только просмотр |
| Контент сайта (услуги/мастера/FAQ/настройки) | ✅ | ❌ |
| Посещения — создание/просмотр | ✅ (все) | ✅ создание + просмотр **всех** |
| Сертификаты — создание/использование/история | ✅ | ✅ |
| Отчёты: выручка, зарплаты | ✅ | ❌ (только админ) |

Реализация в Filament: `Panel::authGuard()` + Policy на каждый Resource. Так как логин общий и
мастер видит все посещения, фильтрация посещений по мастеру **не нужна** — Policy решает только
доступ к контенту сайта, настройкам и отчётам (`$user->role === 'admin'`).

---

## 4. Публичный сайт

### 4.1. Роуты (ЧПУ для SEO)

```php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/masters/{master:slug}', [MasterController::class, 'show'])->name('masters.show');
```

Контроллеры отдают только `is_active`, отсортированные по `sort_order`. На главной — коллекции
`services`, `masters`, `faqs` + настройки студии.

### 4.2. Структура Blade

```
resources/views/
  layouts/app.blade.php        — <head>, шрифты, site.css, sticky-хедер, футер, @yield('content')
  home.blade.php               — секции главной
  services/show.blade.php      — шаблон услуги (данные из $service)
  masters/show.blade.php       — шаблон мастера (данные из $master)
  partials/
    header.blade.php  footer.blade.php
    home/hero.blade.php  home/services.blade.php  home/about.blade.php
    home/masters.blade.php  home/gift.blade.php  home/map.blade.php
    home/booking.blade.php  home/faq.blade.php
  components/
    service-card.blade.php   master-card.blade.php   (опционально)
```

`sc-for` в референсах = `@foreach`. Все ссылки — через `route()`.

### 4.3. Страницы и блоки

**Главная** (`home.blade.php`), секции по порядку:
1. Hero — слоган «В наших руках — *ваше удовольствие*» (курсив + бронза `#A97C50`), 2 CTA
   (Записаться → YClients, Наши услуги → якорь), статы (6 видов · от 60′ · 3 мастера).
2. Полоса «Только по записи» (тёмная).
3. Услуги — заголовок «Выберите свой массаж» + 6 карточек (фото, «Проработка N/5», название,
   короткое описание = `lead`, мета, «Подробнее →» → страница услуги).
4. О студии — «Место, где тело наконец выдыхает» + фото.
5. Мастера — «Те, кому доверяют тело» + 3 карточки → страницы мастеров.
6. Подарочные сертификаты (id `gift`) — визуал карточки-сертификата, CTA → Instagram.
7. Карта + контакты (id `map`, тёмная) — iframe Яндекс-карты, адрес/запись/инстаграм.
8. Форма записи (id `booking`) — Имя, Телефон, Услуга (select). Submit → редирект на `yclients_main`.
   Форма **пока не пишет в БД** (антиспам/сохранение заявок — опционально позже).
9. FAQ (id `faq`) — нативные `<details>` (без JS), 6 вопросов.
10. Футер.

**Страница услуги** (`services/show.blade.php`) по `$service`: breadcrumb, hero (name/level/lead/
статы/CTA/фото), «Что входит» (includes), «Работаем по запросу» (request_lead + requests-чипы),
«Подробно об услуге» (details) + сайдбар «Идеально, если хочется» (ideal) + «Другие услуги».

**Страница мастера** (`masters/show.blade.php`) по `$master`: breadcrumb, hero (портрет/name/role/
bio1/bio2/статы/CTA «Записаться к {name_dative}»), «Подход в работе» (principles), «Услуги мастера»
(m2m services → карточки-ссылки), «Фотографии» (gallery), booking-CTA, футер.

Вёрстка — high-fidelity по токенам (раздел 8 дизайн-хэндоффа). Референсы: `docs/design/*.dc.html`
(это НЕ продакшн-код, а прототипы вида; воссоздать в Blade).

---

## 5. Админка (Filament)

### 5.1. Навигация

Группа **«Сайт»** (только админ):
- Услуги (ServiceResource) — CRUD + загрузка `photo_hero`/`photo_card`, Repeater для includes/
  requests/details, управление slug/sort_order/is_active/SEO.
- Мастера (MasterResource) — CRUD + фото (1 основное + галерея), Repeater principles, m2m услуги,
  yclients_url, soft delete.
- FAQ (FaqResource) — CRUD.
- Настройки студии — страница Filament (телефон, адрес, ссылки YClients/Instagram, embed карты, gift).

Группа **«Учёт»**:
- Посещения (VisitResource) — список с фильтрами (мастер, услуга, период) + форма создания (5.2).
- Сертификаты (CertificateResource) — список, создание, RelationManager «Операции» (история).
- Отчёты — страницы: Дашборд выручки, Зарплаты по мастерам (раздел 7).

Мастеру справочники Услуги/Мастера доступны read-only; контент сайта и настройки — скрыты.

### 5.2. Форма создания посещения (главный экран мастера, цель — 5–10 секунд)

Поля и поведение:
1. **Мастер** — выбор из активных (обязателен: логин общий, поэтому мастер всегда выбирает себя).
2. **Услуга** — выбор из активных.
3. **Базовая стоимость** — автоподстановка `service.base_price`, **редактируется вручную**
   (скидки/акции/договорённости).
4. **Итоговая стоимость (`service_price`)** — по умолчанию = базовой; мастер вводит фактическую.
5. **Скидка / особые условия (`discount_reason`)** — текст, опционально.
6. **Оплата сертификатом** — чекбокс. Если включён → появляется выбор сертификата.
   - В списке только сертификаты `status=active` и `expires_at >= today` с остатком.
   - Логика по типу:
     - **visits**: списывается 1 посещение; `paid_amount=0`, `payment_type=certificate`.
     - **money, хватает**: списывается `service_price`; `paid_amount=0`, `payment_type=certificate`.
     - **money, не хватает**: показать «Не хватает X р. Требуется доплата». Поле «Сумма доплаты»
       = `service_price − remaining_amount` (автозаполнение), `payment_type` (наличные/карта)
       становится обязательным; `paid_amount` = сумма доплаты.
7. **Комментарий** — опционально.

При сохранении — в транзакции (`DB::transaction` + `lockForUpdate` на сертификате):
1. создать `Visit`;
2. если использован сертификат — создать `CertificateOperation(type=usage, visit_id, amount)`;
3. обновить `remaining_visits`/`remaining_amount`; при исчерпании — `status=used`.

### 5.3. Форма создания сертификата

Поля: тип (visits/money), количество ИЛИ сумма, дата продажи (по умолчанию сегодня).
Автоматически: `number` (генерация), `expires_at = sold_at + 3 месяца`, `status=active`,
`remaining_* = initial_*`, запись `CertificateOperation(type=sale, amount=+initial)`.

---

## 6. Бизнес-логика: сроки и статусы сертификатов

- Срок действия — **3 месяца с даты продажи** (`sold_at`), фиксируется в `expires_at` при создании.
- Статус:
  - `active` — есть остаток и `expires_at >= today`;
  - `used` — остаток исчерпан;
  - `expired` — `expires_at < today` (проставлять командой/скедьюлером ежедневно либо вычислять на лету).
- В выборе при создании посещения — только `active` и не истёкшие.
- Остаток — всегда сверяем/пересчитываем через сумму `certificate_operations`.

---

## 7. Отчёты

### 7.1. Дашборд выручки
- Выручка за сегодня = `SUM(paid_amount)` за день; разбивка наличные/карта.
- Кол-во посещений за сегодня.
- Продажи сертификатов за месяц (кол-во и сумма `initial_*` по `sale`-операциям).

### 7.2. Зарплаты по мастерам
- Фильтр периода: день / неделя / месяц / произвольный.
- Таблица: Мастер · Кол-во посещений · Сумма услуг (`SUM(service_price)`) · Зарплата (`× 0.35`).
- Формула зарплаты вынести в конфиг/сервис (`config('studio.salary_rate', 0.35)`) — легко менять.

---

## 8. Дизайн-токены (кратко; полностью — в дизайн-хэндоффе)

- **Цвета:** фон `#E9E3D8`, панель `#DFD7C9`, ink/тёмное `#241C16`, подложка фото `#1C1512`,
  текст body `#5A4E43`, muted `#6B5E52`/`#7A6E60`, акцент бронза `#A97C50`, hover `#8A6238`,
  золото на тёмном `#C39B6C`, светлое золото `#E7C79B`.
- **Шрифты (Google Fonts):** Cormorant Garamond (заголовки/цифры), Manrope (тело/навигация/кнопки).
- **Логотип HANDS:** Manrope 300, `letter-spacing:.5em`.
- **Радиусы:** карточки 16px, крупные блоки 20–24px, кнопки/пилюли 100px.
- **Кнопка primary:** фон `#241C16`, текст `#F1EADD`, паддинг 16×32, радиус 100px.
- **Хедер:** sticky, `rgba(233,227,216,.82)` + `backdrop-filter: blur(14px)`.

---

## 9. SEO (полный чек-лист)

- Серверный рендеринг всех публичных страниц (Blade, без SPA).
- `<title>` / `meta description` из полей модели (`seo_title`, `seo_description`); Open Graph + Twitter Cards.
  Рекомендация: свой Blade-компонент `<x-seo>` или пакет `artesaos/seotools`.
- H1: «{Услуга} в Могилёве» / «{Имя} — мастер студии HANDS».
- **JSON-LD** структурированные данные:
  - `LocalBusiness` / `HealthAndBeautyBusiness` (адрес, гео, часы, телефон) — на всех страницах;
  - `Service` — на странице каждой услуги;
  - `FAQPage` — на главной (блок FAQ);
  - `AggregateRating` — если появятся отзывы.
- ЧПУ: `/services/{slug}`, `/masters/{slug}` (уже заложено).
- `sitemap.xml` (`spatie/laravel-sitemap`) + `robots.txt` (закрыть `/admin`).
- Оптимизация изображений: webp, `loading="lazy"`, корректные `alt`, правильные размеры (раздел 10).
  Рекомендация: `spatie/laravel-medialibrary` + `intervention/image` (конверсия в webp).
- **Google Search Console** и **Яндекс.Вебмастер** + **Яндекс.Метрика** (для РФ/РБ Яндекс критичен).
- Отложенная загрузка виджета YClients (`async`/`defer`), чтобы не тормозил рендер.
- Карточки в Яндекс.Картах / 2ГИС (вне сайта, но влияет на локальный SEO).
- Core Web Vitals: без тяжёлого JS, шрифты через `preconnect` + `display=swap`.

---

## 10. Интеграции и ассеты

### YClients (только запись, без API)
- Общая ссылка: `https://n1865142.yclients.com`
- Дмитрий: `https://n2124342.yclients.com` · Анна: `https://n2124346.yclients.com` · Андрей: `https://n2124340.yclients.com`
- Все внешние ссылки: `target="_blank" rel="noopener"`.
- Форма записи: submit → `window.open(yclients_main, '_blank', 'noopener')` (3 строки JS) либо серверный redirect.

### Яндекс-карта
- iframe map-widget без API-ключа. Координаты `ll` уточнить (сейчас примерные по городу). URL хранить в настройках.
- Контейнер: высота ≥ 460px, `filter: grayscale(.15)`.

### Фото (object-fit: cover), рекомендуемые размеры
| Где | Пропорции | Размер |
|---|---|---|
| Hero главной (правая панель) | 4:5 | 1200×1500 |
| «О студии» (левое фото) | 3:4 | 1000×1333 |
| Карточка услуги (главная) `photo_card` | 3:4 | 800×1067 |
| Шапка услуги `photo_hero` | ~5:6 | 900×1080 |
| Портрет мастера `photo_hero` | 3:4 | 900×1200 |
| Галерея мастера (3 фото) | ~7:6 | 840×720 |
| Фон карточки-сертификата | 16:10 | 1200×750 |

Временные фото — в дизайн-пакете (`assets/`), для продакшна заменить оригиналами. Фото мастеров предоставит заказчик.

---

## 11. Порядок реализации

**Фаза 0 — пакеты и базовый слой**
0. Установить `spatie/laravel-data`, `spatie/laravel-medialibrary` (+ `intervention/image`),
   `filament/filament`; поднять php до `^8.4`. Убедиться, что базовый слой (раздел 2A) на месте:
   `BaseQueryRepository/Service` + контракты + `DIServiceProvider`.

**Фаза 1 — фундамент**
1. Layout + `site.css` (токены, шрифты, базовые компоненты: кнопка, eyebrow, карточка, секция).
2. На каждую модель `Service`/`Master`/`Faq` — полный набор по разделу 2A: Model (+ MediaLibrary/
   SoftDeletes), `Data`-DTO, Repository (+ интерфейс), Service (+ интерфейс), биндинг в
   `DIServiceProvider`. Миграции + сидеры (контент из дизайн-хэндоффа).
3. Роуты + контроллеры (читают через `*ServiceInterface`). Blade: header/footer → главная → услуга → мастер.
4. Форма записи (redirect YClients), карта (настройки), FAQ (`<details>`).

**Фаза 2 — админка контента**
5. Filament: панель, роли (admin/master), guard.
6. Ресурсы Service/Master/Faq + загрузка фото + Repeater'ы + slug/sort/active/SEO + страница Настроек.

**Фаза 3 — учёт**
7. Модели/миграции: User.role, Visit, Certificate, CertificateOperation + слои (2A):
   DTO/Repository/Service на каждую. Доменные сервисы `VisitService::register()`,
   `CertificateService::issue()`. Policy по ролям.
8. VisitResource + форма создания посещения — вызывает `VisitServiceInterface::register()`
   (вся логика сертификатов и транзакция внутри сервиса, не в ресурсе).
9. CertificateResource + RelationManager «Операции».
10. Отчёты: дашборд выручки, зарплаты по мастерам.

**Фаза 4 — SEO и полировка**
11. `<x-seo>`, JSON-LD (LocalBusiness/Service/FAQPage), sitemap, robots, webp/lazy/alt.
12. Search Console / Яндекс.Вебмастер / Метрика.

---

## 12. Решения и оставшиеся вопросы

**Согласовано (v1.0):**
1. Мастер видит **все** посещения (фильтрация по мастеру не нужна).
2. Логин **один общий** для мастеров; мастер выбирает себя при создании посещения. + отдельный админ.
3. Текстовые блоки (`includes/requests/details/principles`) — **JSON-колонки** (Filament Repeater).
   Фото — **Spatie MediaLibrary** (услуга: `card`+`hero`; мастер: `main`+`gallery`×3), конверсии в webp.
4. Настройки студии — **таблица `settings` + экран в админке**.

**Осталось уточнить (не блокирует старт):**
5. Точные координаты студии для Яндекс-карты (сейчас примерные).
6. Форма записи — только редирект в YClients или **ещё сохранять заявку** в БД? (MVP: только редирект)
7. Ставка зарплаты 35% фиксирована глобально или может отличаться по мастеру/услуге? (MVP: глобально в конфиге)
