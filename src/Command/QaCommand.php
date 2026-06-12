<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Komplette QA-Kette: Pint (--test) -> PHPStan -> Tests. Stoppt beim ersten Fehler.
 */
final class QaCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa')
            ->setDescription('QA-Kette: Pint (pruefen) -> PHPStan -> Tests');

        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Pint korrigieren statt nur pruefen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pint = $this->pintArgs(! $input->getOption('fix'));
        $stan = $this->stanArgs();
        $test = $this->testCommand($this->config());

        foreach (['Pint' => $pint, 'PHPStan' => $stan, 'Tests' => $test] as $label => $cmd) {
            $output->writeln("<info>==> {$label}</info>");
            $code = $this->runHost($cmd);

            if ($code !== 0) {
                $output->writeln("<error>{$label} fehlgeschlagen (Exit {$code}) — QA-Kette abgebrochen.</error>");

                return $code;
            }
        }

        $output->writeln('<info>QA gruen.</info>');

        return 0;
    }
}
