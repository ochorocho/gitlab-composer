<?php

namespace Gitlab\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\Capable as CapableInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Gitlab\Composer\Command\CommandProvider;

final class Plugin
    implements PluginInterface, CapableInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
//        file_put_contents('./tmp/composer.log', __METHOD__ . "\n",FILE_APPEND);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => 'installOrUpdate',
            'post-update-cmd' => 'installOrUpdate',
        );
    }

    public function installOrUpdate($event)
    {
//        file_put_contents('./tmp/composer.log', __METHOD__ . "\n",FILE_APPEND);
//        file_put_contents('./tmp/composer.log', get_class($event) . "\n",FILE_APPEND);
//        file_put_contents('./tmp/composer.log', $event->getName() . "\n",FILE_APPEND);
    }

    public function getCapabilities(): array
    {
        return [
            CommandProviderCapability::class => CommandProvider::class
        ];
    }
}
