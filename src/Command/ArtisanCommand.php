<?php

declare(strict_types=1);

namespace Nodus\DevTools\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ArtisanCommand extends AbstractDevCommand
{
    protected function configure(): void
    {
        $this->setName('d:art')
            ->setAliases(['d:artisan', 'docker:artisan'])
            ->setDescription('Run artisan inside the app container');

        // For the help output only; the real arguments come from passthroughArgs().
        $this->addArgument('args', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'artisan arguments');
        $this->addEnvOption();
        $this->ignoreValidationErrors();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runner = $this->runner();
        $service = $runner->config()->appService;
        $artisan = $runner->config()->artisanTokens();

        return $runner->compose($this->env($input), [
            'exec', $service, ...$artisan, ...$this->passthroughArgs(),
        ]);
    }
}
