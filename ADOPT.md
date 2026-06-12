# Adopting dev-tools — instructions for an AI agent

This file is an **executable migration instruction**. A coding agent (Claude
Code or similar) that reads it inside a foreign project carries out the steps and
reports the result. Goal: switch the project over to
[`nodus-it/dev-tools`](https://github.com/nodus-it/dev-tools) and clear out the
old, copied script/QA leftovers.

## Scope — what you do and what you do NOT

You migrate **only**:
- the **command layer** (old `dev:*`/`dup`/`ddown`/`de` scripts → plugin commands),
- the **QA packages and configs** (pint/phpstan/larastan and their config files).

You do **NOT** touch:
- **Docker images, Dockerfiles, compose files, entrypoints, nginx/php-ini** —
  the Docker setup is managed separately. You only **read** the compose file
  names to map them; you do **not rename or edit them**.
- `.env`, secrets, tokens, credentials — never touch or print them.

Do **not** commit automatically at the end. Present the changes and summarize
what you did.

## Target convention (default = no composer variables needed)

`dev-tools` runs without any configuration when the project meets this
convention. Where the project still deviates, you set `extra.nodus-dev` fields as
a bridge — until the separate Docker normalization lands.

| Thing | Convention (default) |
|---|---|
| compose directory | `.tools/docker/` |
| compose files | `compose.yml` + `compose.<env>.yml` (`dev`/`stage`/`prod`) |
| app service | `app` |
| artisan | `php artisan` |
| test runner | Pest (else `artisan test`) — detected automatically |
| Pint rules | central `vendor/nodus-it/dev-tools/config/pint.json` |
| PHPStan | project `phpstan.neon` in the root, inheriting the central base via `includes` |

## Steps

### 1. Install the plugin

```bash
composer require --dev nodus-it/dev-tools
```

Add to `composer.json` `config.allow-plugins` (otherwise the plugin won't load):

```json
"config": { "allow-plugins": { "nodus-it/dev-tools": true } }
```

### 2. Remove old command scripts

Delete from `scripts` all pure Docker wrappers now covered by the plugin —
typical names:

```
dev:up  dev:stop  dev:down  dev:logs  dev:exec  dev:artisan  dev:fresh  dev:seed
dup  ddown  de
```

**Keep** composite scripts that do more than Docker — e.g. a `dev` that starts
`docker compose … up` **and** `npm run dev` in parallel via `concurrently` (the
plugin does not cover the Vite part). Such scripts may stay; optionally move their
Docker part to `nd up` later.

Replacement mapping (document this in the PR):

| old | new |
|---|---|
| `composer dev:up` / `dup` | `composer d:up` (`nd up`) |
| `composer dev:stop` / `ddown` | `composer d:down` |
| `composer dev:exec` / `de` | `composer d:sh` |
| `composer dev:logs` | `composer d:logs` |
| `composer dev:artisan …` | `composer d:art …` |

### 3. Set the Docker mapping (without touching files)

Determine the **existing** compose files under `.tools/docker/` (or `.docker/`).
Then:

- **Already matching the convention** (`.tools/docker/compose.yml` +
  `compose.<env>.yml`, service `app`)? → add **nothing**, zero-config.
- **Deviating** (e.g. `dev/docker-compose.yml`, `dev-compose.yml`, directory
  `.docker/`)? → add a bridge in `extra.nodus-dev` pointing at the **existing**
  names. Example:

```json
"extra": {
    "nodus-dev": {
        "dir": ".tools/docker",
        "environments": {
            "dev":  ["dev/docker-compose.yml"],
            "prod": ["compose.prod.yml"]
        }
    }
}
```

This bridge disappears later when the Docker normalization renames the files to
`compose.<env>.yml` — that is **not** your job here.

### 4. Consolidate QA packages

- `laravel/pint` and `phpstan/phpstan` come in via `dev-tools` — remove them as
  **direct** duplicates from `require-dev`, **unless** the project deliberately
  pins a different version.
- Laravel project? Keep/add `larastan/larastan` in `require-dev` (`dev-tools`
  stays framework-agnostic and does not ship it).
- Pest plugins (`pestphp/*`) stay unchanged.

### 5. Consolidate QA configs

**Pint:** keep a project-local `pint.json` only if it deliberately deviates;
otherwise delete it (the central rule base applies automatically). No variable
needed.

**PHPStan:** the target is a **thin `phpstan.neon` in the project root** that
inherits the central base:

```neon
includes:
    - vendor/nodus-it/dev-tools/config/phpstan.neon
    # Laravel: also larastan
    - vendor/larastan/larastan/extension.neon
    # if present:
    - phpstan-baseline.neon
parameters:
    level: 6
    paths:
        - app
```

- Config located elsewhere (e.g. `.tools/phpstan.neon`)? → move it to the root
  and **fix the relative `includes` paths**. After that no `phpstan-config`
  variable is needed.
- The **baseline** (`phpstan-baseline.neon`) stays in the project.
- Remove the old `pint`/`phpstan`/`test` scripts from `composer.json` — there's
  now `composer qa:pint` / `qa:stan` / `qa:test` / `qa`.

### 6. Verify

```bash
composer list | grep -E 'd:|qa'      # commands registered?
composer qa:pint -- --test            # Pint runs (check only, no changes)
composer qa:stan                      # PHPStan runs
composer d:ps                         # Docker mapping resolves
```

### 7. Report

Summarize: removed scripts, removed/added packages, moved/created configs,
whether `extra.nodus-dev` was needed (and why). State explicitly that Docker
images/compose files were **deliberately left untouched**.

## Command reference

See the [README](https://github.com/nodus-it/dev-tools#readme). In short:
`d:up|down|build|ps|logs|sh|art|fresh` (Docker, `--env=dev|stage|prod`) and
`qa:pint|qa:stan|qa:test|qa` (QA). The same commands are available via `nd …`.
