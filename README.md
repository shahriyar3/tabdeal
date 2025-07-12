# Mautic Tabdeal

A customized Mautic project with a custom plugin and Docker configuration using Yarn instead of NPM.

## Features

- **Mautic 5.x** - Latest version of Mautic
- **CustomFormBundle Plugin** - Custom plugin for form management
- **Docker Compose** - Easy setup with Docker
- **Yarn** - Using Yarn instead of NPM for JavaScript dependency management
- **Custom Entity** - Store form data in separate database table
- **Redis Cache** - High-performance caching
- **MailHog** - Email testing service

## Prerequisites

- Docker
- Docker Compose
- Git

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/mautic_tabdeal.git
cd mautic_tabdeal
```

### 2. Environment Configuration

#### Create .env File

```bash
cp env.example .env

```

#### Recommended .env Configuration

```env
# Database Configuration
MAUTIC_DB_HOST=mysql
MAUTIC_DB_PORT=3306
MAUTIC_DB_NAME=mautic
MAUTIC_DB_USER=mautic
MAUTIC_DB_PASS=mautic_password

# Mautic Core Configuration
MAUTIC_SECRET_KEY=your_secret_key_here_change_this_in_production
MAUTIC_LOCALE=en_US
MAUTIC_TIMEZONE=UTC
MAUTIC_SITE_URL=http://localhost:8080

# Mail Configuration (Optional - for testing use MailHog)
MAUTIC_MAILER_HOST=mailhog
MAUTIC_MAILER_PORT=1025
MAUTIC_MAILER_USER=
MAUTIC_MAILER_PASSWORD=
MAUTIC_MAILER_ENCRYPTION=null

# Cache Configuration
MAUTIC_CACHE_ADAPTER=file
MAUTIC_CACHE_PREFIX=mautic_

# Session Configuration
MAUTIC_SESSION_NAME=mautic_session

# Queue Configuration (Optional)
MAUTIC_QUEUE_PROTOCOL=doctrine
MAUTIC_QUEUE_HOST=localhost
MAUTIC_QUEUE_PORT=5672
MAUTIC_QUEUE_USER=
MAUTIC_QUEUE_PASSWORD=

# Redis Configuration (Optional - for production)
MAUTIC_REDIS_HOST=redis
MAUTIC_REDIS_PORT=6379
MAUTIC_REDIS_PASSWORD=

# Logging Configuration
MAUTIC_LOG_LEVEL=error
MAUTIC_LOG_PATH=var/logs

# Security Configuration
MAUTIC_TRUSTED_PROXIES=127.0.0.1,::1
MAUTIC_TRUSTED_HOSTS=localhost,127.0.0.1

# Development Configuration (set to false in production)
MAUTIC_DEBUG=true
MAUTIC_ENV=dev
```

### 3. Start with Docker

#### Simple Method (Using Makefile)

```bash
# Complete installation and setup
make install

# Or individual commands
make start
```

#### Manual Method

```bash
docker compose up -d --build
```

This command:
- Builds Docker containers
- Installs PHP dependencies
- Installs JavaScript dependencies with Yarn
- Builds asset files
- Starts all services

### 4. Run Migrations

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Access Mautic

After setup, Mautic will be available at:
- **URL**: http://localhost:8080
- **Admin Panel**: http://localhost:8080/s/login
- **MailHog**: http://localhost:8025

## CustomFormBundle Plugin

### Description

CustomFormBundle is a custom plugin that provides the ability to create and manage custom forms. This plugin includes:

- **Custom Entity**: `CustomFormEntry` for storing form data
- **Custom Model**: `CustomFormModel` for business logic management
- **Integration Settings**: For plugin configuration

### Database Structure

#### custom_form_entry Table

```sql
CREATE TABLE custom_form_entry (
    id INT AUTO_INCREMENT NOT NULL,
    enabled TINYINT(1) DEFAULT NULL,
    textField1 VARCHAR(255) DEFAULT NULL,
    textField2 VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

#### Migration

Migration file for table creation:
- **Path**: `app/migrations/Version20241201000000.php`
- **Description**: Creates custom_form_entry table for CustomFormBundle plugin

### Run Migration

To run migration in Docker environment:

```bash
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### Plugin Files

```
plugins/CustomFormBundle/
├── Config/
│   └── config.php          # Doctrine configuration for entity
├── Entity/
│   └── CustomFormEntry.php # Entity for storing data
├── Model/
│   └── CustomFormModel.php # Model for business logic
└── CustomFormBundle.php    # Main plugin file
```

## Project Structure

```
mautic_tabdeal/
├── app/
│   ├── bundles/            # Core Mautic bundles
│   ├── migrations/         # Database migrations
│   └── ...
├── plugins/
│   ├── CustomFormBundle/   # Custom plugin
│   └── ...
├── docker-compose.yaml     # Docker Compose configuration
├── Dockerfile             # Custom Dockerfile
├── package.json           # JavaScript dependencies (Yarn)
├── env.example            # Environment configuration example
├── Makefile               # Useful commands for project management
└── README.md              # This file
```

## Docker Configuration

### Services

- **mautic**: PHP 8.1 with Mautic
- **mysql**: MySQL 8.0 for database
- **redis**: Redis 7 for caching
- **mailhog**: Mail testing service

### Ports

- **8080**: Mautic Web Interface
- **3306**: MySQL Database
- **6379**: Redis Cache
- **8025**: MailHog Web Interface

## Dependency Management

### Using Makefile (Recommended)

```bash
# Show all available commands
make help

# Complete installation and setup
make install

# Build assets
make assets

# Run migrations
make migrate

# Clear cache
make clean

# Database backup
make backup

# View logs
make logs
```

### JavaScript (Yarn)

```bash
# Install dependencies
yarn install

# Build assets
yarn build

# Or in Docker
docker compose exec php yarn install
docker compose exec php yarn build
```

### PHP (Composer)

```bash
# Install dependencies
composer install

# Or in Docker
docker compose exec php composer install
```

## Important Notes

### 1. Project Size

- `node_modules/` and build files are excluded from Git
- This reduces project size and increases clone speed
- Asset files are generated during Docker build

### 2. Security

- Sensitive files like `.env` are in `.gitignore`
- Database settings are defined in `docker-compose.yml`
- **Important**: Always create `.env` file with secure settings

### 3. Plugins

- CustomFormBundle plugin is pre-installed
- To add new plugins, place them in the `plugins/` folder

### 4. Performance Optimization

- Use Redis for caching (in production)
- Optimize PHP settings for production environment
- Use CDN for assets

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   ```bash
   docker compose exec php bin/console doctrine:database:create
   ```

2. **Migration Error**
   ```bash
   docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. **Assets Error**
   ```bash
   docker compose exec php bin/console mautic:assets:generate
   ```

4. **Environment File Error**
   ```bash
   # Check if .env file exists
   ls -la .env
   
   # Copy from example
   cp env.example .env
   ```



## Makefile Commands

| Command | Description |
|---------|-------------|
| `make help` | Show all available commands |
| `make install` | Complete installation and setup |
| `make start` | Start services |
| `make stop` | Stop services |
| `make restart` | Restart services |
| `make logs` | View logs |
| `make clean` | Clear cache and logs |
| `make migrate` | Run migrations |
| `make assets` | Build assets |
| `make test` | Run tests |
| `make backup` | Database backup |
| `make restore` | Database restore |
| `make status` | Show service status |
| `make info` | Show system information |

