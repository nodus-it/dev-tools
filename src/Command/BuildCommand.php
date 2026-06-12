<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class BuildCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('d:build')
            ->setAliases(['docker:build'])
            ->setDescription('Images bauen (compose build)');

        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runner()->compose($this->env($input), ['build']);
    }
}
