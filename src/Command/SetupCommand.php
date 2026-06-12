<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Bringt ein frisch geklontes Projekt in einen lauffaehigen Zustand.
 * Alle Schritte sind durch Datei-Existenz abgesichert und damit framework-agnostisch.
 */
final class SetupCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('app:setup')
            ->setDescription('Projekt einrichten (.env, key:generate, migrate, npm build, ggf. Boost)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = $this->projectRoot();
        $config = $this->config();
        $artisan = $config->artisanTokens();
        $hasArtisan = is_file($root.'/artisan');

        // 1) .env aus .env.example
        if (! is_file($root.'/.env') && is_file($root.'/.env.example')) {
            $output->writeln('<info>==> .env aus .env.example anlegen</info>');
            copy($root.'/.env.example', $root.'/.env');
        }

        // 2) App-Key
        if ($hasArtisan) {
            $output->writeln('<info>==> php artisan key:generate</info>');
            if (($code = $this->runHost([...$artisan, 'key:generate'])) !== 0) {
                return $code;
            }
        }

        // 3) Migrationen
        if ($hasArtisan) {
            $output->writeln('<info>==> php artisan migrate --force</info>');
            if (($code = $this->runHost([...$artisan, 'migrate', '--force'])) !== 0) {
                return $code;
            }
        }

        // 4) Frontend
        if (is_file($root.'/package.json')) {
            $output->writeln('<info>==> npm install && npm run build</info>');
            if (($code = $this->runHost(['npm', 'install', '--no-audit', '--no-fund'])) !== 0) {
                return $code;
            }
            if (($code = $this->runHost(['npm', 'run', 'build'])) !== 0) {
                return $code;
            }
        }

        // 5) Laravel Boost (nur falls vorhanden) — AI-Kontext einrichten
        if ($hasArtisan && is_dir($root.'/vendor/laravel/boost')) {
            $output->writeln('<info>==> php artisan boost:install</info>');
            $this->runHost([...$artisan, 'boost:install', '--no-interaction']);
        }

        $output->writeln('<info>Setup abgeschlossen.</info>');

        return 0;
    }
}
