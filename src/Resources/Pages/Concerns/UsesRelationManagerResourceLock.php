<?php

namespace Blendbyte\FilamentResourceLock\Resources\Pages\Concerns;

use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Livewire\Attributes\On;

trait UsesRelationManagerResourceLock
{
    use UsesLocks;

    public $relatedRecord;

    public string $parentClass;

    public string $relatedClass;

    public function bootUsesRelationManagerResourceLock(): void
    {
        $this->parentClass = get_class($this->getRelationship()->getParent());
        $this->relatedClass = get_class($this->getRelationship()->getRelated());
    }

    #[On('resourceLockObserver::unlock')]
    public function resourceLockObserverUnlock()
    {
        if ($this->relatedRecord) {
            if ($this->relatedRecord->unlock(force: true)) {
                $this->closeLockedResourceModal();
                $this->relatedRecord->refresh();
                $this->relatedRecord->lock();
            }
        } else {
            $ownerRecord = $this->getOwnerRecord();
            if ($ownerRecord->unlock(force: true)) {
                $this->closeLockedResourceModal();
                $ownerRecord->refresh();
                $ownerRecord->lock();
            }
        }
    }

    public function mountTableAction(string $name, ?string $record = null, array $arguments = []): mixed
    {
        parent::mountTableAction($name, $record);

        if ($name === 'edit') {
            $this->relatedRecord = $this->relatedClass::find($record);
            $this->checkIfResourceLockHasExpired($this->relatedRecord);
            $this->lockResource($this->relatedRecord);
        }

        return null;
    }

    public function unmountTableAction(bool $shouldCancelParentActions = true): void
    {
        if ($this->mountedTableActionRecord) {
            $this->relatedRecord = $this->relatedClass::find($this->mountedTableActionRecord);
            $this->relatedRecord->unlock();
        }

        parent::unmountTableAction($shouldCancelParentActions);
    }

    public function resourceLockReturnUrl()
    {
        $parentResource = filament()->getCurrentPanel()->getModelResource(
            $this->getRelationship()->getParent()
        );

        return $parentResource::getUrl('edit', ['record' => $this->getOwnerRecord()->id]);
    }

    public function getResourceLockOwner(): void
    {
        if ($this->relatedRecord?->resourceLock && ResourceLockPlugin::get()->shouldDisplayResourceLockOwner()) {
            $getResourceLockOwnerActionClass = ResourceLockPlugin::get()->getResourceLockOwnerAction();
            $getResourceLockOwnerAction = app($getResourceLockOwnerActionClass);

            $this->resourceLockOwner = $getResourceLockOwnerAction->execute($this->relatedRecord->resourceLock->user);
        }
    }
}
