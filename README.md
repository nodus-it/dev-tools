# nodus-it/dev-tools

Einheitliche Dev-Befehle für Nodus-Projekte. Statt in jedem Repo eigene
`dev:up`/`dup`/`pint`/`qa`-Scripts zu pflegen, liegt die Logik zentral in
diesem Composer-Plugin. Pro Projekt wird nur noch **konfiguriert**, nicht
kopiert.

Zwei Befehlsgruppen aus einer Codebasis:

- **`d:*`** — Docker-Compose-Steuerung (`d:up`, `d:sh`, `d:art`, …)
- **`qa:*`** — Code-Style, statische Analyse, Tests (`qa:pint`, `qa:stan`, `qa:test`, `qa`)
- **`app:setup`** — frisch geklontes Projekt lauffähig machen

Drei Aufruf-Ebenen:

| Form | funktioniert wo | Beispiel |
|------|-----------------|----------|
| `composer d:up` / `composer qa` | überall, kein Setup | sicherster Fallback |
| `./vendor/bin/nd up` | überall nach `composer install` | CI |
| `nd up` / `nd qa` | mit `.envrc`/direnv oder Alias | Alltag lokal |

## Installation (im Konsumenten-Projekt)

`dev-tools` ist ein Dev-Werkzeug und gehört in die **`require-dev`** des
Projekts:

```bash
composer require --dev nodus-it/dev-tools
```

Composer fragt, ob das Plugin laufen darf. Dauerhaft erlauben:

```jsonc
"config": {
    "allow-plugins": {
        "nodus-it/dev-tools": true
    }
}
```

### Warum `require-dev` — und warum die Tools trotzdem mitkommen

`dev-tools` zieht `laravel/pint` und `phpstan/phpstan` über sein eigenes
**`require`** mit (nicht `require-dev`, denn das lädt nicht transitiv).
Trotzdem landen sie **nie in der Produktion**:

- `composer install` (dev/CI) → `dev-tools` wird installiert → pint/phpstan kommen mit.
- `composer install --no-dev` (Prod) → `dev-tools` steht im `require-dev` des
  Projekts → der **gesamte Teilbaum** inkl. pint/phpstan wird übersprungen.

Einzige Regel: `dev-tools` immer in `require-dev`, nie in `require`.

## Konfiguration

Alles Projekt-Spezifische steht unter `extra.nodus-dev` in der
`composer.json`. Alle Felder haben Defaults.

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

| Feld | Default | Bedeutung |
|------|---------|-----------|
| `dir` | `.tools/docker` | Verzeichnis der Compose-Dateien |
| `app-service` | `app` | Service für `sh`, `art`, `fresh` |
| `artisan` | `php artisan` | Artisan-Aufruf im Container |
| `default-env` | `dev` | Environment ohne `--env` |
| `environments` | dev/stage/prod | Compose-Datei-Layer pro Environment |
| `test` | _Heuristik_ | Test-Runner; ohne Angabe: Pest, sonst `artisan test` |
| `pint-config` | _auto_ | Pfad zu Pint-Config (sonst lokale `pint.json` / Paket-Default) |
| `phpstan-config` | _auto_ | Pfad zu PHPStan-Config (sonst lokale `phpstan.neon`) |

## Docker-Befehle (`d:*`)

| `composer …` | `nd …` | Wirkung |
|--------------|--------|---------|
| `d:up` | `nd up` | `compose up -d` |
| `d:down` | `nd down` | `compose down` |
| `d:build` | `nd build` | `compose build` |
| `d:ps` | `nd ps` | `compose ps` |
| `d:logs [svc]` | `nd logs [svc]` | `compose logs -f` |
| `d:sh [svc]` | `nd sh [svc]` | Shell im Container (bash, sonst sh) |
| `d:art …` | `nd art …` | `artisan` im App-Container |
| `d:fresh` | `nd fresh` | `artisan migrate:fresh --seed` |
| — | `nd exec …` | beliebiger Befehl im App-Container |
| — | `nd run …` | Einmal-Container (`run --rm`) |

Environment wählen: `--env=prod` bzw. `-e prod`.

Optionen an artisan durchreichen:

```bash
nd art migrate --force                 # sauber durchgereicht
composer d:art -- migrate --force      # via Composer das -- davor
```

## QA-Befehle (`qa:*`)

| `composer …` | `nd …` | Wirkung |
|--------------|--------|---------|
| `qa:pint` (`pint`) | `nd pint` | Pint fixen — `--test` nur prüfen |
| `qa:stan` (`stan`) | `nd stan` | PHPStan-Analyse |
| `qa:test` (`test`) | `nd test` | Tests (Pest/PHPUnit/artisan test) |
| `qa` | `nd qa` | `pint --test` → `stan` → `test`, stoppt beim ersten Fehler |

### Zentrale QA-Regeln, lokal erbbar

`dev-tools` bringt eine Basis-Config mit: `config/pint.json` und
`config/phpstan.neon`. Die Befehle nutzen sie automatisch, solange das Projekt
keine eigene mitbringt.

- **Pint** kann nicht nativ erben — `qa:pint` zeigt darum per `--config` auf die
  zentrale `pint.json` (oder eine lokale, falls vorhanden).
- **PHPStan** erbt nativ. Lege im Projekt eine dünne `phpstan.neon` an:

  ```neon
  includes:
      - vendor/nodus-it/dev-tools/config/phpstan.neon
  parameters:
      level: 6
      paths:
          - app
          - src
  ```

  Baselines (`phpstan-baseline.neon`) und Level-Overrides bleiben **im
  Projekt**. Laravel-Projekte ergänzen `larastan` in ihrer eigenen
  `require-dev` und in der lokalen `includes`-Liste — `dev-tools` bleibt
  framework-agnostisch.

## `app:setup`

`composer app:setup` (bzw. `nd setup`) richtet ein frisch geklontes Projekt
ein — jeder Schritt ist durch Datei-Existenz abgesichert:

1. `.env` aus `.env.example` (falls fehlt)
2. `artisan key:generate` + `artisan migrate --force` (falls `artisan` da ist)
3. `npm install && npm run build` (falls `package.json` da ist)
4. `artisan boost:install` (falls `laravel/boost` installiert ist)

## `nd` ohne `vendor/bin/` (optional)

Eine `.envrc` im Projekt (mit [direnv](https://direnv.net/)):

```bash
PATH_add vendor/bin
```

Danach genügt `nd up` / `nd qa` im Projektverzeichnis. Ohne direnv tut es ein
Alias: `nd() { ./vendor/bin/nd "$@"; }`.

## Lizenz

MIT — siehe [LICENSE](LICENSE).
