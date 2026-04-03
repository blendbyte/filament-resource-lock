<?php

namespace Blendbyte\FilamentResourceLock\Models\Concerns;

use Blendbyte\FilamentResourceLock\Events\ResourceLockExpired;
use Blendbyte\FilamentResourceLock\Events\ResourceLockForceUnlocked;
use Blendbyte\FilamentResourceLock\Events\ResourceLocked;
use Blendbyte\FilamentResourceLock\Events\ResourceUnlocked;
use Blendbyte\FilamentResourceLock\Models\ResourceLock;
use Blendbyte\FilamentResourceLock\ResourceLockPlugin;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * The HasLocks trait provides several functions to models to handle locking and unlocking of records.
 */
trait HasLocks
{
    /**
     * Get the morphOne relationship for the ResourceLock model.
     */
    public function resourceLock(): MorphOne
    {
        return $this->morphOne(ResourceLockPlugin::get()->getResourceLockModel(), 'lockable')->latestOfMany();
    }

    /**
     * Lock the resource.
     * Calling lock() on an already locked model will refresh the lock if it belongs to the current user.
     *
     * @return bool Returns true if locking the resource was successful, false otherwise.
     */
    public function lock(): bool
    {
        if ($this->isUnlocked()) {
            // An expired record exists — dispatch expiry event, clean it up
            if ($this->resourceLock !== null && $this->resourceLock->isExpired()) {
                $expiredUserId = $this->resourceLock->user_id;
                $this->resourceLock()->delete();
                $this->unsetRelation('resourceLock');

                if (config('filament-resource-lock.events.enabled', true)) {
                    ResourceLockExpired::dispatch($this, $expiredUserId);
                }
            }

            $resourceLockModel = ResourceLockPlugin::get()->getResourceLockModel();
            $guard = $this->getCurrentAuthGuardName();
            $resourceLock = new $resourceLockModel;
            $resourceLock->user_id = auth()->guard($guard)->user()->id;
            $this->resourceLock()->save($resourceLock);

            if (config('filament-resource-lock.events.enabled', true)) {
                ResourceLocked::dispatch($this, $resourceLock->user_id);
            }

            return true;
        }

        if ($this->isLockedByCurrentUser()) {
            $this->resourceLock()->touch();

            return true;
        }

        return false;
    }

    /**
     * Check if the resource is locked by the current user.
     *
     * @return bool Returns true if the resource is locked by the current user, false otherwise.
     */
    public function isLockedByCurrentUser(): bool
    {
        $resourceLock = $this->resourceLock;
        $guard = $this->getCurrentAuthGuardName();

        if ($resourceLock && $resourceLock->user->id === auth()->guard($guard)->user()->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if the resource is locked.
     *
     * @return bool Returns true if the resource is locked, false otherwise.
     */
    public function isLocked(): bool
    {
        if (is_null($this->resourceLock)) {
            return false;
        }

        return $this->resourceLock->exists() && ! $this->resourceLock->isExpired();
    }

    /**
     * Check if the resource is unlocked.
     *
     * @return bool Returns true if the resource is unlocked, false otherwise.
     */
    public function isUnlocked(): bool
    {
        return ! $this->isLocked();
    }

    /**
     * Check if the lock on the resource has expired.
     *
     * @return bool Returns true if the lock on the resource has expired, false otherwise.
     */
    public function hasExpiredLock(): bool
    {
        if ($this->isUnlocked()) {
            return true;
        }

        return $this->resourceLock->isExpired();
    }

    /**
     * Unlock the resource.
     *
     * @param  bool  $force  Whether to force unlock or not.
     * @return bool Returns true if unlocking the resource was successful, false otherwise.
     */
    public function unlock(bool $force = false): bool
    {
        if ($this->isLocked()) {
            if ($force || $this->lockCreatedByCurrentUser() || $this->hasExpiredLock()) {
                $originalUserId = $this->resourceLock->user_id;
                $this->resourceLock()->delete();

                if (config('filament-resource-lock.events.enabled', true)) {
                    if ($force) {
                        ResourceLockForceUnlocked::dispatch($this, $originalUserId, auth()->id());
                    } else {
                        ResourceUnlocked::dispatch($this, $originalUserId);
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Check if the lock was created by the current user.
     *
     * @return bool Returns true if the lock was created by the current user, false otherwise.
     */
    public function lockCreatedByCurrentUser(): bool
    {
        $guard = $this->getCurrentAuthGuardName();

        return $this->resourceLock->user_id === auth()->guard($guard)->user()->id;
    }

    /**
     * Finds and returns the current guard of the auth user.
     *
     * @return array|null
     */
    private function getCurrentAuthGuardName(): ?string
    {
        if (Filament::getCurrentPanel() === null) {
            return null;
        }

        return Filament::getCurrentPanel()->auth()->name;
    }
}
