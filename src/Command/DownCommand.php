<?php

declare(strict_types=1);

namespace Nodus\DockerTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DownCommand extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('d:down')
            ->setAliases(['docker:down'])
            ->setDescription('Container stoppen (compose down)');

        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runner()->compose($this->env($input), ['down']);
    }
}
