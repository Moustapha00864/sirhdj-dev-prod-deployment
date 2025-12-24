# Docker Quick Start Guide

## First Time Setup

1. **Copy environment file**:
   ```bash
   cp env.docker.example .env
   ```

2. **Generate APP_KEY**:
   ```bash
   docker run --rm php:8.2 php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

3. **Edit .env file** and set:
   - `APP_KEY` (from step 2)
   - `DB_HOST` (use `host.docker.internal` for database on your host machine)
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

4. **Build and start**:
   ```bash
   docker-compose build
   docker-compose up -d
   ```

5. **Run migrations**:
   ```bash
   docker-compose exec app php artisan migrate --force
   docker-compose exec app php artisan db:seed --force
   ```

6. **Access**: http://localhost:8080

## Common Commands

```bash
# View logs
docker-compose logs -f app

# Restart
docker-compose restart

# Stop
docker-compose down

# Run artisan commands
docker-compose exec app php artisan [command]

# Shell access
docker-compose exec app bash

# Clear caches
docker-compose exec app php artisan optimize:clear
```

## Troubleshooting

**Can't connect to database?**
- Check DB_HOST in .env (use `host.docker.internal` for host database)
- Verify database is running: `docker-compose exec app php artisan db:show`

**Permission errors?**
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

**Port 8080 in use?**
- Edit `docker-compose.yaml` and change `"8080:80"` to `"8081:80"` (or any free port)

For detailed documentation, see [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md)
