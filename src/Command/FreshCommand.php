<?php

declare(strict_types=1);

namespace Nodus\DockerTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FreshCommand extends DockerCommand
{
    protected function configure(): void
    {
        $this->setName('d:fresh')
            ->setAliases(['docker:fresh'])
            ->setDescription('Datenbank neu aufsetzen (artisan migrate:fresh --seed)');

        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runner = $this->runner();
        $service = $runner->config()->appService;
        $artisan = $runner->config()->artisanTokens();

        return $runner->compose($this->env($input), [
            'exec', $service, ...$artisan, 'migrate:fresh', '--seed',
        ]);
    }
}
