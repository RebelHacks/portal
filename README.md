# RebelHacks Portal

The portal uses Symfony for the pages and MariaDB for, well, the DB. By the end, you should be able to sign into the local portal using a sample account.

The website repository's [developer onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md) covers Git, PHP, Composer, and editor installation. MariaDB setup is included below. On Windows, run the commands below inside Ubuntu/WSL.

## Branch naming scheme

Your branch naming scheme should be:
<type>/num-description 
e.g. `refactor/44-login-and-registration-to-backend`

## Development setup

Use PHP 8.5.12 or newer with `pdo_mysql`, Composer 2, and MariaDB 10.11. MariaDB 10.6 also works.

These steps set up the portal and an empty database on your computer. If you've already done this, skip to [day-to-day startup](#day-to-day-startup).

### 1. Install and start MariaDB

The local portal connects to MariaDB as `root` and uses a database named `portal`.

We're making local DBs first. Shared DBs come later, and this guide doesn't cover them yet.

**Ubuntu 24.04, including Ubuntu 24.04 in WSL:**

`cat /etc/os-release` shows your Ubuntu version. On Ubuntu 22.04, do the extra PHP step in the [onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md#php-composer-and-mariadb) first, then come back. You'll get MariaDB 10.6, which works.

```bash
sudo apt update
sudo apt install mariadb-server php8.5-mysql
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

**Checkpoint:** Composer should finish without errors, `php bin/console about` should show `dev`, and the last command should print `true`. If it prints `false` on Ubuntu, run `sudo apt install php8.5-mysql` and try again.

### 3. Create tables and sample data

Run this section once, using the empty `portal` database you just created. From `portal/backend`, with MariaDB still running:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --append --no-interaction
php bin/console doctrine:schema:validate
```

Run these commands one at a time and check that each succeeds.

- The first creates the tables by running the **migrations** in `backend/migrations/`. Each migration is one step of changes to the database, and together they build the tables described by the PHP classes in `backend/src/Entity/`.
- The second loads sample data: the 2027 event, users with applications, and teams. The scripts that add it are called **fixtures**.
- The third checks that the tables match the PHP classes. Both checks should report `[OK]`.

### 4. Start the backend

From `portal/backend`:

```bash
php -S 127.0.0.1:8001 -t public
```

Leave this terminal running and open **http://127.0.0.1:8001**. Sign in as `ava@demo.com` with the password `password`.

**Checkpoint:** you land on the lobby, which says "Welcome, Ava Nguyen!".

These sample accounts all use the same password:

| Role | Email | Password |
| --- | --- | --- |
| Participant | `ava@demo.com` | `password` |
| Team leader | `zz.team.01.lead@demo.com` | `password` |
| Judge | `judge1@demo.com` | `password` |

The sample data has no administrator account. If your task needs the organizer-only routes, turn a sample user into one. Log in with `mariadb -h 127.0.0.1 -u root -p portal` and run:

```sql
UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'liam@demo.com';
EXIT;
```

Then sign in as `liam@demo.com` with the password `password`.

## Day-to-day startup

Stopping the server keeps your database and sample users. You only need to start the services again.

1. Start MariaDB: `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS.
2. In a terminal at `portal/backend`, run `php -S 127.0.0.1:8001 -t public`.
3. Open **http://127.0.0.1:8001**.

Ctrl+C stops the development server.

## After pulling new changes

New code can need new packages and database changes. After a `git pull`, run these in `portal/backend`:

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
```

If a page then shows a 500 error mentioning an unknown column or table, or the migration fails, your database is older than the migrations. That happens once for everyone who set up before the new schema in November 2026, because its migration replaced the old one. The sample data is disposable, so rebuild it:

```bash
php bin/console doctrine:schema:drop --force --full-database
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --append --no-interaction
```

This empties your local `portal` database and reloads the sample data. If you made yourself an admin, repeat that step.

## Changing the database

When you change a class in `backend/src/Entity/`, add a migration in the same pull request. From `portal/backend`:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

The first writes a new file in `backend/migrations/` with the SQL for your change. Read it before you commit it: it should only touch what you changed. The second applies it to your database.

Never edit a migration that's already on `main`. Other people's databases have already run it, so fix mistakes with a new migration.

## Environment variables

These are the settings in `backend/.env`. Keep that file out of Git.

| Setting | Purpose |
| --- | --- |
| `APP_ENV=dev`, `APP_DEBUG=1` | Runs Symfony in development mode with detailed error messages |
| `APP_SECRET` | Paste the random value printed by the PHP command in step 2 |
| `DEFAULT_URI` | Address Symfony uses when generating links from terminal commands |
| `DATABASE_URL` | Connection details for your MariaDB database |
| `MAILER_DSN=null://null` | Turns off email delivery |
| `UPLOAD_DIR` | Optional, and unused while resume uploads are off. Where uploaded resumes are saved. Leave it out locally to use `backend/var/uploads`. On a server, set it to a folder outside the project, so deploys don't wipe it. |

## Running on a server

The settings above are for your own computer. On a server other people can reach:

- Set `APP_ENV=prod` and `APP_DEBUG=0`. In development mode, anyone can open Symfony's debug pages, and they show every secret.
- Generate a new `APP_SECRET`. Never reuse your local one.
- If the server sits behind a proxy or load balancer, set Symfony's trusted proxies. Otherwise every visitor looks like the same address and shares one sign-up and login limit.

## Quick Links

| Path | Purpose |
| --- | --- |
| `backend/templates/` | The pages, written in Twig |
| `backend/src/Controller/` | Handles page requests |
| `backend/src/Entity/` | PHP classes that describe the database tables |
| `backend/src/DataFixtures/` | Scripts that create sample users and teams |
| `backend/config/packages/security.yaml` | Login and access rules |

## Checks before a pull request

From `backend`, run `composer check-platform-reqs` and `php bin/console lint:container`. Then try the feature you changed using the sample accounts above. Check `git diff` before staging files. If a check fails, paste its output in the pull request description and say whether it was already failing before your change.

