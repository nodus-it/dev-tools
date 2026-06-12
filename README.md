# nodus-it/dev-tools

Unified developer commands for Nodus projects. Instead of maintaining copied
`dev:up`/`dup`/`pint`/`qa` scripts in every repo, the logic lives centrally in
this Composer plugin. Per project you only **configure**, you don't copy.

Two command groups from one codebase:

- **`d:*`** — Docker Compose control (`d:up`, `d:sh`, `d:art`, …)
- **`qa:*`** — code style, static analysis, tests (`qa:pint`, `qa:stan`, `qa:test`, `qa`)
- **`app:setup`** — get a freshly cloned project running

Three invocation layers:

| Form | works where | example |
|------|-------------|---------|
| `composer d:up` / `composer qa` | everywhere, no setup | safest fallback |
| `./vendor/bin/nd up` | everywhere after `composer install` | CI |
| `nd up` / `nd qa` | with `.envrc`/direnv or an alias | local everyday use |

## Installation (in the consuming project)

`dev-tools` is a dev tool and belongs in the project's **`require-dev`**:

```bash
composer require --dev nodus-it/dev-tools
```

Composer will ask whether the plugin may run. Allow it permanently:

```jsonc
"config": {
    "allow-plugins": {
        "nodus-it/dev-tools": true
    }
}
```

### Why `require-dev` — and why the tools still come along

`dev-tools` pulls in `laravel/pint` and `phpstan/phpstan` through its own
**`require`** (not `require-dev`, which is not installed transitively). They
still **never end up in production**:

- `composer install` (dev/CI) → `dev-tools` is installed → pint/phpstan come with it.
- `composer install --no-dev` (prod) → `dev-tools` sits in the project's
  `require-dev` → the **entire subtree** incl. pint/phpstan is skipped.

The one rule: always put `dev-tools` in `require-dev`, never in `require`.

## Configuration

**Zero-config goal:** if a project follows the convention (compose files
`.tools/docker/compose.yml` + `compose.<env>.yml`, service `app`, Pest, a root
`phpstan.neon`), it needs **no** `extra.nodus-dev` block at all. The section
below is only for deviations.

Everything project-specific lives under `extra.nodus-dev` in `composer.json`.
Every field has a default.

```jsonc
"extra": {
    "nodus-dev": {
        "dir": ".tools/docker",
        "app-service": "app",
        "artisan": "php artisan",
        "default-env": "dev",
        "environments": {
            "dev":   ["compose.yml", "compose.dev.yml"],
            "stage": ["compose.yml", "compose.stage.yml"],
            "prod":  ["compose.yml", "compose.prod.yml"]
        },
        "test": "vendor/bin/pest"
    }
}
```

| Field | Default | Meaning |
|-------|---------|---------|
| `dir` | `.tools/docker` | directory holding the compose files |
| `app-service` | `app` | service used by `sh`, `art`, `fresh` |
| `artisan` | `php artisan` | artisan invocation inside the container |
| `default-env` | `dev` | environment used without `--env` |
| `environments` | dev/stage/prod | compose file layers per environment |
| `test` | _heuristic_ | test runner; without a value: Pest, otherwise `artisan test` |
| `pint-config` | _auto_ | path to a Pint config (else local `pint.json` / package default) |
| `phpstan-config` | _auto_ | path to a PHPStan config (else local `phpstan.neon` / central base) |
| `phpstan-paths` | `["app","src"]` | analysis paths in the zero-config case only (no local `phpstan.neon`) |

## Migrating an existing project

Point an AI agent in the target project at [`ADOPT.md`](ADOPT.md) — it migrates
the command layer and QA and clears out old script/config leftovers (Docker
images are left untouched):

```
Read https://raw.githubusercontent.com/nodus-it/dev-tools/main/ADOPT.md
and run the migration for this project.
```

## Docker commands (`d:*`)

| `composer …` | `nd …` | effect |
|--------------|--------|--------|
| `d:up` | `nd up` | `compose up -d` |
| `d:down` | `nd down` | `compose down` |
| `d:build` | `nd build` | `compose build` |
| `d:ps` | `nd ps` | `compose ps` |
| `d:logs [svc]` | `nd logs [svc]` | `compose logs -f` |
| `d:sh [svc]` | `nd sh [svc]` | shell in the container (bash, else sh) |
| `d:art …` | `nd art …` | `artisan` in the app container |
| `d:fresh` | `nd fresh` | `artisan migrate:fresh --seed` |
| — | `nd exec …` | arbitrary command in the app container |
| — | `nd run …` | one-off container (`run --rm`) |

Pick the environment: `--env=prod` or `-e prod`.

Passing options through to artisan:

```bash
nd art migrate --force                 # passed through cleanly
composer d:art -- migrate --force      # via Composer with the leading --
```

## QA commands (`qa:*`)

| `composer …` | `nd …` | effect |
|--------------|--------|--------|
| `qa:pint` | `nd pint` | run Pint — `--test` to only check |
| `qa:stan` | `nd stan` | PHPStan analysis |
| `qa:test` | `nd test` | tests (Pest/PHPUnit/artisan test) |
| `qa` | `nd qa` | `pint --test` → `stan` → `test`, stops at the first failure |

> Deliberately **no** short composer aliases (`pint`/`test`/…) so the plugin
> never shadows identically named project scripts. The short forms exist via the
> `nd` binary. Docker stays under `d:*`.

### Central QA rules, locally inheritable

`dev-tools` ships a base config: `config/pint.json` and `config/phpstan.neon`.
The commands use them automatically as long as the project does not provide its
own.

- **Pint** cannot inherit natively — `qa:pint` therefore points `--config` at the
  central `pint.json` (or a local one, if present).
- **PHPStan** inherits natively. Create a thin `phpstan.neon` in the project:

  ```neon
  includes:
      - vendor/nodus-it/dev-tools/config/phpstan.neon
  parameters:
      level: 6
      paths:
          - app
          - src
  ```

  Baselines (`phpstan-baseline.neon`) and level overrides stay **in the
  project**. Laravel projects add `larastan` to their own `require-dev` and to
  the local `includes` list — `dev-tools` stays framework-agnostic and pulls in
  no `illuminate/*`.

## `app:setup`

`composer app:setup` (or `nd setup`) gets a freshly cloned project running —
every step is guarded by file existence:

1. `.env` from `.env.example` (if missing)
2. `artisan key:generate` + `artisan migrate --force` (if `artisan` exists)
3. `npm install && npm run build` (if `package.json` exists)
4. `artisan boost:install` (if `laravel/boost` is installed)

## `nd` without `vendor/bin/` (optional)

An `.envrc` in the project (using [direnv](https://direnv.net/)):

```bash
PATH_add vendor/bin
```

Then `nd up` / `nd qa` works inside the project directory. Without direnv an
alias does the job: `nd() { ./vendor/bin/nd "$@"; }`.

## License

MIT — see [LICENSE](LICENSE).
