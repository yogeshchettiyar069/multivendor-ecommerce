# Multi-Vendor E-Commerce Platform

A production-grade, multi-vendor marketplace where independent vendors manage their
own catalogue and orders while a platform admin oversees the whole operation —
built on **Laravel 12**, **MongoDB**, and an **Inertia + React + TypeScript**
front-end, fully containerised so it runs with a single command.

> **Status:** under active construction. This README is expanded each phase; the
> full feature tour, ERD, and screenshots land in the final phase.

## Tech stack

| Layer        | Choice                                                            |
| ------------ | ---------------------------------------------------------------- |
| Backend      | Laravel 12 (PHP 8.3), MVC                                         |
| Database     | MongoDB 7 via `mongodb/laravel-mongodb` (Eloquent-compatible)     |
| Front-end    | Inertia.js + React 18 + TypeScript                               |
| UI           | Tailwind CSS + shadcn/ui + lucide-react                          |
| Auth         | Laravel Breeze (Inertia/React) + role-based access control        |
| Payments     | Stripe (test mode) — PaymentIntents + webhooks                    |
| Infra        | Docker (PHP-FPM, nginx, MongoDB replica set, Redis, queue worker) |
| Quality      | Pest, PHPStan (level 6), Laravel Pint, ESLint + Prettier         |

## Run it in one command

> **Prerequisites:** [Docker Desktop](https://www.docker.com/products/docker-desktop)
> running. Nothing else — PHP, Node, and MongoDB all live inside the containers.

```bash
cp .env.docker .env          # pre-wired for the Docker stack
docker compose up --build     # build images and boot the whole stack
```

Then open **http://localhost:8080**.

The stack starts six services: `web` (nginx), `app` (PHP-FPM), `mongo`
(single-node **replica set**, required for transactions), `mongo-init` (one-shot
replica-set initiation), `redis`, and a `queue` worker. On first boot the `app`
container waits for a writable MongoDB primary, runs migrations (which create the
indexes), and — when `SEED_ON_BOOT=true` — seeds demo data.

### Useful shortcuts (optional `make`)

```bash
make up       # build + start (detached)
make logs     # tail app/web logs
make shell    # shell into the app container
make test     # run the test suite
make fresh    # wipe volumes and rebuild from scratch
make down     # stop everything
```

## Run without Docker

Requires PHP 8.3 with the `mongodb` extension, Composer, Node, and a MongoDB
replica set (local `mongod --replSet rs0` or a free Atlas M0 cluster).

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
# point MONGODB_URI at your database, then:
php artisan migrate
npm run dev          # in one terminal
php artisan serve    # in another  ->  http://localhost:8000
```

## MongoDB & transactions

Atomic checkout relies on multi-document MongoDB transactions, which require a
**replica set**. The bundled Docker `mongo` service runs as a single-node replica
set (`--replSet rs0`) and is auto-initiated by the `mongo-init` service. MongoDB
Atlas clusters are replica sets out of the box, so the same code works against a
free Atlas M0 tier by changing only `MONGODB_URI`.

## License

[MIT](LICENSE)
