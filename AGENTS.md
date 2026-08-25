# AGENTS.md

Инструкции для агентов, которые меняют этот репозиторий. Документ для людей — `README.md`.

## Что это

PHP API сервиса YT-Kachalka (поиск и в будущем скачивание YouTube-видео). Фронт — соседний `yt-kachalka-frontend`, общая сборка — родительский `yt-kachalka-app`.

Стек: PHP 8.4, Symfony 8 MicroKernel, PHP-FPM + Nginx. Точка входа — `index.php`. PSR-4: `App\` → `src/`.

## Слои — куда класть код

Соблюдай границы слоёв. Новую фичу раскладывай так же, как Search.

| Слой | Путь | Можно | Нельзя |
| --- | --- | --- | --- |
| Domain | `src/Domain/{Feature}/` | Сущности, value objects, интерфейсы сервисов | Symfony, HTTP, Google Client, Doctrine-аннотации «для удобства» |
| Application | `src/Application/{Feature}/` | Сценарии, реализация доменных интерфейсов, маппинг во внешний JSON | Контроллеры, знание Request/Response |
| Infrastructure | `src/Infrastructure/{Feature}/` | Клиенты API, адаптеры, файловая система, БД | Бизнес-правила |
| Presentation | `src/Presentation/` | Kernel, контроллеры, HTTP-атрибуты | Прямые вызовы YouTube/Google Client |

Текущий поток поиска (эталон):

```
Presentation/Controllers/SearchController
  → Domain/Search/Service/SearchServiceInterface
    → Application/Search/Service/SearchService
      → Infrastructure/Search/Client/YoutubeDataApiClient
        → Domain/Search/Entity/SearchModel
```

Интерфейс живёт в Domain, реализация — в Application. Связка в `config/services.yaml`:

```yaml
App\Domain\Search\Service\SearchServiceInterface: '@App\Application\Search\Service\SearchService'
```

Клиенты с невыводимыми аргументами (API-ключ) описывай явно:

```yaml
App\Infrastructure\Search\Client\YoutubeDataApiClient:
    arguments:
        $apiKey: '%env(YOUTUBE_API_KEY)%'
```

Не тащи инфраструктурные типы в Domain. Не обходи интерфейс и не вызывай `YoutubeDataApiClient` из контроллера.

## Стиль PHP

- PHP 8.4+: constructor promotion, `readonly`, типизированные свойства и возвраты.
- Контроллеры: атрибут `#[Route]`, query — `#[MapQueryParameter]`.
- Ответы API — JSON (`JsonResponse` или `json_encode(..., JSON_THROW_ON_ERROR)`).
- Имена классов и методов — английский. Комментарии — только если без них неочевидно «почему».
- Не используй устаревший `FILTER_SANITIZE_STRING`.
- Namespace = путь PSR-4. Файл `src/Presentation/Foo.php` → `namespace App\Presentation`.

## Symfony и DI

- Сервисы: autowire + autoconfigure в `config/services.yaml`. Kernel из `App\` исключай (`exclude`), чтобы его не регистрировали как сервис.
- Бандлы — `config/bundles.php`. Пакетный конфиг — `config/packages/`. Маршруты контроллеров — `config/routes.yaml` (`resource: routing.controllers`).
- `index.php` должен создавать тот Kernel, который реально автолоадится (класс и путь совпадают).
- `bin/console` в репозитории нет — не опирайся на него, пока его не добавят.
- API Platform и Doctrine уже в зависимостях, но API Resource и маппинг сущностей не используются. Не подключай их «заодно», если задача этого не требует.
- `security.yaml` ссылается на несуществующий `App\Entity\Admin`. Не чини это побочным рефакторингом.

## HTTP-контракт

Ломай контракт только если об этом прямо попросили.

- `GET /` → `{"hello":"world"}`.
- `GET /search?search={query}` → JSON-массив объектов `SearchModel`:
  `videoId`, `title`, `description`, `thumbnailUrl`, `channelTitle`, `publishedAt`.
- Пустой query → `[]`, не ошибка.
- Поиск: `type=video`, по умолчанию до 25 результатов.

Контроллер поиска ещё не дописан (пустой action, нет `use` на интерфейс). Дописывая его: зови `SearchServiceInterface::search()`, верни JSON, не ходи в клиент YouTube напрямую.

Скачивание с фронта задумано как `POST` с телом `{"url": "..."}`. Эндпоинта пока нет; `norkunas/youtube-dl-php` в `composer.json` не используется. Не реализуй скачивание, пока не попросили.

## Docker и окружение

- Локально в этом репо: `docker-compose.yaml`, API на `:8080`, контейнеры `php-app` / `nginx-app`.
- Вместе с фронтом: родительский compose, API на `:8000`.
- PHP-образ: `docker/api/Dockerfile` (`php:8.4-fpm`). Entrypoint чинит права на `var/`.
- Nginx: `docker/nginx/nginx.conf`, `fastcgi_pass api:9000`, корень — `/var/www/html` (нужен `index.php` в корне проекта, не только `src/`).
- Секреты: `.env.example` → `.env`. Нужны `APP_SECRET` и `YOUTUBE_API_KEY`. Не коммить `.env` и ключи.
- `DATABASE_URL` есть в Doctrine-конфиге, сервиса БД в compose нет.

## Что не делать

- Не добавляй README/доки, которые не просили, и не раздувай `composer.json` без нужды.
- Не переноси фичи в другой слой «для красоты» и не вводи новую корневую структуру (`src/Entity`, `src/Controller`), пока проект явно слоистый.
- Не коммить `vendor/`, `var/`.
- Тестов и PHPStan/CS-fixer в репо нет — не подключай пайплайн, пока не попросили. Если пишешь тесты по задаче, клади их отдельно и не ломай слои ради удобства моков.
- Не «доделывай» соседние сломанные места (Kernel, security, nginx volumes), если задача про другое.

## Kernel (текущее состояние)

Файл переехал в `src/Presentation/Kernel.php`, но класс всё ещё `App\Infrastructure\Kernel`. `index.php` создаёт `App\Infrastructure\Kernel`, exclude в `services.yaml` указывает на старый путь `src/Infrastructure/Kernel.php`. PSR-4 из-за этого расходится с диском.

Если трогаешь Kernel — выровняй **путь, namespace, `index.php` и exclude** одним изменением. Иначе не трогай.

## Чеклист перед сдачей изменения

1. Класс в правильном слое, namespace = путь.
2. Новые внешние зависимости спрятаны за интерфейсом Domain/Application.
3. HTTP-контракт поиска/hello не сломан без явной просьбы.
4. Секреты только через `%env(...)%` / `.env`, не в коде.
5. Правки минимальные: без рефакторинга соседних недоделок.

## Agent skills

### Issue tracker

Issues live in GitHub Issues for `lewddreamz/yt-kachalka-api` (`gh` CLI). See `docs/agents/issue-tracker.md`.

### Triage labels

Canonical roles mapped 1:1: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `CONTEXT.md` and `docs/adr/` at the repo root. See `docs/agents/domain.md`.
