<?php

declare(strict_types=1);

namespace Nodus\DevTools;

/**
 * Liest die Projekt-Konfiguration aus composer.json -> extra.nodus-dev.
 * Alle Felder haben Defaults, damit das Tool auch ohne Konfiguration laeuft.
 */
final class Config
{
    // -- Docker (d:*) --------------------------------------------------------

    public string $dir = '.tools/docker';

    public string $appService = 'app';

    public string $artisan = 'php artisan';

    public string $defaultEnv = 'dev';

    /** @var array<string, list<string>> */
    public array $environments = [
        'dev' => ['compose.yml', 'compose.dev.yml'],
        'stage' => ['compose.yml', 'compose.stage.yml'],
        'prod' => ['compose.yml', 'compose.prod.yml'],
    ];

    // -- QA (qa:*) -----------------------------------------------------------

    /** Test-Runner-Befehl; null => Heuristik (pest, sonst artisan test). */
    public ?string $test = null;

    /** Optionaler Pfad zur projektlokalen Pint-Config (sonst: lokale pint.json / Paket-Default). */
    public ?string $pintConfig = null;

    /** Optionaler Pfad zur projektlokalen PHPStan-Config (sonst: lokale phpstan.neon / Paket-Default). */
    public ?string $phpstanConfig = null;

    /** Analyse-Pfade fuer den Zero-Config-Fall (keine lokale phpstan.neon vorhanden). */
    /** @var list<string> */
    public array $phpstanPaths = ['app', 'src'];

    public static function load(string $projectRoot): self
    {
        $config = new self();
        $file = $projectRoot.'/composer.json';

        if (! is_file($file)) {
            return $config;
        }

        $json = json_decode((string) file_get_contents($file), true);
        $data = is_array($json) ? ($json['extra']['nodus-dev'] ?? []) : [];

        $config->dir = $data['dir'] ?? $config->dir;
        $config->appService = $data['app-service'] ?? $config->appService;
        $config->artisan = $data['artisan'] ?? $config->artisan;
        $config->defaultEnv = $data['default-env'] ?? $config->defaultEnv;

        if (! empty($data['environments']) && is_array($data['environments'])) {
            $config->environments = $data['environments'];
        }

        $config->test = $data['test'] ?? $config->test;
        $config->pintConfig = $data['pint-config'] ?? $config->pintConfig;
        $config->phpstanConfig = $data['phpstan-config'] ?? $config->phpstanConfig;

        if (! empty($data['phpstan-paths']) && is_array($data['phpstan-paths'])) {
            $config->phpstanPaths = array_values($data['phpstan-paths']);
        }

        return $config;
    }

    /**
     * Wurzel dieses Pakets (enthaelt config/, src/, bin/).
     */
    public static function packageRoot(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return list<string> artisan-Binary als Tokens (z. B. ["php", "artisan"])
     */
    public function artisanTokens(): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($this->artisan)) ?: []));
    }

    /**
     * @return list<string> Compose-Datei-Liste fuer ein Environment (relativ zu $dir)
     */
    public function filesFor(string $env): array
    {
        if (! isset($this->environments[$env])) {
            throw new \RuntimeException(
                "Unbekanntes Environment '{$env}'. Bekannt: ".implode(', ', array_keys($this->environments))
            );
        }

        return $this->environments[$env];
    }
}
