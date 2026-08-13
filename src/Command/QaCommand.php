<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The full QA chain: Pint (--test) -> PHPStan -> tests. Stops at the first failure.
 */
final class QaCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('qa')
            ->setDescription('QA chain: Pint (check) -> PHPStan -> tests');

        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Fix with Pint instead of only checking');
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
                $output->writeln("<error>{$label} failed (exit {$code}) — QA chain aborted.</error>");

                return $code;
            }
        }

        $output->writeln('<info>QA green.</info>');

        return 0;
    }
}
