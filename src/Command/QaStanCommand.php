<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QaStanCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa:stan')
            ->setAliases(['stan', 'phpstan'])
            ->setDescription('Statische Analyse mit PHPStan (lokale Config erbt zentrale via includes)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->config();
        $cmd = ['vendor/bin/phpstan', 'analyse', '--no-progress'];

        // PHPStan findet eine lokale phpstan.neon selbst; explizit nur, wenn konfiguriert.
        if (is_string($config->phpstanConfig) && $config->phpstanConfig !== '') {
            $cmd[] = '--configuration='.$config->phpstanConfig;
        }

        return $this->runHost($cmd);
    }
}
