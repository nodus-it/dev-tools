<?php

declare(strict_types=1);

namespace Nodus\DevTools;

/**
 * Builds the "docker compose -f ..." invocations and runs them, passing the TTY through.
 */
final class Runner
{
    public function __construct(
        private readonly Config $config,
        private readonly string $projectRoot,
    ) {}

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @return list<string> ["docker", "compose", "--env-file", "<root>/.env", "-f", "<dir>/<file>", ...]
     */
    public function baseArgs(string $env): array
    {
        $args = ['docker', 'compose'];

        // Pass the project's `.env` explicitly. Compose derives its project
        // directory from the first `-f` file and would otherwise look for the
        // file NEXT TO the compose files instead of in the repository root. The
        // stack still starts — but with the defaults from the YAML files rather
        // than the project's values. That surfaces much later, as a wrong
        // hostname or a service left without its password.
        //
        // Only the file, NOT `--project-directory`: relative volume paths in the
        // compose files are resolved against the project directory and would
        // point nowhere.
        $envFile = $this->projectRoot.'/.env';

        if (is_file($envFile)) {
            $args[] = '--env-file';
            $args[] = $envFile;
        }

        foreach ($this->config->filesFor($env) as $file) {
            $args[] = '-f';
            $args[] = $this->config->dir.'/'.$file;
        }

        return $args;
    }

    /**
     * @param  list<string>  $extra
     */
    public function compose(string $env, array $extra): int
    {
        return $this->exec([...$this->baseArgs($env), ...$extra]);
    }

    /**
     * @param  list<string>  $cmd
     */
    private function exec(array $cmd): int
    {
        $line = implode(' ', array_map('escapeshellarg', $cmd));
        $full = 'cd '.escapeshellarg($this->projectRoot).' && '.$line;

        passthru($full, $code);

        return $code;
    }
}
