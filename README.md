# RebelHacks Portal

The portal uses Next.js for the interface, Symfony for the API, and MariaDB for, well, the DB. By the end, you should be able to sign into the local portal using a sample account.

Start with the website repository's [developer onboarding guide](https://github.com/RebelHacks/website/blob/main/docs/ONBOARDING.md) for Git, Node, PHP, and editor installation.

## Development setup

Use Node.js 24, PHP 8.3 with `pdo_mysql`, Composer 2, and MariaDB 10.11.

These steps set up the portal and an empty database on your computer. If you've already done this, use [starting again tomorrow](#starting-again-tomorrow).

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

Keep the version number (in the example, `10.6.23`) for step 2. Use the number from `SELECT VERSION();`, which asks the database server. `mariadb --version` reports the version of the terminal program instead.

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
3. Replace `your-local-db-password` in `DATABASE_URL` with your MariaDB root password. Keep the database name `portal`, user `root`, host `127.0.0.1`, and port `3306`. Special characters in the password need URL encoding here: for example, write `@` as `%40` in this URL.

The template's connection string looks like this:

```env
DATABASE_URL="mysql://root:your-local-db-password@127.0.0.1:3306/portal?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
```

Keep `mysql://` at the start: PHP uses its MySQL driver to connect to MariaDB. Use the same host and port as the terminal connection in step 1.

While still in `portal/backend`:

```bash
composer install
composer check-platform-reqs
php bin/console about
php -r 'var_export(extension_loaded("pdo_mysql")); echo PHP_EOL;'
```

**Checkpoint:** Composer should finish without errors, `php bin/console about` should show `dev`, and the last command should print `true`. Create `.env` before running `composer install`, because the installation reads those settings.

### 3. Create keys, tables, and sample data

From `portal/backend`, with MariaDB still running:

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

Run this section once during initial setup.

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

`npm ci` installs the package versions listed in `package-lock.json`.

Open **http://localhost:3001** and sign in using one of these sample accounts:

| Role | Email | Password |
| --- | --- | --- |
| Participant | `ava@demo.com` | `password` |
| Team leader | `zz.team.01.lead@demo.com` | `password` |
| Judge | `judge1@demo.com` | `password` |

The sample data has no administrator account. Ask a maintainer to help create one if your task needs access to the admin pages.

The frontend template contains:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api
NEXT_PUBLIC_ROUND_COUNT=2
```

`NEXT_PUBLIC_ROUND_COUNT` sets the judging-round choices on the admin page for assigning judges. With `2`, the choices include "Round 1" and "Round 2". Restart the frontend after editing `.env.local`.

## Starting again tomorrow

Your database and sample users persist when you stop the servers. You only need to start the services again.

1. Start MariaDB: `sudo service mariadb start` on Ubuntu/WSL, or `brew services start mariadb@10.11` on macOS.
2. In a terminal at `portal/backend`, run `php -S 127.0.0.1:8001 -t public`.
3. In a terminal at `portal/frontend`, run `npm run dev -- --port 3001`.
4. Open **http://localhost:3001**.

Ctrl+C stops each development server. Do not repeat schema creation, fixture loading, key generation, or environment-file copying as part of daily startup.

## Backend environment reference

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

The backend reads both CORS settings. They allow requests from `http://localhost:3001` and `http://127.0.0.1:3001`. If you change the frontend's address, update it in both settings. Visitors can read values beginning with `NEXT_PUBLIC_`, so keep passwords and private keys out of them.

## Finding the code and checking a change

| Path | Purpose |
| --- | --- |
| `frontend/app/` | Pages and their components |
| `frontend/lib/api.ts` | Sends requests to the backend |
| `frontend/lib/rounds.ts` | Creates the list of judging rounds |
| `backend/src/Controller/` | Handles API requests |
| `backend/src/Entity/` | PHP classes that describe the database tables |
| `backend/src/DataFixtures/` | Scripts that create sample users and teams |
| `backend/config/packages/security.yaml` | Login and access rules |

From `frontend`, run these separately:

```bash
npm run lint
npx next typegen
npx tsc --noEmit --incremental false
npm run build
```

There is no `npm test` script!! Include any failed checks in your pull request, and point out errors that were already present before your changes. The build downloads Google fonts, so it needs internet access.

For backend changes, run `composer check-platform-reqs` and `php bin/console lint:container` from `backend`. Then try the feature you changed using the sample accounts above. Check `git diff` before staging files; setup commands can also change files generated by Next.js and TypeScript.
