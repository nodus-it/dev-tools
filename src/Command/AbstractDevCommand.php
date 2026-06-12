<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Nodus\DevTools\Config;
use Nodus\DevTools\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractDevCommand extends BaseCommand
{
    protected function projectRoot(): string
    {
        $root = realpath(dirname(Factory::getComposerFile()));

        return $root !== false ? $root : (getcwd() ?: '.');
    }

    protected function runner(): Runner
    {
        $root = $this->projectRoot();

        return new Runner(Config::load($root), $root);
    }

    protected function env(InputInterface $input): string
    {
        $env = $input->getOption('env');

        if (is_string($env) && $env !== '') {
            return $env;
        }

        return Config::load($this->projectRoot())->defaultEnv;
    }

    /**
     * Rohe Tokens hinter dem Command-Namen aus argv, ohne --env/-e.
     * Noetig fuer sauberes Durchreichen von artisan-Optionen (z. B. --force),
     * die die Symfony-Console sonst selbst zu parsen versucht.
     *
     * @return list<string>
     */
    protected function passthroughArgs(): array
    {
        $argv = $_SERVER['argv'] ?? [];
        // argv[0] = composer-Binary, argv[1] = Command-Name -> beides weg
        $tokens = array_slice($argv, 2);

        $result = [];
        for ($i = 0, $n = count($tokens); $i < $n; $i++) {
            $token = $tokens[$i];

            if ($token === '--env' || $token === '-e') {
                $i++; // Wert ueberspringen

                continue;
            }

            if (str_starts_with($token, '--env=')) {
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }

    protected function addEnvOption(): void
    {
        $this->addOption(
            'env',
            'e',
            InputOption::VALUE_REQUIRED,
            'Ziel-Environment (dev|stage|prod)'
        );
    }

    protected function config(): Config
    {
        return Config::load($this->projectRoot());
    }

    /**
     * Fuehrt einen Host-Befehl im Projekt-Root aus (TTY durchgereicht).
     *
     * @param  list<string>  $cmd
     */
    protected function runHost(array $cmd): int
    {
        $line = 'cd '.escapeshellarg($this->projectRoot()).' && '.implode(' ', array_map('escapeshellarg', $cmd));

        passthru($line, $code);

        return $code;
    }

    /**
     * Pfad zu einer projektlokalen oder zentral mitgelieferten QA-Config.
     * Reihenfolge: explizit konfiguriert -> lokal im Projekt -> Paket-Default.
     */
    protected function resolveQaConfig(?string $configured, string $localName, string $packageDefault): ?string
    {
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $local = $this->projectRoot().'/'.$localName;
        if (is_file($local)) {
            return $local; // erbt i. d. R. die zentrale Config via includes/import
        }

        $bundled = Config::packageRoot().'/'.$packageDefault;

        return is_file($bundled) ? $bundled : null;
    }

    /**
     * Ermittelt den Test-Runner-Befehl als Tokens.
     *
     * @return list<string>
     */
    protected function testCommand(Config $config): array
    {
        if (is_string($config->test) && $config->test !== '') {
            return array_values(array_filter(preg_split('/\s+/', trim($config->test)) ?: []));
        }

        // Heuristik: Pest, sonst artisan test
        if (is_file($this->projectRoot().'/vendor/bin/pest')) {
            return ['vendor/bin/pest'];
        }

        return [...$config->artisanTokens(), 'test'];
    }
}
