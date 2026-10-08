# RebelHacks Portal

The portal uses Next.js for the interface, Symfony for the API, and MariaDB for, well, the DB. By the end, you should be able to sign into the local portal using a sample account.

Start with the website repository's [developer onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md) for Git, Node, PHP, and editor installation.

## Development setup

Use Node.js 24, PHP 8.3 with `pdo_mysql`, Composer 2, and MariaDB 10.11.

These steps are for a **new local development database**. If you already have a working environment, keep its configuration and use [starting again tomorrow](#starting-again-tomorrow).

### 1. Start MariaDB

The local portal connects to MariaDB as `root` and uses a database named `portal`.

We're making local DBs first - and then we'll get to the shared DBs when we get to it.

**Use [Beekeeper Studio](https://www.beekeeperstudio.io/get)** - it's good software!

**Ubuntu 24.04, including Ubuntu 24.04 in WSL:**

```bash
sudo apt update
sudo apt install mariadb-server php8.3-mysql
sudo service mariadb start
sudo mariadb --protocol=socket
```

The last command opens MariaDB's SQL prompt!
Looks like this.
```
MariaDB [(none)]>
```
If `sudo` asks for a password, enter your Ubuntu account password. The MariaDB `root` password is separate.

If you already set a MariaDB root password, you can log in with `mariadb -u root -p` and enter it at the prompt.

For an older Ubuntu release, first check `cat /etc/os-release` and the shared guide's PHP prerequisites. The package names above assume 24.04.

**macOS with Homebrew:**

```bash
brew install mariadb@10.11
export PATH="$(brew --prefix mariadb@10.11)/bin:$PATH"
brew services start mariadb@10.11
mariadb -u root
```

Add that `export PATH` line to your shell profile (usually `~/.zshrc`) to make it available in new terminals. If your existing MariaDB root account already has a password, use `mariadb -u root -p` instead.

For other Linux distributions, install MariaDB and the MySQL extension for your PHP version through the distribution's package manager, then start its MariaDB service.

**Checkpoint:** the prompt should now be MariaDB's SQL prompt!
 Run:

```sql
SELECT VERSION();
```

You should get something that looks like:
```
+----------------------------------+
| VERSION()                        |
+----------------------------------+
| 10.6.23-MariaDB-0ubuntu0.22.04.1 |
+----------------------------------+
```

Keep the version number (in the example, `10.6.23`) for step 2 - we'll use it to set up our DB. Also note that this will only give us the running server's version! `mariadb --version` will give you the client version.

On a fresh MariaDB installation, choose a password for `root`. Replace `your-local-db-password` below with your choice. If root already has a password, keep it and skip this command.

```sql
SET PASSWORD FOR 'root'@'localhost' = PASSWORD('your-local-db-password');
```

Then create the local database:

```sql
CREATE DATABASE portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

EXIT;
```

Back at your normal terminal prompt, connect using the same account the portal will use:

```bash
mariadb -h 127.0.0.1 -u root -p portal
```

Enter your MariaDB root password when prompted. At the SQL prompt:

```sql
SELECT DATABASE();
SHOW TABLES;
EXIT;
```

**Checkpoint:** the selected database should be `portal`, with no tables yet. Make sure you fix your connection errors here before continuing!

### 2. Configure and install the backend

From the portal repository root:

```bash
cd backend
cp .env.example .env
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Open `backend/.env` in your editor:

1. Replace `APP_SECRET` with the value printed by PHP.
2. In `DATABASE_URL`, set `serverVersion` to the numeric server version from step 1 followed by `-MariaDB`. For example, a server reporting `10.11.13-MariaDB-...` uses `10.11.13-MariaDB`.
3. Replace `your-local-db-password` in `DATABASE_URL` with your MariaDB root password. Keep the database name `portal`, user `root`, host `127.0.0.1`, and port `3306`. URL-encode special characters in the password when putting it in this connection string.

The template's connection string looks like this:

```env
DATABASE_URL="mysql://root:your-local-db-password@127.0.0.1:3306/portal?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
```

The `mysql://` prefix is intentional: Doctrine connects to MariaDB through PHP's MySQL driver. Use `127.0.0.1` consistently here; `localhost` can select a Unix socket instead of the TCP connection we tested.

While still in `portal/backend`:

```bash
composer install
composer check-platform-reqs
php bin/console about
php -r 'var_export(extension_loaded("pdo_mysql")); echo PHP_EOL;'
```

**Checkpoint:** Composer should finish successfully, Symfony should report the development environment, and the last command should print `true`. The environment file must exist before `composer install`, because Composer runs Symfony setup commands.

### 3. Create keys, tables, and sample data

From `portal/backend`, with MariaDB still running:

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --append --no-interaction
php bin/console doctrine:schema:validate
```

Run these commands one at a time and check that each succeeds.

- The keys let the API sign login tokens. They stay in ignored `backend/config/jwt/*.pem` files.
- Schema creation makes the tables defined by the current PHP entities. Run it once, against the empty local database from step 1.
- Fixtures create sample users and event data. Password hashing can take roughly a minute with little output. Let the command finish.
- Validation should report that the mapping is correct and the database schema is in sync.

Run this section once during initial setup.

### 4. Start and check the API

From `portal/backend`:

```bash
php -S 127.0.0.1:8001 -t public
```

Leave this terminal running, open another terminal, and test a fixture login:

```bash
curl -i http://127.0.0.1:8001/api/login \
  -H 'Content-Type: application/json' \
  --data '{"email":"ava@demo.com","password":"password"}'
```

**Checkpoint:** expect HTTP 200 and JSON containing a `token`. This verifies the database, sample user, password checking, and JWT keys together. An unauthenticated request to a protected route such as `/api/teams` should return 401; that alone does not mean the API is broken.

### 5. Start the frontend

In another terminal, start from the portal repository root:

```bash
cd frontend
cp .env.example .env.local
npm ci
npm run dev -- --port 3001
```

`npm ci` installs the versions recorded in the lockfile. Do not regenerate or upgrade dependencies as an incidental onboarding change.

Open **http://localhost:3001** and sign in using one of these local fixture accounts:

| Role | Email | Password |
| --- | --- | --- |
| Participant | `ava@demo.com` | `password` |
| Team leader | `zz.team.01.lead@demo.com` | `password` |
| Judge | `judge1@demo.com` | `password` |

These are sample accounts for your local database. Fixtures do not provide an administrator account; ask a maintainer for the local setup if your assigned task needs that role.

The frontend template contains:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api
NEXT_PUBLIC_ROUND_COUNT=2
```

NEXT_PUBLIC_ROUND_COUNT is required and matches the two sample rounds in the fixtures. Restart the frontend after editing environment variables.  

## Starting again tomorrow

Your database and sample users persist when you stop the servers. You only need to start the services again.

1. Start MariaDB: `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS.
2. In a terminal at `portal/backend`, run `php -S 127.0.0.1:8001 -t public`.
3. In a terminal at `portal/frontend`, run `npm run dev -- --port 3001`.
4. Open **http://localhost:3001**.

Ctrl+C stops each development server. Do not repeat schema creation, fixture loading, key generation, or environment-file copying as part of daily startup.

## Backend environment reference

The committed `.env.example` contains synthetic local values. Your real `.env` and private keys stay uncommitted.

| Setting | Purpose |
| --- | --- |
| `APP_ENV=dev`, `APP_DEBUG=1` | Symfony development mode |
| `APP_SECRET` | A locally generated application secret |
| `DEFAULT_URI` | API base address used outside browser requests |
| `DATABASE_URL` | Database driver, credentials, host, database, and server version |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY` | Paths to the generated local key pair |
| `JWT_PASSPHRASE` | Passphrase used when generating and reading the private key |
| `CORS_ALLOW_ORIGIN` | Origin regular expression for Nelmio's CORS configuration |
| `CORS_ALLOWED_ORIGINS` | Origin list for the custom CORS listener |
| `MAILER_DSN=null://null` | Disables email delivery in this local setup |

Both CORS settings are consumed by the current backend. The examples allow the portal frontend on port 3001. If you change the frontend's address, update both settings consistently. `NEXT_PUBLIC_` frontend values are browser-visible and must not contain secrets.

## Finding the code and checking a change

| Path | Purpose |
| --- | --- |
| `frontend/app/` | Routes, pages, and their UI |
| `frontend/lib/api.ts` | API client configuration |
| `frontend/lib/rounds.ts` | Round-count configuration |
| `backend/src/Controller/` | API routes and request handling |
| `backend/src/Entity/` | Database models used by Doctrine |
| `backend/src/DataFixtures/` | Local sample users and event data |
| `backend/config/packages/security.yaml` | Login and access rules |

From `frontend`, run these separately:

```bash
npm run lint
npx next typegen
npx tsc --noEmit --incremental false
npm run build
```

There is no `npm test` script!! Report existing failures separately from anything introduced by your change. Builds fetch Google fonts and require network access.

For backend changes, run `composer check-platform-reqs` and `php bin/console lint:container` from `backend`, then exercise the affected API with local fixture data. Review `git diff` before staging: generated files already tracked in this repository can change during setup.
