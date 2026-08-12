<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Brings a freshly cloned project into a runnable state.
 * Every step is guarded by a file check and therefore framework-agnostic.
 */
final class SetupCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('app:setup')
            ->setDescription('Set up the project (.env, key:generate, migrate, npm build, Boost if present)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = $this->projectRoot();
        $config = $this->config();
        $artisan = $config->artisanTokens();
        $hasArtisan = is_file($root.'/artisan');

        // 1) .env from .env.example
        if (! is_file($root.'/.env') && is_file($root.'/.env.example')) {
            $output->writeln('<info>==> create .env from .env.example</info>');
            copy($root.'/.env.example', $root.'/.env');
        }

        // 2) application key
        if ($hasArtisan) {
            $output->writeln('<info>==> php artisan key:generate</info>');
            if (($code = $this->runHost([...$artisan, 'key:generate'])) !== 0) {
                return $code;
            }
        }

        // 3) migrations
        if ($hasArtisan) {
            $output->writeln('<info>==> php artisan migrate --force</info>');
            if (($code = $this->runHost([...$artisan, 'migrate', '--force'])) !== 0) {
                return $code;
            }
        }

        // 4) front end
        if (is_file($root.'/package.json')) {
            $output->writeln('<info>==> npm install && npm run build</info>');
            if (($code = $this->runHost(['npm', 'install', '--no-audit', '--no-fund'])) !== 0) {
                return $code;
            }
            if (($code = $this->runHost(['npm', 'run', 'build'])) !== 0) {
                return $code;
            }
        }

        // 5) Laravel Boost (only if present) — set up the AI context
        if ($hasArtisan && is_dir($root.'/vendor/laravel/boost')) {
            $output->writeln('<info>==> php artisan boost:install</info>');
            $this->runHost([...$artisan, 'boost:install', '--no-interaction']);
        }

        $output->writeln('<info>Setup complete.</info>');

        return 0;
    }
}
