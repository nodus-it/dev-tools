<?php

declare(strict_types=1);

namespace Nodus\DockerTools\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LogsCommand extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('d:logs')
            ->setAliases(['docker:logs'])
            ->setDescription('Logs folgen (compose logs -f)');

        $this->addArgument('service', InputArgument::OPTIONAL, 'Nur dieser Service');
        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = ['logs', '-f'];
        $service = $input->getArgument('service');

        if (is_string($service) && $service !== '') {
            $args[] = $service;
        }

        return $this->runner()->compose($this->env($input), $args);
    }
}
