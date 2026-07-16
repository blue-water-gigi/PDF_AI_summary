# PDF AI Summary

PDF AI Summary is a web service that turns uploaded PDF documents into concise, structured summaries with the help of AI. Users can choose a summary format, review previously generated results, manage their subscription, and track plan limits.

## Features

- PDF upload and text extraction (files up to 20 MB)
- AI-powered summaries through OpenRouter
- Standard, bullet-point, key-highlight, and detailed-analysis formats
- User registration, authentication, profile settings, and notifications
- Summary history and per-plan usage limits
- Subscription management with Stripe and YooMoney integrations
- Admin interface for user plan management

## Tech stack

- PHP 8.4, Laravel 12, PostgreSQL 16
- React 19, TypeScript, Inertia.js 2
- Tailwind CSS 4 and Vite 6
- Nginx and Docker Compose
- OpenRouter for AI completions
- Stripe and YooMoney for payments

## Requirements

- Docker with Docker Compose
- An [OpenRouter](https://openrouter.ai/) API key
- Stripe or YooMoney credentials only if payment flows are required

## Quick start

1. Clone the repository and enter its directory:

   ```bash
   git clone git@github.com:blue-water-gigi/PDF_AI_summary.git
   cd PDF_AI_summary
   ```

2. Create the application environment file:

   ```bash
   cp main_app/.env.example main_app/.env
   ```

3. Update `main_app/.env` for the Docker environment:

   ```dotenv
   APP_NAME="PDF AI Summary"
   APP_URL=http://localhost:8000

   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=pdf_summ
   DB_USERNAME=admin
   DB_PASSWORD=admin

   OPENROUTER_API_KEY=your_openrouter_api_key
   ```

   Do not commit real API keys or payment credentials.

4. Build and start the main services:

   ```bash
   docker compose up -d --build postgres php nginx
   ```

5. Enter the PHP container and initialize the application:

   ```bash
   docker compose exec php bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate --seed
   npm run build
   exit
   ```

6. Open [http://localhost:8000](http://localhost:8000).

## Development

Start the containers if they are not already running:

```bash
docker compose up -d postgres php nginx
```

Run the Vite development server with hot module replacement:

```bash
docker compose exec php npm run dev
```

When you need an interactive shell in the application container, use:

```bash
docker compose exec php bash
```

If queued jobs are used, run a worker in a separate terminal:

```bash
docker compose exec php php artisan queue:work
```

## Payment webhooks

Payment credentials are configured in `main_app/.env`:

```dotenv
PAYMENT_GATEWAY=stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_RESTRICTED=
STRIPE_WEBHOOK_SECRET=
STRIPE_API_KEY=
```

The default payment gateway is YooMoney when `PAYMENT_GATEWAY` is not set. To start the optional Stripe CLI listener, run:

```bash
docker compose up -d stripe-cli
docker compose logs -f stripe-cli
```

Use the webhook signing secret printed by Stripe CLI as `STRIPE_WEBHOOK_SECRET`, then restart the affected services after changing the environment file.

## Tests and code quality

Run the backend test suite:

```bash
docker compose exec php php artisan test --compact
```

Check frontend formatting:

```bash
docker compose exec php npm run format:check
```

Run PHP formatters:

```bash
docker compose exec php composer format
```

## Useful commands

```bash
# View service status
docker compose ps

# Follow application logs
docker compose logs -f nginx php

# Apply new database migrations
docker compose exec php php artisan migrate

# Clear Laravel caches
docker compose exec php php artisan optimize:clear

# Stop the environment
docker compose down
```

To stop the environment and permanently delete the local PostgreSQL volume, use `docker compose down -v`. This removes all local database data.

## Project structure

```text
PDF_AI_summary/
├── docker/                 # Nginx and PHP development configuration
├── main_app/               # Laravel, React, and Inertia application
│   ├── app/                # Application logic
│   ├── database/           # Migrations, factories, and seeders
│   ├── resources/          # React pages, components, and styles
│   ├── routes/             # Web and console routes
│   └── tests/              # Pest test suite
└── docker-compose.yaml     # Local service definitions
```

## Notes

- The application is served by Nginx on port `8000`.
- Vite is exposed on port `5173` during frontend development.
- PostgreSQL is exposed on port `5432` for local development tools.
- Uploaded PDFs are deleted after processing; generated summaries are stored in the database.
