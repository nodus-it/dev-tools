# nodus-it/docker-tools

Einheitliche Docker-Compose-Befehle für Nodus-Projekte. Statt in jedem Repo
eigene `dev:up`/`dev:artisan`-Scripts zu pflegen, liegt die Logik zentral in
diesem Composer-Plugin. Pro Projekt wird nur noch **konfiguriert**, nicht
kopiert.

Drei Aufruf-Ebenen aus einer Codebasis:

| Form | funktioniert wo | Beispiel |
|------|-----------------|----------|
| `composer d:up` | überall, kein Setup | sicherster Fallback |
| `./vendor/bin/nd up` | überall nach `composer install` | CI |
| `nd up` | mit `.envrc`/direnv oder Alias | Alltag lokal |

## Installation (im Konsumenten-Projekt)

```bash
composer require nodus-it/docker-tools
```

Composer fragt beim ersten Mal, ob das Plugin laufen darf. Dauerhaft erlauben,
indem in der `composer.json` des Projekts steht:

```jsonc
"config": {
    "allow-plugins": {
        "nodus-it/docker-tools": true
    }
}
```

## Konfiguration

Alles Projekt-Spezifische steht unter `extra.nodus-docker` in der
`composer.json`. Alle Felder haben Defaults — ein Laravel-Projekt mit
Standard-Layout braucht oft gar keine.

```jsonc
"extra": {
    "nodus-docker": {
        "dir": ".tools/docker",
        "app-service": "app",
        "artisan": "php artisan",
        "default-env": "dev",
        "environments": {
            "dev":   ["compose.yml", "compose.dev.yml"],
            "stage": ["compose.yml", "compose.stage.yml"],
            "prod":  ["compose.yml", "compose.prod.yml"]
        }
    }
}
```

| Feld | Default | Bedeutung |
|------|---------|-----------|
| `dir` | `.tools/docker` | Verzeichnis der Compose-Dateien |
| `app-service` | `app` | Service für `sh`, `art`, `fresh` |
| `artisan` | `php artisan` | Artisan-Aufruf im Container |
| `default-env` | `dev` | Environment ohne `--env` |
| `environments` | dev/stage/prod | Compose-Datei-Layer pro Environment (Reihenfolge = `-f`-Reihenfolge) |

Die Dateien werden als `<dir>/<datei>` zusammengesetzt:
`compose.yml` ist die Basis, `compose.<env>.yml` überschreibt pro Umgebung
(Layered Overrides).

## Befehle

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

Environment wählen: `--env=prod` (bzw. `-e prod`), z. B.
`composer d:up --env=stage` oder `nd up -e prod`.

Optionen an artisan durchreichen:

```bash
nd art migrate --force                 # sauber durchgereicht
composer d:art -- migrate --force      # via Composer das -- davor
```

## `nd` ohne `vendor/bin/` (optional)

Eine `.envrc` im Projekt (mit [direnv](https://direnv.net/)) legt
`vendor/bin` in den PATH:

```bash
PATH_add vendor/bin
```

Danach genügt `nd up` im Projektverzeichnis. Ohne direnv tut es ein Alias in
der Shell-Rc: `nd() { ./vendor/bin/nd "$@"; }`.

## Lizenz

MIT — siehe [LICENSE](LICENSE).
