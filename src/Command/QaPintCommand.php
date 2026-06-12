<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class QaPintCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa:pint')
            ->setDescription('Code-Style mit Pint (zentrale Regeln, lokal ueberschreibbar)');

        $this->addOption('test', null, InputOption::VALUE_NONE, 'Nur pruefen, nicht aendern (--test)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runHost($this->pintArgs((bool) $input->getOption('test')));
    }
}
