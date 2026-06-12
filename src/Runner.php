<?php

declare(strict_types=1);

namespace Nodus\DevTools;

/**
 * Baut die "docker compose -f ..."-Aufrufe und fuehrt sie TTY-durchreichend aus.
 */
final class Runner
{
    public function __construct(
        private readonly Config $config,
        private readonly string $projectRoot,
    ) {
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @return list<string> ["docker", "compose", "-f", "<dir>/<file>", ...]
     */
    public function baseArgs(string $env): array
    {
        $args = ['docker', 'compose'];

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
