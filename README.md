# Athorrent Frontend

A lightweight seedbox frontend built with Symfony, Twig, and TypeScript.
It manages per-user qBittorrent backends through Docker and provides file
browsing, torrent management, search (via Jackett), and sharing.

![build](https://github.com/Athorcis/athorrent-frontend/actions/workflows/ci.yml/badge.svg)

## Requirements

- [Docker](https://docs.docker.com/get-docker/) with Compose v2
- Access to the Docker socket (used by the backend manager to spawn
  qBittorrent containers)
- Git

## Quick start (development)

### 1. Clone the repository

```sh
git clone https://github.com/Athorcis/athorrent-frontend.git
cd athorrent-frontend
```

### 2. Configure local hosts

Add the following entries to your hosts file:

```
127.0.0.1 athorrent.local
127.0.0.1 jackett.athorrent.local
```

On Linux and macOS, edit `/etc/hosts`. On Windows, edit
`C:\Windows\System32\drivers\etc\hosts` as administrator.

### 3. Configure environment variables

Copy the defaults and override machine-specific values in `.env.local`
(this file is git-ignored):

```sh
cp .env .env.local
```

At minimum, set:

| Variable | Description |
| --- | --- |
| `APP_SECRET` | Random secret string for Symfony (required) |
| `BACKEND_DOCKER_MOUNT_SRC` | Absolute host path to `var/user` (bind mount for user data) |

Example on Windows:

```dotenv
APP_SECRET=change-me-to-a-random-string
BACKEND_DOCKER_MOUNT_SRC=C:/Users/you/athorrent-frontend/var/user
```

Example on Linux or macOS:

```dotenv
APP_SECRET=change-me-to-a-random-string
BACKEND_DOCKER_MOUNT_SRC=/home/you/athorrent-frontend/var/user
```

Ensure `var/user` exists before starting the stack:

```sh
mkdir -p var/user var/lib
```

Other useful variables (defaults are provided in `.env`):

| Variable | Description |
| --- | --- |
| `DATABASE_URL` | Database connection (SQLite by default) |
| `BACKEND_DOCKER_QBITTORRENT_IMAGE` | qBittorrent backend image |
| `BACKEND_DOCKER_NETWORK` | Docker network for backend containers (`athorrent-dev_default` in dev) |
| `JACKETT_ORIGIN` | Jackett API URL inside the Compose network |
| `DEFAULT_URI` | Base URL used when generating links from CLI |

### 4. Start the development stack

From the project root:

```sh
bash docker/scripts/up-env.sh dev
```

The stack includes:

- **reverse-proxy** (Caddy) — HTTPS termination at `https://athorrent.local`
- **nginx** + **php** — Symfony application
- **backend-manager** — spawns and manages qBittorrent containers
- **jackett** — torrent indexer for search (`https://jackett.athorrent.local`)

On first visit, your browser may warn about the self-signed certificate
(Caddy `tls internal`). Accept the certificate or install the Caddy root CA.

### 5. Connect to the PHP container

Open a shell in the running PHP container (container name may vary; use
`docker ps` if needed):

```sh
docker exec -it athorrent-dev-php-1 bash
```

When using the dev container, open a terminal in the IDE — you are already
inside the PHP container.

### 6. Install dependencies

From the container shell:

```sh
composer install
yarn install
```

### 7. Initialize the database and create an admin user

From the container shell:

```sh
php bin/console doctrine:schema:create
php bin/console user:create admin your-password ROLE_ADMIN
```

Available roles: `ROLE_USER`, `ROLE_ADMIN`.

### 8. Build frontend assets

From the container shell:

```sh
yarn dev
```

Watch mode during development:

```sh
yarn watch
```

Production build:

```sh
yarn build
```

Open [https://athorrent.local](https://athorrent.local) and log in with the
account created above.

## Dev container

A [dev container](.devcontainer/devcontainer.json) configuration is provided.
Open the repository in VS Code or Cursor and select **Reopen in Container**
to start the dev Compose stack and connect to the PHP container
automatically.

## Running tests

End-to-end tests use [Cypress](https://www.cypress.io/) against the test
Compose stack.

### Start the test environment

```sh
bash docker/scripts/up-env.sh test
```

This resets the test database and creates a default `admin` / `test` account.

Add `athorrent.local` to your hosts file (see above) if it is not already
configured.

### Run Cypress

```sh
cd tests
yarn install --immutable
yarn cypress run
```

Interactive mode:

```sh
cd tests
yarn cypress open
```

Tests expect the application at `https://athorrent.local`.

## Production

Production images are built via Docker Bake and published to GitHub Container
Registry on each push to `master`:

- `ghcr.io/athorcis/athorrent-frontend-php`
- `ghcr.io/athorcis/athorrent-frontend-nginx`

Build locally:

```sh
docker buildx bake -f docker/build-bake.hcl
```

Set `APP_ENV=prod`, provide a strong `APP_SECRET`, and configure
`DATABASE_URL` for your target database (SQLite, MySQL, or PostgreSQL are
supported). Run `composer dump-env prod` to compile environment files for
production deployments.

## Project structure

| Path | Purpose |
| --- | --- |
| `src/` | Symfony PHP application (controllers, backend manager, filesystem) |
| `assets/` | TypeScript and SCSS sources (Webpack Encore) |
| `templates/` | Twig templates |
| `docker/` | Dockerfile, Compose files, and deployment configuration |
| `tests/cypress/` | End-to-end tests |
| `translations/` | i18n messages (English and French) |

## Related projects

- [athorrent-qbittorrent](https://github.com/Athorcis/athorrent-qbittorrent) — qBittorrent backend image
- [athorrent-backend](https://github.com/Athorcis/athorrent-backend) — legacy standalone backend (superseded by the Docker-based approach)

## License

Apache License 2.0 — see [LICENSE](LICENSE).
