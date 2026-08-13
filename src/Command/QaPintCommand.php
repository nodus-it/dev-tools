<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QaPintCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa:pint')
            ->setDescription('Code style with Pint (central rules, overridable per project)');

        // Default = fix. Check mode via a passed-through "--test"
        // (e.g. in CI: composer qa:pint -- --test).
        $this->addToolPassthrough('Zusaetzliche Pint-Argumente (z. B. --test, --dirty)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runHost([...$this->pintArgs(false), ...$this->passthroughArgs()]);
    }
}
