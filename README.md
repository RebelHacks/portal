# RebelHacks Portal

The portal uses Next.js for the interface, Symfony for the API, and MariaDB for, well, the DB. By the end, you should be able to sign into the local portal using a sample account.

The website repository's [developer onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md) covers Git, Node, PHP, Composer, and editor installation. MariaDB setup is included below. On Windows, run the commands below inside Ubuntu/WSL.

## Development setup

Use Node.js 24, PHP 8.3 with `pdo_mysql`, Composer 2, and MariaDB 10.11. MariaDB 10.6 also works.

These steps set up the portal and an empty database on your computer. If you've already done this, skip to [day-to-day startup](#day-to-day-startup).

### 1. Install and start MariaDB

The local portal connects to MariaDB as `root` and uses a database named `portal`.

We're making local DBs first. Shared DBs come later, and this guide doesn't cover them yet.

**Ubuntu 24.04, including Ubuntu 24.04 in WSL:**

`cat /etc/os-release` shows your Ubuntu version. On Ubuntu 22.04, do the extra PHP step in the [onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md#php-composer-and-mariadb) first, then come back. You'll get MariaDB 10.6, which works.

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

**macOS with Homebrew:**

```bash
brew install mariadb@10.11
export PATH="$(brew --prefix mariadb@10.11)/bin:$PATH"
brew services start mariadb@10.11
mariadb
```

Add that `export PATH` line to your shell profile (usually `~/.zshrc`) to make it available in new terminals.

Plain `mariadb` logs you in as your macOS user, which Homebrew gives full access. On a fresh Homebrew install, `mariadb -u root` gets "Access denied" until you set a root password below. If your existing MariaDB root account already has a password, use `mariadb -u root -p` instead.

For other Linux distributions, install MariaDB and the MySQL extension for your PHP version through the distribution's package manager, then start its MariaDB service.

At the MariaDB prompt, run:

```sql
SELECT VERSION();
```

You should get something that looks like:

```
+----------------------+
| VERSION()            |
+----------------------+
| 10.11.14-MariaDB-...  |
+----------------------+
```

If your version starts with `10.11`, you don't need to do anything with it. If it starts with anything else (Ubuntu 22.04 gives `10.6`), remember the first two numbers for step 2.

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

| The error says | What to do |
| --- | --- |
| `Access denied` | The password doesn't match. Open the SQL prompt the way you did at the start of this step, run the `SET PASSWORD` line again, then retry. |
| `Can't connect` | MariaDB isn't running. Start it with `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS. |
| `Unknown database` | Open the SQL prompt again and rerun the `CREATE DATABASE` lines. |

**Optional: [Beekeeper Studio](https://www.beekeeperstudio.io/get)** shows your database in a window, so you can click through tables instead of typing SQL - it's good software! Make a new MariaDB connection with host `127.0.0.1`, port `3306`, user `root`, your MariaDB root password, and default database `portal`.

### 2. Configure and install the backend

From the portal repository root:

```bash
cd backend
cp .env.example .env
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Open `backend/.env` in your editor:

1. Replace `APP_SECRET` with the value printed by PHP.
2. Only if your MariaDB version doesn't start with `10.11`: in `DATABASE_URL`, change `serverVersion` to your first two numbers followed by `.0-MariaDB`, such as `10.6.0-MariaDB`. A value that doesn't match your MariaDB breaks the database commands in step 3.
3. Replace `your-local-db-password` in `DATABASE_URL` with your MariaDB root password. Keep the database name `portal`, user `root`, host `127.0.0.1`, and port `3306`. Special characters in the password need URL encoding here: for example, write `@` as `%40` in this URL.

The template's connection string looks like this:

```env
DATABASE_URL="mysql://root:your-local-db-password@127.0.0.1:3306/portal?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
```

Keep `mysql://` at the start: PHP uses its MySQL driver to connect to MariaDB.

While still in `portal/backend`:

```bash
composer install
composer check-platform-reqs
php bin/console about
php -r 'var_export(extension_loaded("pdo_mysql")); echo PHP_EOL;'
```

**Checkpoint:** Composer should finish without errors, `php bin/console about` should show `dev`, and the last command should print `true`. If it prints `false` on Ubuntu, run `sudo apt install php8.3-mysql` and try again.

### 3. Create keys, tables, and sample data

Run this section once, using the empty `portal` database you just created. From `portal/backend`, with MariaDB still running:

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --append --no-interaction
php bin/console doctrine:schema:validate
```

Run these commands one at a time and check that each succeeds.

- The first command creates the keys the API uses for login. They are saved in `backend/config/jwt/` and excluded from Git.
- The second creates the database tables described by the PHP classes in `backend/src/Entity/`.
- The third loads sample users and teams. The scripts that add this data are called **fixtures**. Loading them can take about a minute; wait for the terminal prompt to return.
- The fourth checks that the tables match the PHP classes. Both checks should report `[OK]`.

`doctrine:schema:create` prints a `[CAUTION]` about production. That's expected on your own computer.

Skip `doctrine:migrations:migrate`, even if a Symfony tutorial tells you to run it. The files in `backend/migrations/` are out of date and the command fails partway. If you already ran it, use the rebuild commands in [after pulling new changes](#after-pulling-new-changes).

### 4. Start and check the API

From `portal/backend`:

```bash
php -S 127.0.0.1:8001 -t public
```

Leave this terminal running, open another terminal, and sign in with a sample account:

```bash
curl -i http://127.0.0.1:8001/api/login \
  -H 'Content-Type: application/json' \
  --data '{"email":"ava@demo.com","password":"password"}'
```

**Checkpoint:** expect `HTTP/1.1 200 OK` and a response containing `"token"`. That means the sample account signed in successfully.

### 5. Start the frontend

In another terminal, start from the portal repository root:

```bash
cd frontend
cp .env.example .env.local
npm ci
npm run dev -- --port 3001
```

`npm ci` installs the package versions listed in `package-lock.json`. It also reports vulnerabilities and suggests `npm audit fix`. Leave that alone: it rewrites `package-lock.json`, which doesn't belong in your pull request.

Open **http://localhost:3001** and sign in using one of these sample accounts:

| Role | Email | Password |
| --- | --- | --- |
| Participant | `ava@demo.com` | `password` |
| Team leader | `zz.team.01.lead@demo.com` | `password` |
| Judge | `judge1@demo.com` | `password` |

The sample data has no administrator account. If your task needs the admin pages, turn a sample user into one. Log in with `mariadb -h 127.0.0.1 -u root -p portal` and run:

```sql
UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'liam@demo.com';
EXIT;
```

Sign in as `liam@demo.com` with the password `password`, then open **http://localhost:3001/dashboard-admin** yourself. Signing in always lands on `/dashboard`, and nothing links to the admin page.

The frontend template contains:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api
NEXT_PUBLIC_ROUND_COUNT=2
```

`NEXT_PUBLIC_ROUND_COUNT` sets the judging-round choices on the admin page for assigning judges. With `2`, the choices include "Round 1" and "Round 2". Restart the frontend after editing `.env.local`.

Visitors can read values beginning with `NEXT_PUBLIC_`, so keep passwords and private keys out of them.

## Day-to-day startup

Stopping the servers keeps your database and sample users. You only need to start the services again.

1. Start MariaDB: `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS.
2. In a terminal at `portal/backend`, run `php -S 127.0.0.1:8001 -t public`.
3. In a terminal at `portal/frontend`, run `npm run dev -- --port 3001`.
4. Open **http://localhost:3001**.

Ctrl+C stops each development server.

## After pulling new changes

New code can need new packages. After a `git pull`, run `composer install` in `portal/backend` and `npm ci` in `portal/frontend`.

If the API then answers with a 500 error mentioning an unknown column or table, your tables are older than the code. The sample data is disposable, so rebuild it. From `portal/backend`:

```bash
php bin/console doctrine:schema:drop --force --full-database
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --append --no-interaction
```

This empties your local `portal` database and reloads the sample users. If you made yourself an admin, repeat that step.

## Environment variables

These are the settings in `backend/.env`. Keep that file and the private login key out of Git.

| Setting | Purpose |
| --- | --- |
| `APP_ENV=dev`, `APP_DEBUG=1` | Runs Symfony in development mode with detailed error messages |
| `APP_SECRET` | Paste the random value printed by the PHP command in step 2 |
| `DEFAULT_URI` | Address Symfony uses when generating links from terminal commands |
| `DATABASE_URL` | Connection details for your MariaDB database |
| `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY` | Files containing the keys used to create and check login tokens |
| `JWT_PASSPHRASE` | Password that protects the private login key |
| `CORS_ALLOW_ORIGIN` | Frontend addresses allowed to call the API, written as a regular expression |
| `CORS_ALLOWED_ORIGINS` | The same frontend addresses, separated by commas |
| `MAILER_DSN=null://null` | Turns off email delivery |

The backend reads both CORS settings. They allow requests from `http://localhost:3001` and `http://127.0.0.1:3001`. If you change the frontend's address, update it in both settings.

## Quick Links

| Path | Purpose |
| --- | --- |
| `frontend/app/` | Pages and their components |
| `frontend/lib/api.ts` | Sends requests to the backend |
| `frontend/lib/rounds.ts` | Creates the list of judging rounds |
| `backend/src/Controller/` | Handles API requests |
| `backend/src/Entity/` | PHP classes that describe the database tables |
| `backend/src/DataFixtures/` | Scripts that create sample users and teams |
| `backend/config/packages/security.yaml` | Login and access rules |

## Checks before a pull request

From `frontend`, run these separately:

```bash
npm run lint
npx next typegen
npx tsc --noEmit --incremental false
npm run build
```

There is no `npm test` script!! `npm run lint` already reports a few errors on a fresh clone, so run it once before you start your task: whatever shows up then was there before you. If a check fails, paste its output in the pull request description and say whether it was already failing before your change.

For backend changes, run `composer check-platform-reqs` and `php bin/console lint:container` from `backend`. Then try the feature you changed using the sample accounts above. Check `git diff` before staging files. Running the frontend rewrites `frontend/next-env.d.ts`; undo that with `git restore frontend/next-env.d.ts` before you commit.
