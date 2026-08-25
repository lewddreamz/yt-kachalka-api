# yt-kachalka-api

Backend API сервиса **YT-Kachalka**: поиск роликов на YouTube и (в перспективе) скачивание видео.

Фронтенд живёт в соседнем репозитории `yt-kachalka-frontend`. Вместе они собираются из родительского каталога `yt-kachalka-app`.

## Стек

| Компонент | Версия / заметка |
| --- | --- |
| PHP | 8.4 (образ `php:8.4-fpm`) |
| Symfony | 8.x (`symfony/framework-bundle`) |
| HTTP | PHP-FPM + Nginx |
| YouTube Data API | `google/apiclient` |
| Скачивание (пока не используется) | `norkunas/youtube-dl-php` |
| ORM | Doctrine (подключён, доменных сущностей пока нет) |

## Архитектура

Код разложен по слоям (DDD / hexagonal):

```
src/
├── Domain/            # сущности и контракты, без Symfony и HTTP
├── Application/       # сценарии использования
├── Infrastructure/    # внешние клиенты (YouTube Data API и т.п.)
└── Presentation/      # HTTP: Kernel, контроллеры
```

Поток поиска:

`SearchController` → `SearchServiceInterface` → `SearchService` → `YoutubeDataApiClient` → `SearchModel`

## Быстрый старт

### Вместе с фронтендом (рекомендуется)

Из каталога `yt-kachalka-app`:

```bash
docker compose build
docker compose up -d
```

API будет доступен на [http://localhost:8000](http://localhost:8000).

### Только этот репозиторий

```bash
cp .env.example .env
# Заполните YOUTUBE_API_KEY ключом YouTube Data API v3

docker compose build
docker compose up -d
```

API: [http://localhost:8080](http://localhost:8080).

Nginx должен отдавать корень проекта (файл `index.php`), а PHP-FPM — слушать контейнер `api` на порту `9000`.

### Зависимости без Docker

Нужны PHP 8.4+ и Composer:

```bash
composer install
```

Точка входа: `index.php` (Symfony MicroKernel).

## Переменные окружения

Скопируйте `.env.example` в `.env`:

| Переменная | Назначение |
| --- | --- |
| `APP_SECRET` | Секрет Symfony |
| `YOUTUBE_API_KEY` | Ключ [YouTube Data API v3](https://developers.google.com/youtube/v3) |

Doctrine ожидает `DATABASE_URL`, но БД в текущем `docker-compose` не поднимается.

Ключ API передаётся в контейнер `api` через `docker-compose.yaml`.

## HTTP API

Базовый URL: `http://localhost:8080` (этот репозиторий) или `http://localhost:8000` (родительский compose).

### `GET /`

Проверка, что приложение живо.

```json
{ "hello": "world" }
```

### `GET /search?search={query}`

Поиск видео через YouTube Data API.

Пустой `search` → `[]`. Иначе JSON-массив:

```json
[
  {
    "videoId": "dQw4w9WgXcQ",
    "title": "…",
    "description": "…",
    "thumbnailUrl": "https://i.ytimg.com/vi/…/mqdefault.jpg",
    "channelTitle": "…",
    "publishedAt": "2009-10-25T06:57:33Z"
  }
]
```

Эндпоинт поиска ещё в работе: сервис и клиент YouTube готовы, контроллер нужно дописать.

## Структура репозитория

```
.
├── index.php                 # Front controller
├── composer.json
├── docker-compose.yaml
├── docker/
│   ├── api/                  # PHP-FPM: Dockerfile, entrypoint
│   └── nginx/nginx.conf
├── config/                   # Symfony: bundles, packages, routes, services
└── src/                      # Код приложения
```

## Что уже есть и чего нет

**Есть:** слоистый каркас, поиск по YouTube Data API, Docker (PHP-FPM + Nginx), health-check `GET /`.

**Пока нет:** скачивание видео (`youtube-dl-php` только в зависимостях), тесты, `bin/console`, готовый API Platform resource, рабочая Doctrine-модель и аутентификация (`security.yaml` ссылается на несуществующий `App\Entity\Admin`).
