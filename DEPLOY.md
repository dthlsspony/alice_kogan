# Разворачивание на проде (без Docker)

Стек: **Nginx + PHP-FPM 8.3+ + MariaDB** на обычном Linux (Debian/Ubuntu).
Приложение лежит в подкаталоге `src/` этого репозитория; document root —
`src/public`.

## 1. Пакеты на сервере

```bash
sudo apt update
sudo apt install -y nginx mariadb-server git \
    php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-exif \
    php8.4-bcmath php8.4-gd php8.4-intl php8.4-zip php8.4-curl \
    php8.4-xml php8.4-opcache
# composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# node — только если ассеты будете собирать на сервере (см. шаг 5)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

Список расширений повторяет прод-образ Dockerfile (`pdo_mysql mbstring exif
pcntl bcmath gd intl zip opcache`); `pcntl` встроен в php-cli.

## 2. Код и права

```bash
sudo mkdir -p /var/www/kogan
sudo chown $USER:www-data /var/www/kogan
git clone <репозиторий> /var/www/kogan
cd /var/www/kogan/src
composer install --no-dev --optimize-autoloader
```

Права на запись php-fpm-пользователю (в Debian/Ubuntu — `www-data`):

```bash
chown -R $USER:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

## 3. База данных

```bash
sudo mariadb
```

```sql
CREATE DATABASE kogan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kogan'@'localhost' IDENTIFIED BY '<сильный пароль>';
GRANT ALL PRIVILEGES ON kogan.* TO 'kogan'@'localhost';
FLUSH PRIVILEGES;
```

## 4. .env

```bash
cd /var/www/kogan/src
cp .env.example .env
nano .env
php artisan key:generate
```

Обязательные отличия от dev-значений:

| Переменная | Значение |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (иначе гостям будут видны пароли БД в ошибках) |
| `APP_URL` | `https://kogan-im.com` (реальный домен) |
| `DB_HOST` | `127.0.0.1` |
| `DB_PASSWORD` | пароль из шага 3 |

`SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` = `database` — Redis не нужен.
`MAIL_MAILER=log` — писем сайт не шлёт (восстановления пароля нет).

## 5. Ассеты (public/build)

`public/build` **не в git** ( `.gitignore`). Два варианта:

- **Собрать на сервере** (нужен Node из шага 1):
  ```bash
  cd /var/www/kogan/src
  npm install --ignore-scripts
  npm run build
  ```
- **Или скопировать с дев-машины** (Node на сервере не нужен):
  ```bash
  # с дев-машины
  rsync -av src/public/build/ user@server:/var/www/kogan/src/public/build/
  ```
  После новых Tailwind-классов в blade — пересобрать и снова скопировать.

## 6. Медиатека legacy-контента (обязательно!)

Контент сеялки (`storage/app/public/kogan/`, 377 файлов, ~55 МБ: документы,
фото, сконвертированные mp4) в git **не входит**, а на проде нет каталога
`/var/www/legacy`, из которого сеялка восстанавливает файлы. Поэтому каталог
нужно привезти с дев-машины (он лежит в volume контейнера `kogan_php`):

```bash
# с дев-машины: вынуть из контейнера (или взять src/storage/app/public/kogan)
docker cp kogan_php:/var/www/html/storage/app/public/kogan ./kogan-media
rsync -av ./kogan-media/ user@server:/var/www/kogan/src/storage/app/public/kogan/
```

Пропустите шаг — и все `/storage/kogan/...` (документы, фото, видео) будут 404.

## 7. Миграции и сид-контент

```bash
cd /var/www/kogan/src
php artisan migrate --seed --force
php artisan storage:link
```

Сидер создаёт страницы/статьи/книги/меню из `database/seeders/data/kogan/*.json`
(они в git) и админа `admin@example.com` / `admin123`.

> ⚠️ **Сразу смените пароль админа** (войти на /login → профиль).
> И больше никогда не запускайте на проде `migrate:fresh --seed` —
> он стирает правки, сделанные через админку.

## 8. Nginx

`/etc/nginx/sites-available/kogan.conf`:

```nginx
server {
    listen 80;
    server_name kogan-im.com www.kogan-im.com;
    root /var/www/kogan/src/public;
    index index.php;
    client_max_body_size 8m;   # лимит загрузок в админке (FileUpload 4 МБ)

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known) { deny all; }

    # статику отдаём долго кэшированной (имена файлов у Vite хэшированы)
    location ~* \.(css|js|woff2?|jpg|jpeg|png|gif|svg|ico)$ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/kogan.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Отличия от docker-конфига: `/storage/` пойдёт через симлинк
`public/storage` (шаг 7) — отдельный `location` с `alias` на Linux не нужен.

HTTPS: `sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d kogan-im.com -d www.kogan-im.com`.

## 9. Cron (очистка статистики)

В проекте есть расписание — `model:prune` ежедневно подчищает таблицу
`visits` (сырые визиты старше 90 дней). Без cron таблица будет расти вечно:

```bash
crontab -e
* * * * * cd /var/www/kogan/src && php artisan schedule:run >> /dev/null 2>&1
```

Очередь (`queue:work`) не нужна — заданий в очередь сайт не ставит.

## 10. Кэши и opcache (прод-режим)

```bash
cd /var/www/kogan/src
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache        # иконки Filament
```

`/etc/php/8.4/fpm/conf.d/99-prod.ini`:

```ini
opcache.validate_timestamps=0   # код меняется только деплоем (+ reload fpm)
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
realpath_cache_size=4096K
realpath_cache_ttl=600
```

`sudo systemctl reload php8.4-fpm` после правки.

## 11. Обновление (деплой новой версии)

```bash
cd /var/www/kogan/src
php artisan down            # страница обслуживания
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
# ассеты: npm install && npm run build  (или rsync с дев-машины)
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl reload php8.4-fpm
php artisan up
```

## 12. Бэкапы

```bash
# база
mysqldump kogan | gzip > /backup/kogan-$(date +%F).sql.gz
# медиатека (загруженные через админку обложки + kogan/)
rsync -a /var/www/kogan/src/storage/app/public/ /backup/storage-public/
```

## Чек-лист

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, ключ сгенерирован
- [ ] пароль админа сменён (`admin@example.com` / `admin123` больше не работает)
- [ ] `public/build` собран/скопирован (шрифты, css без 404)
- [ ] `storage/app/public/kogan` привезён с дев-машины (видео/фото/документы открываются)
- [ ] `php artisan storage:link` сделан
- [ ] cron `schedule:run` стоит (иначе `visits` растёт бесконечно)
- [ ] HTTPS включён, http → https редиректит
- [ ] `php artisan down` → страница обслуживания показывается
