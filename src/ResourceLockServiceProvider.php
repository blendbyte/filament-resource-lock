<?php

namespace Blendbyte\FilamentResourceLock;

use Blendbyte\FilamentResourceLock\Console\Commands\ResourceLockClearCommand;
use Blendbyte\FilamentResourceLock\Console\Commands\ResourceLockClearExpiredCommand;
use Blendbyte\FilamentResourceLock\Listeners\AuditResourceLockEventSubscriber;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ResourceLockServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-resource-lock';

    public function packageRegistered(): void
    {
        $this->callAfterResolving('livewire.finder', function ($finder) {
            $finder->addComponent('filament-resource-lock-observer', class: Http\Livewire\ResourceLockObserver::class);
        });
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        Livewire::component('filament-resource-lock-observer', Http\Livewire\ResourceLockObserver::class);

        Event::subscribe(AuditResourceLockEventSubscriber::class);
    }

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasConfigFile()
            ->hasMigration('create_resource_lock_table')
            ->hasMigration('create_resource_lock_audit_table')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('blendbyte/filament-resource-lock');
            })
            ->hasCommand(ResourceLockClearCommand::class)
            ->hasCommand(ResourceLockClearExpiredCommand::class);
    }
}
