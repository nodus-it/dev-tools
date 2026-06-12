<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QaTestCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa:test')
            ->setDescription('Tests ausfuehren (Pest/PHPUnit/artisan test, projektkonfigurierbar)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runHost([...$this->testCommand($this->config()), ...$this->passthroughArgs()]);
    }
}
