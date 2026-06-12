<?php

declare(strict_types=1);

namespace Nodus\DockerTools;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

/**
 * Registriert die d:*-Befehle bei Composer.
 *
 * Damit ein Projekt das Plugin nutzen darf, muss in dessen composer.json stehen:
 *   "config": { "allow-plugins": { "nodus-it/docker-tools": true } }
 */
final class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @return array<class-string, class-string>
     */
    public function getCapabilities(): array
    {
        return [
            CommandProviderCapability::class => CommandProvider::class,
        ];
    }
}
