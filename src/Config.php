<?php

declare(strict_types=1);

namespace Nodus\DockerTools;

/**
 * Liest die Projekt-Konfiguration aus composer.json -> extra.nodus-docker.
 * Alle Felder haben Defaults, damit das Tool auch ohne Konfiguration laeuft.
 */
final class Config
{
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

    public static function load(string $projectRoot): self
    {
        $config = new self();
        $file = $projectRoot.'/composer.json';

        if (! is_file($file)) {
            return $config;
        }

        $json = json_decode((string) file_get_contents($file), true);
        $data = is_array($json) ? ($json['extra']['nodus-docker'] ?? []) : [];

        $config->dir = $data['dir'] ?? $config->dir;
        $config->appService = $data['app-service'] ?? $config->appService;
        $config->artisan = $data['artisan'] ?? $config->artisan;
        $config->defaultEnv = $data['default-env'] ?? $config->defaultEnv;

        if (! empty($data['environments']) && is_array($data['environments'])) {
            $config->environments = $data['environments'];
        }

        return $config;
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
