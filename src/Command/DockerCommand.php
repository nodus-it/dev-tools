<?php

declare(strict_types=1);

namespace Nodus\DockerTools\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Nodus\DockerTools\Config;
use Nodus\DockerTools\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class DockerCommand extends BaseCommand
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
}
