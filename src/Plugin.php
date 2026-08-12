<?php

declare(strict_types=1);

namespace Nodus\DevTools;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

/**
 * Registers the d:* and qa:* commands with Composer.
 *
 * A project may only use the plugin if its composer.json contains:
 *   "config": { "allow-plugins": { "nodus-it/dev-tools": true } }
 */
final class Plugin implements Capable, PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void {}

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

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
