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
            ->setAliases(['pint'])
            ->setDescription('Code-Style mit Pint (zentrale Regeln, lokal ueberschreibbar)');

        $this->addOption('test', null, InputOption::VALUE_NONE, 'Nur pruefen, nicht aendern (--test)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->config();
        $cmd = ['vendor/bin/pint'];

        if ($input->getOption('test')) {
            $cmd[] = '--test';
        }

        $cfg = $this->resolveQaConfig($config->pintConfig, 'pint.json', 'config/pint.json');
        if ($cfg !== null) {
            $cmd[] = '--config='.$cfg;
        }

        return $this->runHost($cmd);
    }
}
