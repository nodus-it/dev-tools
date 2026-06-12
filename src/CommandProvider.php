<?php

declare(strict_types=1);

namespace Nodus\DevTools;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Nodus\DevTools\Command\ArtisanCommand;
use Nodus\DevTools\Command\BuildCommand;
use Nodus\DevTools\Command\DownCommand;
use Nodus\DevTools\Command\FreshCommand;
use Nodus\DevTools\Command\LogsCommand;
use Nodus\DevTools\Command\PsCommand;
use Nodus\DevTools\Command\QaCommand;
use Nodus\DevTools\Command\QaPintCommand;
use Nodus\DevTools\Command\QaStanCommand;
use Nodus\DevTools\Command\QaTestCommand;
use Nodus\DevTools\Command\SetupCommand;
use Nodus\DevTools\Command\ShCommand;
use Nodus\DevTools\Command\UpCommand;

final class CommandProvider implements CommandProviderCapability
{
    /**
     * @return list<\Composer\Command\BaseCommand>
     */
    public function getCommands(): array
    {
        return [
            // Docker (d:*)
            new UpCommand(),
            new DownCommand(),
            new BuildCommand(),
            new PsCommand(),
            new LogsCommand(),
            new ShCommand(),
            new ArtisanCommand(),
            new FreshCommand(),
            // QA (qa:*)
            new QaPintCommand(),
            new QaStanCommand(),
            new QaTestCommand(),
            new QaCommand(),
            // Projekt
            new SetupCommand(),
        ];
    }
}
