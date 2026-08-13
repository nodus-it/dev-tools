<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('d:up')
            ->setAliases(['docker:up'])
            ->setDescription('Start containers (compose up -d)');

        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runner()->compose($this->env($input), ['up', '-d']);
    }
}
