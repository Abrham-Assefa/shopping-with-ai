# shopping-with-ai

An e-commerce application for building and managing an online store.

## Technology

- PHP 8.4+
- Laravel 13
- Vue.js 3
- MySQL 8, MariaDB 10.11, or PostgreSQL 16

## Local development

### Requirements

- PHP 8.4+ with the required extensions
- Composer
- Node.js and npm
- A supported database server

### Setup

1. Install PHP dependencies and create the local environment file:

   ```bash
   composer install
   cp .env.example .env
   ```

2. Configure the database connection and `APP_URL` in `.env`.

3. Generate the application key and use the built-in installer with a new, empty database:

   ```bash
   php artisan key:generate
   ```

4. In each frontend package directory (admin and storefront), install dependencies and build the assets:

   ```bash
   npm install
   npm run build
   ```

5. Start the local server from the project root:

   ```bash
   php artisan serve
   ```

   Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

> Run the installer only against a new, empty database. It is not an upgrade command.

## License

This project is distributed under the MIT License. See [LICENSE](LICENSE).
