<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Composer\Command\BaseCommand;
use Composer\Factory;
use Nodus\DevTools\Config;
use Nodus\DevTools\Runner;
use Symfony\Component\Console\Input\InputArgument;
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
     * Raw tokens after the command name from argv, without --env/-e.
     * Needed to pass artisan options (e.g. --force) through cleanly; the
     * Symfony console would otherwise try to parse them itself.
     *
     * @return list<string>
     */
    protected function passthroughArgs(): array
    {
        $argv = $_SERVER['argv'] ?? [];
        // argv[0] = composer binary, argv[1] = command name -> drop both
        $tokens = array_slice($argv, 2);

        $result = [];
        for ($i = 0, $n = count($tokens); $i < $n; $i++) {
            $token = $tokens[$i];

            if ($token === '--env' || $token === '-e') {
                $i++; // skip the value

                continue;
            }

            if (str_starts_with($token, '--env=')) {
                continue;
            }

            // The "--" separator (Composer/shell) is not a tool argument; left in,
            // it would make pint/phpstan/pest read the following flags as
            // arguments instead of options.
            if ($token === '--') {
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }

    /**
     * Configures a qa command so additional tokens are passed raw to the
     * underlying tool (pint/phpstan/pest): an IS_ARRAY argument (for the help
     * output only) plus ignoreValidationErrors(), so Symfony accepts arbitrary
     * flags. execute() takes the real tokens from argv via passthroughArgs() —
     * including those behind "--".
     */
    protected function addToolPassthrough(string $description): void
    {
        $this->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, $description);
        $this->ignoreValidationErrors();
    }

    protected function addEnvOption(): void
    {
        $this->addOption(
            'env',
            'e',
            InputOption::VALUE_REQUIRED,
            'Target environment (dev|stage|prod)'
        );
    }

    protected function config(): Config
    {
        return Config::load($this->projectRoot());
    }

    /**
     * Runs a host command in the project root (TTY passed through).
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
     * Path to a project-local or centrally shipped QA config.
     * Order: explicitly configured -> local to the project -> package default.
     */
    protected function resolveQaConfig(?string $configured, string $localName, string $packageDefault): ?string
    {
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $local = $this->projectRoot().'/'.$localName;
        if (is_file($local)) {
            return $local; // usually inherits the central config via includes/import
        }

        $bundled = Config::packageRoot().'/'.$packageDefault;

        return is_file($bundled) ? $bundled : null;
    }

    /**
     * Builds the Pint invocation.
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
     * Builds the PHPStan invocation. Order:
     *   1. explicitly configured config
     *   2. project-local phpstan.neon(.dist) -> autodiscovery
     *   3. zero config: central config + existing default paths as CLI arguments
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
            return $cmd; // PHPStan picks up the local config itself
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
     * Determines the test runner command as tokens.
     *
     * @return list<string>
     */
    protected function testCommand(Config $config): array
    {
        if (is_string($config->test) && $config->test !== '') {
            return array_values(array_filter(preg_split('/\s+/', trim($config->test)) ?: []));
        }

        // Heuristic: Pest, otherwise artisan test
        if (is_file($this->projectRoot().'/vendor/bin/pest')) {
            return ['vendor/bin/pest'];
        }

        return [...$config->artisanTokens(), 'test'];
    }
}
