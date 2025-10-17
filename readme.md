# EOL Edetabel (scaffold)

This is a minimal scaffold for the EOL edetabel importer and API. It contains a simple Importer class, a CLI `bin/import.php` and a public entrypoint for smoke tests.

Quick start:

1. Install dependencies:

```bash
composer install
```

2. Copy config example and set your API key:

```bash
cp config/.env.example .env
# edit .env and set RANKING_API_KEY and DB_* values
```

3. Run importer (example):

```bash
php bin/import.php
```

4. Run built-in webserver for smoke checks:

```bash
php -S localhost:8000 -t public
# then visit http://localhost:8000/health
```

