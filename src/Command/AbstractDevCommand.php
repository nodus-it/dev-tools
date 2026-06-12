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
     * Baut den Pint-Aufruf.
     *
     * @return list<string>
     */
    protected function pintArgs(bool $check): array
    {
        $cmd = ['vendor/bin/pint'];

        if ($check) {
            $cmd[] = '--test';
        }

        $cfg = $this->resolveQaConfig($this->config()->pintConfig, 'pint.json', 'config/pint.json');
        if ($cfg !== null) {
            $cmd[] = '--config='.$cfg;
        }

        return $cmd;
    }

    /**
     * Baut den PHPStan-Aufruf. Reihenfolge:
     *   1. explizit konfigurierte Config
     *   2. projektlokale phpstan.neon(.dist) -> Autodiscovery
     *   3. Zero-Config: zentrale Config + vorhandene Default-Pfade als CLI-Argument
     *
     * @return list<string>
     */
    protected function stanArgs(): array
    {
        $config = $this->config();
        $root = $this->projectRoot();
        $cmd = ['vendor/bin/phpstan', 'analyse', '--no-progress'];

        if (is_string($config->phpstanConfig) && $config->phpstanConfig !== '') {
            $cmd[] = '--configuration='.$config->phpstanConfig;

            return $cmd;
        }

        if (is_file($root.'/phpstan.neon') || is_file($root.'/phpstan.neon.dist')) {
            return $cmd; // PHPStan findet die lokale Config selbst
        }

        $bundled = Config::packageRoot().'/config/phpstan.neon';
        if (is_file($bundled)) {
            $cmd[] = '--configuration='.$bundled;
        }
        foreach ($config->phpstanPaths as $path) {
            if (is_dir($root.'/'.$path)) {
                $cmd[] = $path;
            }
        }

        return $cmd;
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
