# Simple API (Symfony 7)

This repository contains a fresh [Symfony](https://symfony.com/) 7 skeleton configured for building APIs with Doctrine ORM and PostgreSQL.

## Getting started

1. Install PHP 8.2 or higher with the required extensions (`ctype`, `iconv`, `pdo_pgsql`).
2. Install Composer dependencies:

   ```bash
   composer install
   ```

3. Configure your database credentials in an `.env.local` file:

   ```dotenv
   DATABASE_URL="postgresql://app_user:app_password@127.0.0.1:5432/app_database?serverVersion=16&charset=utf8"
   ```

4. Create the database schema:

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Start the development server:

   ```bash
   symfony server:start
   ```

The default route (`/`) returns a JSON response confirming that the application is running.

## Testing

Run the automated test suite with:

```bash
php bin/phpunit
```

Ensure you have configured the `DATABASE_URL` for the test environment (`.env.test` or environment variables).
