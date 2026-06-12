<?php

declare(strict_types=1);

namespace Nodus\DockerTools;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Nodus\DockerTools\Command\ArtisanCommand;
use Nodus\DockerTools\Command\BuildCommand;
use Nodus\DockerTools\Command\DownCommand;
use Nodus\DockerTools\Command\FreshCommand;
use Nodus\DockerTools\Command\LogsCommand;
use Nodus\DockerTools\Command\PsCommand;
use Nodus\DockerTools\Command\ShCommand;
use Nodus\DockerTools\Command\UpCommand;

final class CommandProvider implements CommandProviderCapability
{
    /**
     * @return list<\Composer\Command\BaseCommand>
     */
    public function getCommands(): array
    {
        return [
            new UpCommand(),
            new DownCommand(),
            new BuildCommand(),
            new PsCommand(),
            new LogsCommand(),
            new ShCommand(),
            new ArtisanCommand(),
            new FreshCommand(),
        ];
    }
}
