# Docker Deployment Guide

This guide provides detailed instructions for deploying the Laravel HRM application using Docker with Apache.

## Architecture

The Docker setup consists of:
- **App Container**: PHP 8.2 + Apache web server running the Laravel application
- **Redis Container**: For caching and queue management
- **External Database**: Your MySQL/PostgreSQL database (running separately)

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- External database container or server
- At least 2GB free disk space

## Quick Start

### 1. Environment Configuration

Copy the Docker environment template:
```bash
cp env.docker.example .env
```

Edit `.env` and configure the following critical settings:

```env
# Generate a new application key
APP_KEY=base64:YOUR_GENERATED_KEY_HERE

# Database connection (adjust for your setup)
DB_CONNECTION=mysql
DB_HOST=host.docker.internal  # or your database service name
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Application URL
APP_URL=http://localhost:8080  # or your domain
```

**Generate APP_KEY**:
```bash
# Using Docker
docker run --rm php:8.2 php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"

# Or if you have PHP installed locally
php artisan key:generate
```

### 2. Build and Start

```bash
# Build the Docker image
docker-compose build

# Start all services
docker-compose up -d

# Check status
docker-compose ps
```

### 3. Initialize Application

```bash
# Run database migrations
docker-compose exec app php artisan migrate --force

# (Optional) Seed the database with sample data
docker-compose exec app php artisan db:seed --force

# Create storage symlink
docker-compose exec app php artisan storage:link
```

### 4. Access Application

Open your browser and navigate to:
- **Application**: http://localhost:8080
- **Default Admin**: superadmin@example.com / password

## Database Connection Options

### Option 1: Database on Host Machine

Use `host.docker.internal` to connect to services running on your host:

```env
DB_HOST=host.docker.internal
DB_PORT=3306
```

### Option 2: Database in Separate Docker Container

If your database is in another Docker container, you have two options:

**A. Same Docker Network**:
```env
DB_HOST=your_database_service_name
```

**B. Different Docker Network**:

Add to `docker-compose.yaml`:
```yaml
services:
  app:
    networks:
      - medistaff-network
      - your_database_network

networks:
  medistaff-network:
    driver: bridge
  your_database_network:
    external: true
    name: your_actual_network_name
```

### Option 3: Remote Database Server

```env
DB_HOST=your.database.server.com
DB_PORT=3306
```

## Common Commands

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# View logs
docker-compose logs -f app

# View logs for specific service
docker-compose logs -f redis
```

### Laravel Artisan Commands

```bash
# Run any artisan command
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate:status
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan queue:work
```

### Access Container Shell

```bash
# Bash shell
docker-compose exec app bash

# Run commands as www-data user
docker-compose exec -u www-data app bash
```

## File Permissions

The entrypoint script automatically sets correct permissions. If you encounter permission issues:

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage

# Fix bootstrap/cache permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache
```

## Updating the Application

```bash
# Pull latest code
git pull

# Rebuild the image
docker-compose build

# Restart containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan optimize:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## Troubleshooting

### Container Won't Start

Check logs:
```bash
docker-compose logs app
```

Common issues:
- **Port 8080 already in use**: Change port in `docker-compose.yaml`
- **Database connection failed**: Verify DB_HOST and credentials in `.env`
- **Permission denied**: Run `docker-compose down -v` and rebuild

### Database Connection Issues

Test database connectivity:
```bash
# Check if database is reachable
docker-compose exec app php artisan db:show

# Test connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Application Shows 500 Error

```bash
# Check Laravel logs
docker-compose exec app tail -f /var/www/html/storage/logs/laravel.log

# Clear all caches
docker-compose exec app php artisan optimize:clear

# Verify APP_KEY is set
docker-compose exec app php artisan key:generate --force
```

### Assets Not Loading

```bash
# Rebuild assets
docker-compose exec app npm run build

# Clear view cache
docker-compose exec app php artisan view:clear
```

## Production Considerations

### Security

1. **Change default credentials** immediately after first login
2. **Set APP_DEBUG=false** in production
3. **Use strong APP_KEY** (never use the example key)
4. **Configure HTTPS** using a reverse proxy (nginx/traefik)
5. **Restrict database access** to only the app container

### Performance

1. **Enable caching**:
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   QUEUE_CONNECTION=redis
   ```

2. **Optimize Laravel**:
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

3. **Run queue workers**:
   ```bash
   docker-compose exec -d app php artisan queue:work --tries=3
   ```

### Monitoring

```bash
# Monitor container resources
docker stats

# Check container health
docker-compose ps

# View real-time logs
docker-compose logs -f --tail=100
```

## Backup and Restore

### Backup

```bash
# Backup storage directory
docker cp medistaff-app:/var/www/html/storage ./backup/storage

# Backup .env file
docker cp medistaff-app:/var/www/html/.env ./backup/.env
```

### Restore

```bash
# Restore storage
docker cp ./backup/storage medistaff-app:/var/www/html/

# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
```

## Scaling

To run multiple app instances behind a load balancer:

```yaml
services:
  app:
    deploy:
      replicas: 3
    # ... rest of configuration
```

## Support

For issues specific to Docker deployment:
1. Check container logs: `docker-compose logs`
2. Verify environment variables: `docker-compose config`
3. Test database connectivity: `docker-compose exec app php artisan db:show`

For Laravel-specific issues, refer to the main README.md.
