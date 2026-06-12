<?php

declare(strict_types=1);

namespace Nodus\DockerTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PsCommand extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('d:ps')
            ->setAliases(['docker:ps'])
            ->setDescription('Container-Status (compose ps)');

        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runner()->compose($this->env($input), ['ps']);
    }
}
