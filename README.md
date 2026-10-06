# RebelHacks Portal

The portal uses Next.js for the interface, Symfony for the API, and MariaDB for, well, the DB. By the end, you should be able to sign into the local portal using a sample account.

Start with the website repository's [developer onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md) for Git, Node, PHP, and editor installation.

## Development setup

Use Node.js 24, PHP 8.3 with `pdo_mysql`, Composer 2, and MariaDB. New installs should use MariaDB 10.11, matching the repository's DDEV configuration. The walkthrough was exercised on WSL/Ubuntu with MariaDB 10.6.23; macOS installation commands have not been run as part of that check.

These steps are for a **new local development database**. If you already have a working environment, keep its configuration and use [starting again tomorrow](#starting-again-tomorrow).

The repository's database setup needs team review: Compose specifies PostgreSQL, DDEV specifies MariaDB, and the migration history mixes both SQL formats. The steps below use native MariaDB and create a fresh local schema from the current entities!

### 1. Start MariaDB

Inside MariaDB, we will create one database for the portal and a separate account that the API uses to connect.

We're making local DBs first - and then we'll get to the shared DBs when we get to it.

**Use Beekeeper Studio** - it's good software! https://www.beekeeperstudio.io/get

**Ubuntu 24.04 or Ubuntu in WSL:**

```bash
sudo apt update
sudo apt install mariadb-server php8.3-mysql
sudo service mariadb start
sudo mariadb
```

The last command opens MariaDB's SQL prompt!
Looks like this.
```
MariaDB [(none)]>
```
Your account's password is doing to be distinct from the database password we create below.

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

  While we're still in that same SQL prompt, create the local database and account:

```sql
CREATE DATABASE rebelhacks_portal_dev
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'rebelhacks_dev'@'127.0.0.1'
  IDENTIFIED BY 'local-development-only';

GRANT ALL PRIVILEGES ON rebelhacks_portal_dev.*
  TO 'rebelhacks_dev'@'127.0.0.1';

EXIT;
```

These names and this local-only password match the environment template. If a database or account already exists, stop and check what it belongs to; do not drop it to get past the error.

Back at your normal terminal prompt, test the application's account:

```bash
mariadb -h 127.0.0.1 -u rebelhacks_dev -p rebelhacks_portal_dev
```

Enter `local-development-only` when prompted. At the SQL prompt:

```sql
SELECT DATABASE();
SHOW TABLES;
EXIT;
```

**Checkpoint:** the selected database should be `rebelhacks_portal_dev`, with no tables yet. Make sure you fix your connection errors here before continuing!

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
3. Keep the database name, account, password, host, and port aligned with the account you just tested.

The template's connection string looks like this:

```env
DATABASE_URL="mysql://rebelhacks_dev:local-development-only@127.0.0.1:3306/rebelhacks_portal_dev?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
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

The `--append` option avoids the fixture command's default database purge. It is still a data-writing command: use it only for this development database, once during initial setup.

**Why not migrations?** The checked-in migration history mixes MySQL/MariaDB and PostgreSQL syntax and does not fully initialize the current entities. Creating a fresh schema is a tested local workaround, not an upgrade procedure for existing or production data. After this bootstrap, do not run the old migrations over the created tables. Repairing the shared migration history needs team coordination.

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

`npm ci` installs the versions recorded in the lockfile. The lockfile has been reconciled with the existing manifest and tested with a clean install. Do not regenerate or upgrade dependencies as an incidental onboarding change.

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

The round count is required: frontend configuration throws an error if it is missing or invalid. Two is a local sample value matching the fixtures, not a decision about the live event. Restart the frontend after editing environment variables.  

## Starting again tomorrow

Your database and sample users persist when you stop the servers. You only need to start the services again.

1. Start MariaDB: `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS.
2. In a terminal at `portal/backend`, run `php -S 127.0.0.1:8001 -t public`.
3. In a terminal at `portal/frontend`, run `npm run dev -- --port 3001`.
4. Open **http://localhost:3001**.

Ctrl+C stops each development server. Do not repeat schema creation, fixture loading, key generation, or environment-file copying as part of daily startup. Coordinate schema changes to existing databases with the team.

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

## Troubleshooting MariaDB and login

| Symptom | Check |
| --- | --- |
| Cannot connect / error 2002 or 2003 | Start MariaDB. Check the host and port; this guide uses TCP at `127.0.0.1:3306`. Another database service may already own that port. |
| Access denied / error 1045 | Repeat the step 1 login with `-h 127.0.0.1`. Check the account, password, and granted host before changing Symfony configuration. |
| Unknown database / error 1049 | Check that the database was created and that its name matches `DATABASE_URL`. |
| Database or account already exists | Inspect the existing setup with a maintainer. Choose a new local database/account if needed, updating both SQL and `.env`; do not delete existing data. |
| Could not find driver | Install the MySQL extension for the PHP version shown by `php -v`. The `extension_loaded("pdo_mysql")` check should print `true`. |
| SQL error mentioning `SERIAL`, quotes, or incompatible syntax during migration | You may be running the mixed migration history. Use the fresh-database procedure above for onboarding; get help before altering an existing database. |
| Table already exists during `schema:create` | The database is not empty or schema creation already ran. Do not rerun initialization; try schema validation and inspect the database. |
| Environment variable missing during Composer or console commands | Create `backend/.env` from the full template before installing. Existing `.env.local` settings override values in `.env`. |
| JWT signing/key error on login | Generate the local keys using the step 3 command. The configured passphrase must match the existing key; changing it afterward does not re-encrypt the key. |
| Fixture login returns 401 | Verify fixtures completed and that this API points at that database. Existing sample users can have different passwords; fixtures are not a password-reset command. |
| Login works with curl but fails in the browser | Check `NEXT_PUBLIC_API_URL`, restart Next.js after environment changes, and match both backend CORS settings to the browser's exact origin. |
| Round count error | Set `NEXT_PUBLIC_ROUND_COUNT=2` in the frontend's `.env.local` and restart Next.js. |

When asking for help, include your OS, current directory, command, and the first relevant error. Remove passwords, tokens, and private environment values from anything you share.

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

There is no `npm test` script. Existing lint failures are recorded in the website repository's [setup status](https://github.com/RebelHacks/website/blob/main/docs/SETUP-STATUS.md). A clean `npm ci`, type checking, and building passed after the lockfile repair. Builds fetch Google fonts and require network access.

For backend changes, run `composer check-platform-reqs` and `php bin/console lint:container` from `backend`, then exercise the affected API with local fixture data. Review `git diff` before staging: generated files already tracked in this repository can change during setup.
