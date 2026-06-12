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
        $config = $this->config();

        // 1) Pint
        $pint = ['vendor/bin/pint'];
        if (! $input->getOption('fix')) {
            $pint[] = '--test';
        }
        $pintCfg = $this->resolveQaConfig($config->pintConfig, 'pint.json', 'config/pint.json');
        if ($pintCfg !== null) {
            $pint[] = '--config='.$pintCfg;
        }

        // 2) PHPStan
        $stan = ['vendor/bin/phpstan', 'analyse', '--no-progress'];
        if (is_string($config->phpstanConfig) && $config->phpstanConfig !== '') {
            $stan[] = '--configuration='.$config->phpstanConfig;
        }

        // 3) Tests
        $test = $this->testCommand($config);

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
