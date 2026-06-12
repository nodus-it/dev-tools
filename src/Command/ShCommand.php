<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ShCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('d:sh')
            ->setAliases(['docker:sh'])
            ->setDescription('Shell im App-Container oeffnen (bash, sonst sh)');

        $this->addArgument('service', InputArgument::OPTIONAL, 'Service (Default: app-service aus Config)');
        $this->addEnvOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runner = $this->runner();
        $service = $input->getArgument('service');

        if (! is_string($service) || $service === '') {
            $service = $runner->config()->appService;
        }

        return $runner->compose($this->env($input), [
            'exec', $service, 'sh', '-c', 'command -v bash >/dev/null && exec bash || exec sh',
        ]);
    }
}
