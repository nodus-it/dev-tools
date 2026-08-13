<?php

declare(strict_types=1);

namespace Nodus\DevTools;

/**
 * Reads the project configuration from composer.json -> extra.nodus-dev.
 * Every field has a default so the tool also runs without any configuration.
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

    /** Test runner command; null => heuristic (pest, otherwise artisan test). */
    public ?string $test = null;

    /** Optional path to a project-local Pint config (otherwise: local pint.json / package default). */
    public ?string $pintConfig = null;

    /** Optional path to a project-local PHPStan config (otherwise: local phpstan.neon / package default). */
    public ?string $phpstanConfig = null;

    /** Paths to analyse in the zero-config case (no local phpstan.neon present). */
    /** @var list<string> */
    public array $phpstanPaths = ['app', 'src'];

    public static function load(string $projectRoot): self
    {
        $config = new self;
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
     * Root of this package (contains config/, src/, bin/).
     */
    public static function packageRoot(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return list<string> the artisan binary as tokens (e.g. ["php", "artisan"])
     */
    public function artisanTokens(): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($this->artisan)) ?: []));
    }

    /**
     * @return list<string> compose files for an environment (relative to $dir)
     */
    public function filesFor(string $env): array
    {
        if (! isset($this->environments[$env])) {
            throw new \RuntimeException(
                "Unknown environment '{$env}'. Known: ".implode(', ', array_keys($this->environments))
            );
        }

        return $this->environments[$env];
    }
}
