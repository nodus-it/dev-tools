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
            ->setDescription('Code-Style mit Pint (zentrale Regeln, lokal ueberschreibbar)');

        // Default = korrigieren. Pruef-Modus via durchgereichtem "--test"
        // (z. B. in CI: composer qa:pint -- --test).
        $this->addToolPassthrough('Zusaetzliche Pint-Argumente (z. B. --test, --dirty)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->runHost([...$this->pintArgs(false), ...$this->passthroughArgs()]);
    }
}
