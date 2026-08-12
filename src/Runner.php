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

        // Die `.env` des Projekts ausdruecklich mitgeben. Compose leitet sein
        // Projektverzeichnis aus der ersten `-f`-Datei ab und sucht die Datei
        // sonst NEBEN den Compose-Files statt im Repo-Root. Der Stack startet
        // dann trotzdem — nur mit den Defaults aus den YAMLs statt mit den
        // Projektwerten. Das faellt erst spaeter auf, an einem falschen
        // Hostnamen oder einem Dienst, der ohne sein Passwort dasteht.
        //
        // Nur die Datei, NICHT `--project-directory`: Relative Volume-Pfade in
        // den Compose-Files haengen am Projektverzeichnis und wuerden dadurch
        // ins Leere zeigen.
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
