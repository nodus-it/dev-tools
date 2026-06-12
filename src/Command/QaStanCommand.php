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
            ->setDescription('Statische Analyse mit PHPStan (lokale Config erbt zentrale via includes)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runHost($this->stanArgs());
    }
}
