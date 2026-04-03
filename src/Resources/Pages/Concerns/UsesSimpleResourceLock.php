<?php

namespace Blendbyte\FilamentResourceLock\Resources\Pages\Concerns;

use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Livewire\Attributes\On;

trait UsesSimpleResourceLock
{
    use UsesLocks;

    public string $returnUrl;

    public $resourceRecord;

    public string $resourceLockType;

    private bool $isLockable = true;

    public function mountTableAction(string $name, ?string $record = null, array $arguments = []): mixed
    {
        parent::mountTableAction($name, $record);
        $this->resourceRecord = $this->getMountedTableActionRecord();
        $this->returnUrl = $this->getResource()::getUrl('index');
        $this->initializeResourceLock($this->resourceRecord);

        if ($this->isReadOnly) {
            $owner = $this->resourceLockOwner;
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title($owner
                    ? __('filament-resource-lock::read-only.banner_heading_user', ['user' => $owner])
                    : __('filament-resource-lock::read-only.banner_heading'))
                ->send();
            $this->isReadOnly = false;

            return null;
        }

        $this->setupPolling();

        return null;
    }

    public function callMountedTableAction(array $arguments = []): mixed
    {
        if (ResourceLockPlugin::get()->shouldCheckLocksBeforeSaving()) {
            $this->resourceRecord->refresh();
            if ($this->resourceRecord->isLocked() && ! $this->resourceRecord->isLockedByCurrentUser()) {
                $this->checkIfResourceLockHasExpired($this->resourceRecord);
                $this->lockResource($this->resourceRecord);

                return null;
            }
        }
        parent::callMountedTableAction($arguments);

        return null;
    }

    #[On('resourceLockObserver::unloadSimple')]
    public function resourceLockObserverUnload()
    {
        $this->resourceRecord->unlock();
        $this->disablePolling();
    }

    #[On('resourceLockObserver::unlock')]
    public function resourceLockObserverUnlock()
    {
        if ($this->resourceRecord->unlock(force: true)) {
            $this->closeLockedResourceModal();
            $this->resourceRecord->lock();
        }
    }

    public function getResourceLockOwner(): void
    {
        if (! $this->resourceRecord?->resourceLock) {
            return;
        }

        if ($this->isReadOnly || ResourceLockPlugin::get()->shouldDisplayResourceLockOwner()) {
            $getResourceLockOwnerActionClass = ResourceLockPlugin::get()->getResourceLockOwnerAction();
            $getResourceLockOwnerAction = app($getResourceLockOwnerActionClass);

            $this->resourceLockOwner = $getResourceLockOwnerAction->execute($this->resourceRecord->resourceLock->user);
        }
    }
}
